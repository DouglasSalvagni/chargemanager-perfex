<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Billing_groups extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('chargemanager_billing_groups_model');
        $this->load->model('contracts_model');
        $this->load->model('chargemanager_charges_model');
        $this->load->library('billing_groups_validation');
    }

    /**
     * Get contracts for a specific client via AJAX
     */
    public function get_client_contracts()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'view')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        $client_id = $this->input->post('client_id');
        
        if (empty($client_id)) {
            echo json_encode([
                'success' => false, 
                'message' => _l('chargemanager_client_id_required')
            ]);
            return;
        }

        try {
            // Get signed contracts for the client
            $this->db->select('id, subject, contract_value, datestart, dateend, signed, marked_as_signed');
            $this->db->where('client', $client_id);
            $this->db->where('trash', 0);
            $this->db->where('(signed = 1 OR marked_as_signed = 1)');
            $this->db->where('contract_value >', 0);
            
            // Only include non-expired contracts
            $this->db->group_start();
            $this->db->where('dateend IS NULL');
            $this->db->or_where('dateend >=', date('Y-m-d'));
            $this->db->group_end();
            
            $this->db->order_by('datestart', 'DESC');
            $contracts = $this->db->get(db_prefix() . 'contracts')->result_array();

            // Filter out contracts that already have billing groups
            $available_contracts = [];
            foreach ($contracts as $contract) {
                // Check if contract already has a billing group
                $this->db->where('contract_id', $contract['id']);
                $existing_billing_group = $this->db->get(db_prefix() . 'chargemanager_billing_groups')->row();
                
                if (!$existing_billing_group) {
                    $available_contracts[] = [
                        'id' => $contract['id'],
                        'subject' => $contract['subject'],
                        'contract_value' => $contract['contract_value'],
                        'datestart' => $contract['datestart'],
                        'dateend' => $contract['dateend']
                    ];
                }
            }

            $message = count($available_contracts) > 0 ? 
                _l('chargemanager_contracts_loaded_successfully') : 
                _l('chargemanager_no_available_contracts');
                
            echo json_encode([
                'success' => true,
                'message' => $message,
                'contracts' => $available_contracts
            ]);

        } catch (Exception $e) {
            log_activity('ChargeManager Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => _l('chargemanager_error_loading_contracts'),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get contract details via AJAX
     */
    public function get_contract_details()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'view')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        $contract_id = $this->input->post('contract_id');
        
        if (empty($contract_id)) {
            echo json_encode([
                'success' => false, 
                'message' => _l('chargemanager_contract_id_required')
            ]);
            return;
        }

        try {
            $this->db->where('id', $contract_id);
            $contract = $this->db->get(db_prefix() . 'contracts')->row();

            if (!$contract) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('chargemanager_contract_not_found')
                ]);
                return;
            }

            // Check if contract is signed and valid
            $is_signed = $contract->signed == 1 || $contract->marked_as_signed == 1;
            $is_expired = !empty($contract->dateend) && $contract->dateend < date('Y-m-d');
            $has_value = $contract->contract_value > 0;

            if (!$is_signed || $is_expired || !$has_value) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('chargemanager_contract_invalid')
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'contract' => [
                    'id' => $contract->id,
                    'subject' => $contract->subject,
                    'contract_value' => $contract->contract_value,
                    'datestart' => $contract->datestart,
                    'dateend' => $contract->dateend,
                    'description' => $contract->description
                ]
            ]);

        } catch (Exception $e) {
            log_activity('ChargeManager Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => _l('chargemanager_error_loading_contract'),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create a new billing group
     */
    public function create()
    {
        if (!has_permission('chargemanager', '', 'create')) {
            access_denied('chargemanager create');
        }

        $data = $this->input->post();
        
        try {
            // Validate input data
            $validation_result = $this->billing_groups_validation->validate_create($data);
            
            if (!$validation_result['success']) {
                set_alert('danger', implode('<br>', $validation_result['errors']));
                echo json_encode([
                    'success' => false,
                    'message' => implode('<br>', $validation_result['errors'])
                ]);
                return;
            }

            // Additional validation for charges
            $validation_errors = [];
            $today = date('Y-m-d');
            
            if (empty($data['charges']) || !is_array($data['charges'])) {
                $validation_errors[] = _l('chargemanager_error_no_charges_provided');
            } else {
                foreach ($data['charges'] as $index => $charge_data) {
                    $charge_number = $index + 1;
                    
                    // Validate due_date
                    if (empty($charge_data['due_date'])) {
                        $validation_errors[] = sprintf(_l('chargemanager_error_due_date_required'), $charge_number);
                    } elseif ($charge_data['due_date'] < $today) {
                        $validation_errors[] = sprintf(_l('chargemanager_error_due_date_past'), $charge_number, $charge_data['due_date']);
                    }
                    
                    // Validate amount
                    if (empty($charge_data['amount']) || !is_numeric($charge_data['amount']) || floatval($charge_data['amount']) <= 0) {
                        $validation_errors[] = sprintf(_l('chargemanager_error_invalid_amount'), $charge_number);
                    }
                    
                    // Validate billing_type
                    if (empty($charge_data['billing_type'])) {
                        $validation_errors[] = sprintf(_l('chargemanager_error_billing_type_required'), $charge_number);
                    } elseif (!in_array($charge_data['billing_type'], ['BOLETO', 'PIX', 'CREDIT_CARD'])) {
                        $validation_errors[] = sprintf(_l('chargemanager_error_invalid_billing_type'), $charge_number);
                    }
                }
            }
            
            // If there are validation errors, return early
            if (!empty($validation_errors)) {
                $error_message = implode('<br>', $validation_errors);
                set_alert('danger', $error_message);
                echo json_encode([
                    'success' => false,
                    'message' => $error_message
                ]);
                return;
            }

            // Load Gateway Manager
            $this->load->library('chargemanager/Gateway_manager');

            // Create billing group
            $billing_group_data = [
                'client_id' => $data['client_id'],
                'contract_id' => $data['contract_id'],
                'status' => 'open',
                'total_amount' => array_sum(array_column($data['charges'], 'amount'))
            ];

            $billing_group_id = $this->chargemanager_billing_groups_model->create($billing_group_data);

            if (!$billing_group_id) {
                $error_msg = _l('chargemanager_error_creating_billing_group');
                set_alert('danger', $error_msg);
                throw new Exception($error_msg);
            }

            // Create charges in gateway and save locally
            $this->load->model('chargemanager_charges_model');
            $charges_created = [];
            $charges_failed = [];
            
            foreach ($data['charges'] as $index => $charge_data) {
                $charge_number = $index + 1;
                
                try {
                    // Mapear os campos do frontend para o gateway
                    $gateway_charge_data = [
                        'billing_group_id' => $billing_group_id,
                        'client_id' => $data['client_id'],
                        'value' => floatval($charge_data['amount']), // Mapear 'amount' para 'value'
                        'due_date' => $charge_data['due_date'],
                        'billing_type' => $charge_data['billing_type'],
                        'description' => 'Cobrança via ChargeManager - Billing Group #' . $billing_group_id,
                        'gateway' => 'asaas' // Especificar o gateway
                    ];
                    
                    // Create charge via Gateway Manager
                    $gateway_result = $this->gateway_manager->create_charge($gateway_charge_data);
                    
                    if ($gateway_result['success']) {
                        // Save charge to local database
                        $local_charge_data = [
                            'gateway_charge_id' => $gateway_result['charge_id'],
                            'gateway' => $gateway_result['gateway'] ?? 'asaas',
                            'billing_group_id' => $billing_group_id,
                            'client_id' => $data['client_id'],
                            'value' => floatval($charge_data['amount']),
                            'due_date' => $charge_data['due_date'],
                            'billing_type' => $charge_data['billing_type'],
                            'status' => 'pending',
                            'invoice_url' => $gateway_result['invoice_url'] ?? null,
                            'barcode' => $gateway_result['barcode'] ?? null,
                            'pix_code' => $gateway_result['pix_code'] ?? null,
                            'description' => 'Cobrança via ChargeManager - Billing Group #' . $billing_group_id
                        ];

                        $local_charge_id = $this->chargemanager_charges_model->create($local_charge_data);
                        
                        if ($local_charge_id) {
                            $charges_created[] = [
                                'gateway_id' => $gateway_result['charge_id'],
                                'local_id' => $local_charge_id,
                                'charge_number' => $charge_number
                            ];
                        } else {
                            $charges_failed[] = sprintf(_l('chargemanager_error_saving_charge_to_db_number'), $charge_number);
                        }
                    } else {
                        $charges_failed[] = sprintf(_l('chargemanager_error_creating_charge_number'), $charge_number, $gateway_result['message']);
                    }
                } catch (Exception $charge_exception) {
                    $charges_failed[] = sprintf(_l('chargemanager_error_charge_exception'), $charge_number, $charge_exception->getMessage());
                }
            }

            // Check if any charges were created successfully
            if (empty($charges_created)) {
                // No charges were created, delete the billing group
                $this->chargemanager_billing_groups_model->delete($billing_group_id);
                $error_msg = _l('chargemanager_error_no_charges_created') . '<br>' . implode('<br>', $charges_failed);
                set_alert('danger', $error_msg);
                echo json_encode([
                    'success' => false,
                    'message' => $error_msg
                ]);
                return;
            }

            // Generate individual invoices for each charge
            $invoices_generated = 0;
            $invoice_errors = [];
            
            try {
                foreach ($charges_created as $charge_info) {
                    $invoice_result = $this->chargemanager_charges_model->generate_individual_invoice($charge_info['local_id']);
                    
                    if ($invoice_result && $invoice_result['success']) {
                        $invoices_generated++;
                        log_activity('ChargeManager: Invoice #' . $invoice_result['invoice_id'] . ' created for charge #' . $charge_info['local_id']);
                    } else {
                        $error_msg = isset($invoice_result['message']) ? $invoice_result['message'] : 'Unknown error';
                        $invoice_errors[] = 'Charge #' . $charge_info['local_id'] . ': ' . $error_msg;
                        log_activity('ChargeManager Warning: Failed to generate invoice for charge #' . $charge_info['local_id'] . ': ' . $error_msg);
                    }
                }
            } catch (Exception $invoice_exception) {
                log_activity('ChargeManager Warning: Exception while generating invoices: ' . $invoice_exception->getMessage());
                $invoice_errors[] = 'Exception: ' . $invoice_exception->getMessage();
            }

            // Prepare success message
            $success_messages = [];
            $success_messages[] = 'Grupo de cobrança #' . $billing_group_id . ' criado com sucesso';
            $success_messages[] = count($charges_created) . ' cobrança(s) criada(s) com sucesso';
            
            if ($invoices_generated > 0) {
                $success_messages[] = sprintf('Invoices criadas: %d de %d', $invoices_generated, count($charges_created));
            }
            
            if (!empty($invoice_errors)) {
                $success_messages[] = 'Erros na criação de invoices:';
                $success_messages = array_merge($success_messages, $invoice_errors);
            }
            
            if (!empty($charges_failed)) {
                $success_messages[] = sprintf(_l('chargemanager_charges_failed_count'), count($charges_failed));
                $success_messages[] = implode('<br>', $charges_failed);
                set_alert('warning', implode('<br>', $success_messages));
            } else {
                $alert_type = !empty($invoice_errors) ? 'warning' : 'success';
                set_alert($alert_type, implode('<br>', $success_messages));
            }

            echo json_encode([
                'success' => true,
                'message' => implode('<br>', $success_messages),
                'billing_group_id' => $billing_group_id,
                'invoices_generated' => $invoices_generated,
                'invoice_errors' => count($invoice_errors),
                'charges_created' => count($charges_created),
                'charges_failed' => count($charges_failed)
            ]);

        } catch (Exception $e) {
            log_activity('ChargeManager Error: ' . $e->getMessage());
            $error_msg = _l('chargemanager_error_unexpected') . ': ' . $e->getMessage();
            set_alert('danger', $error_msg);
            echo json_encode([
                'success' => false,
                'message' => $error_msg
            ]);
        }
    }

    /**
     * View a billing group
     */
    public function view($id)
    {
        if (!has_permission('chargemanager', '', 'view')) {
            access_denied('chargemanager view');
        }

        $billing_group = $this->chargemanager_billing_groups_model->get_with_relationships($id);
        
        if (!$billing_group) {
            show_404();
        }

        $data['billing_group'] = $billing_group;
        $data['title'] = _l('chargemanager_view_billing_group');
        
        $this->load->view('admin/billing_groups/view', $data);
    }

    /**
     * AJAX table for billing groups in client tab
     * Follows Perfex CRM standard using data_tables_init function
     */
    public function ajax_billing_groups_table()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'view')) {
            ajax_access_denied();
        }

        // Definir colunas da tabela (ordem DEVE corresponder à view)
        $aColumns = [
            'bg.id',
            'bg.status',
            'bg.total_amount',
            'bg.created_at'
        ];

        // Coluna de índice para performance
        $sIndexColumn = 'bg.id';

        // Tabela principal
        $sTable = db_prefix() . 'chargemanager_billing_groups bg';

        // JOINs (se necessário)
        $join = [];

        // Filtros WHERE adicionais
        $where = [];

        // Filtro obrigatório por client_id
        $client_id = $this->input->get('client_id');
        if ($client_id) {
            $where[] = 'AND bg.client_id = ' . (int)$client_id;
        } else {
            // Se não tem client_id, retorna vazio
            $where[] = 'AND 1 = 0';
        }

        // Campos adicionais a serem selecionados (não exibidos na tabela)
        $additionalSelect = [];

        // Inicializar DataTable usando função nativa do Perfex
        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);

        $output = $result['output'];
        $rResult = $result['rResult'];

        // Processar cada linha de dados
        foreach ($rResult as $aRow) {
            $row = [];

            // ID com link
            $row[] = '<a href="' . admin_url('chargemanager/billing_groups/view/' . $aRow['id']) . '">#' . $aRow['id'] . '</a>';

            // Status com badge colorido
            $status_class = $this->get_status_class($aRow['status']);
            $row[] = '<span class="label label-' . $status_class . '">' . _l('chargemanager_status_' . $aRow['status']) . '</span>';

            // Total amount formatado
            $row[] = app_format_money($aRow['total_amount'], get_base_currency());

            // Data formatada
            $row[] = _dt($aRow['created_at']);

            // Ações
            $actions = '<div class="row-options">';
            $actions .= '<a href="' . admin_url('chargemanager/billing_groups/view/' . $aRow['id']) . '">' . _l('view') . '</a>';
            $actions .= '</div>';
            $row[] = $actions;

            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Get status class for badge
     */
    private function get_status_class($status)
    {
        switch ($status) {
            case 'open':
                return 'info';
            case 'processing':
                return 'warning';
            case 'completed':
                return 'success';
            case 'cancelled':
                return 'danger';
            default:
                return 'default';
        }
    }
} 