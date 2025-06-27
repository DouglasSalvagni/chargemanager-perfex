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

            // Validate sale_agent if provided
            $sale_agent = null;
            if (!empty($data['sale_agent']) && is_numeric($data['sale_agent'])) {
                $this->load->model('staff_model');
                $staff = $this->staff_model->get($data['sale_agent']);
                if ($staff && $staff->active == 1) {
                    $sale_agent = $data['sale_agent'];
                }
            } else {
                // If no sale_agent provided, try to get the original lead staff
                $original_lead_staff = $this->chargemanager_billing_groups_model->get_client_original_lead_staff($data['client_id']);
                if ($original_lead_staff) {
                    $sale_agent = $original_lead_staff;
                    log_activity('ChargeManager: Auto-assigned sale agent #' . $sale_agent . ' from original lead for client #' . $data['client_id']);
                }
            }

            // Create billing group
            $billing_group_data = [
                'client_id' => $data['client_id'],
                'contract_id' => $data['contract_id'],
                'sale_agent' => $sale_agent,
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
                            'is_entry_charge' => isset($charge_data['is_entry_charge']) ? intval($charge_data['is_entry_charge']) : (($index === 0) ? 1 : 0), // Use frontend value or fallback to index logic
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
                            
                            // Log entry charge assignment for first charge
                            if ($index === 0) {
                                log_activity('ChargeManager: Charge #' . $local_charge_id . ' automatically set as entry charge');
                            }
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

        // Preparar dados das charges
        $charges = !empty($billing_group->charges) ? $billing_group->charges : [];
        
        // Calcular total pago
        $total_paid = 0;
        foreach ($charges as $charge) {
            if ($charge->status === 'received' || $charge->status === 'paid') {
                $total_paid += floatval($charge->paid_amount ?: $charge->value);
            }
        }
        
        // Preparar dados dos contratos (convertendo o objeto único em array para compatibilidade com a view)
        $contracts = [];
        if (!empty($billing_group->contract)) {
            $contracts = [$billing_group->contract];
        }
        
        // Preparar dados dos invoices
        $invoices = !empty($billing_group->invoices) ? $billing_group->invoices : [];
        
        // Get staff list for potential reassignment
        $this->load->model('staff_model');
        $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
        
        // Buscar activity log se necessário (opcional por enquanto)
        $activity_log = [];
        $this->db->where('perfex_entity_type', 'billing_group');
        $this->db->where('perfex_entity_id', $id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(20);
        $activity_log = $this->db->get(db_prefix() . 'chargemanager_sync_logs')->result();

        $data['billing_group'] = $billing_group;
        $data['charges'] = $charges;
        $data['total_paid'] = $total_paid;
        $data['contracts'] = $contracts;
        $data['invoices'] = $invoices;
        $data['activity_log'] = $activity_log;
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

        // Controle de permissões baseado em staff para usuários não administradores
        if (!is_admin()) {
            $current_staff_id = get_staff_user_id();
            
            // Permitir ver billing groups onde:
            // 1. O usuário é o sale_agent
            // 2. Não há sale_agent definido (NULL ou 0) - para compatibilidade com registros antigos
            $where[] = 'AND (bg.sale_agent = ' . (int)$current_staff_id . ' OR bg.sale_agent IS NULL OR bg.sale_agent = 0)';
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
        $status_config = $this->chargemanager_billing_groups_model->get_status_config($status);
        // Convert label-* classes to table classes
        $class_map = [
            'label-success' => 'success',
            'label-info' => 'info', 
            'label-warning' => 'warning',
            'label-danger' => 'danger',
            'label-default' => 'default'
        ];
        
        return $class_map[$status_config['class']] ?? 'default';
    }

    /**
     * Edit billing group page
     */
    public function edit($id)
    {
        if (!has_permission('chargemanager', '', 'edit')) {
            access_denied('chargemanager edit');
        }

        $billing_group = $this->chargemanager_billing_groups_model->get_with_relationships($id);
        
        if (!$billing_group) {
            show_404();
        }

        // Check if billing group can be edited using new status logic
        if (!$this->chargemanager_billing_groups_model->can_edit_billing_group($billing_group->status)) {
            $status_config = $this->chargemanager_billing_groups_model->get_status_config($billing_group->status);
            $message = $billing_group->status === 'completed_exact' ? 
                'Billing group está completo e não pode ser editado. Todas cobranças pagas com valor exato.' :
                'Billing group cancelado não pode ser editado.';
            set_alert('warning', $message);
            redirect(admin_url('chargemanager/billing_groups/view/' . $id));
            return;
        }

        // Get client data
        $this->load->model('clients_model');
        $client = $this->clients_model->get($billing_group->client_id);
        
        // Get contract data
        $contract = null;
        if ($billing_group->contract_id) {
            $this->db->where('id', $billing_group->contract_id);
            $contract = $this->db->get(db_prefix() . 'contracts')->row();
        }

        // Prepare charges data
        $charges = !empty($billing_group->charges) ? $billing_group->charges : [];
        
        // Calculate totals
        $total_paid = 0;
        $pending_charges = [];
        foreach ($charges as $charge) {
            if ($charge->status === 'received' || $charge->status === 'paid') {
                $total_paid += floatval($charge->paid_amount ?: $charge->value);
            } else {
                $pending_charges[] = $charge;
            }
        }

        // Get staff members for sale agent dropdown
        $this->load->model('staff_model');
        $staff_members = $this->staff_model->get('', ['active' => 1]);

        $data = [
            'billing_group' => $billing_group,
            'client' => $client,
            'contract' => $contract,
            'charges' => $charges,
            'pending_charges' => $pending_charges,
            'total_paid' => $total_paid,
            'staff_members' => $staff_members,
            'title' => _l('chargemanager_edit_billing_group')
        ];
        
        $this->load->view('admin/billing_groups/edit', $data);
    }

    /**
     * Update billing group basic information
     */
    public function update($id)
    {
        if (!has_permission('chargemanager', '', 'edit')) {
            access_denied('chargemanager edit');
        }

        // Only admins can update basic information
        if (!is_admin()) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['success' => false, 'message' => _l('access_denied')]);
                return;
            }
            access_denied('admin required');
        }

        if (!$this->input->is_ajax_request() && $this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect(admin_url('chargemanager/billing_groups/view/' . $id));
        }

        $billing_group = $this->chargemanager_billing_groups_model->get($id);
        
        if (!$billing_group) {
            if ($this->input->is_ajax_request()) {
                echo json_encode(['success' => false, 'message' => 'Billing group not found']);
                return;
            }
            show_404();
        }

        $data = $this->input->post();
        $update_data = [];

        try {
            // Validate and update sale_agent
            if (isset($data['sale_agent'])) {
                if (empty($data['sale_agent'])) {
                    $update_data['sale_agent'] = null;
                } elseif (is_numeric($data['sale_agent'])) {
                    $this->load->model('staff_model');
                    $staff = $this->staff_model->get($data['sale_agent']);
                    if ($staff && $staff->active == 1) {
                        $update_data['sale_agent'] = $data['sale_agent'];
                    } else {
                        throw new Exception('Invalid sale agent selected');
                    }
                }
            }

            // Validate and update status
            if (isset($data['status'])) {
                $allowed_statuses = [
                    'open', 'incomplete', 'cancelled',
                    'partial_on_track', 'partial_over', 'partial_under',
                    'overdue_on_track', 'overdue_over', 'overdue_under',
                    'completed_exact', 'completed_over', 'completed_under',
                    // Legacy statuses for backwards compatibility
                    'partial', 'completed', 'overdue'
                ];
                if (in_array($data['status'], $allowed_statuses)) {
                    $update_data['status'] = $data['status'];
                } else {
                    throw new Exception('Invalid status selected');
                }
            }

            if (empty($update_data)) {
                throw new Exception('No valid data to update');
            }

            // Update billing group
            $updated = $this->chargemanager_billing_groups_model->update($id, $update_data);

            if (!$updated) {
                throw new Exception('Failed to update billing group');
            }

            log_activity('ChargeManager: Billing group #' . $id . ' basic information updated');

            if ($this->input->is_ajax_request()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Billing group updated successfully'
                ]);
            } else {
                set_alert('success', 'Billing group updated successfully');
                redirect(admin_url('chargemanager/billing_groups/view/' . $id));
            }

        } catch (Exception $e) {
            log_activity('ChargeManager Error updating billing group: ' . $e->getMessage());
            
            if ($this->input->is_ajax_request()) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            } else {
                set_alert('danger', $e->getMessage());
                redirect(admin_url('chargemanager/billing_groups/edit/' . $id));
            }
        }
    }

    /**
     * Add new charge to billing group via AJAX
     */
    public function add_charge()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'create')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        $billing_group_id = $this->input->post('billing_group_id');
        $charge_data = $this->input->post();

        try {
            // Validate billing group
            $billing_group = $this->chargemanager_billing_groups_model->get($billing_group_id);
            if (!$billing_group) {
                throw new Exception('Billing group not found');
            }

            // Check if billing group can be edited using new status logic
            if (!$this->chargemanager_billing_groups_model->can_edit_billing_group($billing_group->status)) {
                throw new Exception('Cannot add charges to this billing group due to its current status: ' . $billing_group->status);
            }

            // Validate charge data
            $validation_errors = [];
            
            if (empty($charge_data['value']) || !is_numeric($charge_data['value']) || floatval($charge_data['value']) <= 0) {
                $validation_errors[] = _l('chargemanager_error_invalid_amount');
            }
            
            if (empty($charge_data['due_date'])) {
                $validation_errors[] = _l('chargemanager_error_due_date_required');
            } elseif ($charge_data['due_date'] < date('Y-m-d')) {
                $validation_errors[] = _l('chargemanager_error_due_date_past');
            }
            
            if (empty($charge_data['billing_type']) || !in_array($charge_data['billing_type'], ['BOLETO', 'PIX', 'CREDIT_CARD'])) {
                $validation_errors[] = _l('chargemanager_error_invalid_billing_type');
            }

            if (!empty($validation_errors)) {
                throw new Exception(implode('<br>', $validation_errors));
            }

            // Load Gateway Manager
            $this->load->library('chargemanager/Gateway_manager');

            // Create charge in gateway
            $gateway_charge_data = [
                'billing_group_id' => $billing_group_id,
                'client_id' => $billing_group->client_id,
                'value' => floatval($charge_data['value']),
                'due_date' => $charge_data['due_date'],
                'billing_type' => $charge_data['billing_type'],
                'description' => $charge_data['description'] ?: 'Nova cobrança - Billing Group #' . $billing_group_id,
                'gateway' => 'asaas'
            ];
            
            $gateway_result = $this->gateway_manager->create_charge($gateway_charge_data);
            
            if (!$gateway_result['success']) {
                throw new Exception('Erro ao criar cobrança no gateway: ' . $gateway_result['message']);
            }

            // Save charge to local database
            $local_charge_data = [
                'gateway_charge_id' => $gateway_result['charge_id'],
                'gateway' => 'asaas',
                'billing_group_id' => $billing_group_id,
                'client_id' => $billing_group->client_id,
                'value' => floatval($charge_data['value']),
                'due_date' => $charge_data['due_date'],
                'billing_type' => $charge_data['billing_type'],
                'status' => 'pending',
                'is_entry_charge' => isset($charge_data['is_entry_charge']) ? intval($charge_data['is_entry_charge']) : 0, // New charges are not entry charges by default
                'invoice_url' => $gateway_result['invoice_url'] ?? null,
                'barcode' => $gateway_result['barcode'] ?? null,
                'pix_code' => $gateway_result['pix_code'] ?? null,
                'description' => 'Cobrança via ChargeManager - Billing Group #' . $billing_group_id
            ];

            $local_charge_id = $this->chargemanager_charges_model->create($local_charge_data);
            
            if (!$local_charge_id) {
                throw new Exception('Erro ao salvar cobrança no banco de dados');
            }

            // Generate individual invoice
            $invoice_result = $this->chargemanager_charges_model->generate_individual_invoice($local_charge_id);
            
            if (!$invoice_result['success']) {
                log_activity('ChargeManager Warning: Failed to generate invoice for new charge #' . $local_charge_id . ': ' . $invoice_result['message']);
            }

            // Update billing group total amount
            $this->db->set('total_amount', 'total_amount + ' . floatval($charge_data['value']), false);
            $this->db->set('updated_at', date('Y-m-d H:i:s'));
            $this->db->where('id', $billing_group_id);
            $this->db->update(db_prefix() . 'chargemanager_billing_groups');

            // Refresh billing group status
            $this->chargemanager_billing_groups_model->refresh_status($billing_group_id);

            echo json_encode([
                'success' => true,
                'message' => 'Cobrança adicionada com sucesso',
                'charge_id' => $local_charge_id,
                'invoice_generated' => $invoice_result['success'] ?? false
            ]);

        } catch (Exception $e) {
            log_activity('ChargeManager Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Edit existing charge via AJAX
     */
    public function edit_charge()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'edit')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        $charge_id = $this->input->post('charge_id');
        $charge_data = $this->input->post();

        try {
            // Get charge
            $charge = $this->chargemanager_charges_model->get($charge_id);
            if (!$charge) {
                throw new Exception('Charge not found');
            }

            // Check if charge can be safely deleted
            $deletion_check = $this->chargemanager_billing_groups_model->can_delete_charge($charge_id);
            if (!$deletion_check['can_delete']) {
                throw new Exception($deletion_check['reason'] . '. ' . ($deletion_check['suggestion'] ?? ''));
            }

            // Check if charge can be edited
            if (in_array($charge->status, ['paid', 'received', 'cancelled'])) {
                throw new Exception('Cannot edit charge with status: ' . $charge->status);
            }

            // Validate charge data
            $validation_errors = [];
            
            if (isset($charge_data['value']) && (!is_numeric($charge_data['value']) || floatval($charge_data['value']) <= 0)) {
                $validation_errors[] = _l('chargemanager_error_invalid_amount');
            }
            
            if (isset($charge_data['due_date']) && empty($charge_data['due_date'])) {
                $validation_errors[] = _l('chargemanager_error_due_date_required');
            }
            
            if (isset($charge_data['billing_type']) && !in_array($charge_data['billing_type'], ['BOLETO', 'PIX', 'CREDIT_CARD'])) {
                $validation_errors[] = _l('chargemanager_error_invalid_billing_type');
            }

            if (!empty($validation_errors)) {
                throw new Exception(implode('<br>', $validation_errors));
            }

            // Prepare update data
            $update_data = [];
            $gateway_update_data = [];
            
            if (isset($charge_data['value']) && $charge_data['value'] != $charge->value) {
                $update_data['value'] = floatval($charge_data['value']);
                $gateway_update_data['value'] = floatval($charge_data['value']);
            }
            
            if (isset($charge_data['due_date']) && $charge_data['due_date'] != $charge->due_date) {
                $update_data['due_date'] = $charge_data['due_date'];
                $gateway_update_data['dueDate'] = $charge_data['due_date'];
            }
            
            if (isset($charge_data['billing_type']) && $charge_data['billing_type'] != $charge->billing_type) {
                $update_data['billing_type'] = $charge_data['billing_type'];
                $gateway_update_data['billingType'] = $charge_data['billing_type'];
            }

            if (isset($charge_data['description'])) {
                $update_data['description'] = $charge_data['description'];
                $gateway_update_data['description'] = $charge_data['description'];
            }

            if (empty($update_data)) {
                throw new Exception('Nenhuma alteração detectada');
            }

            // Update charge in gateway first
            if (!empty($gateway_update_data)) {
                // Load Gateway Factory directly
                $gateway_factory_path = FCPATH . 'modules/chargemanager/libraries/payment_gateways/Gateway_factory.php';
                if (file_exists($gateway_factory_path)) {
                    require_once $gateway_factory_path;
                    $gateway_instance = Gateway_factory::create('asaas');
                    
                    if ($gateway_instance) {
                        $gateway_result = $gateway_instance->update_charge($charge->gateway_charge_id, $gateway_update_data);
                        
                        if (!$gateway_result['success']) {
                            throw new Exception('Erro ao atualizar cobrança no gateway: ' . $gateway_result['message']);
                        }
                    }
                }
            }

            // Update charge locally using the model's enhanced update method
            $options = ['allow_past_due_date' => true]; // Allow past due dates in editing
            $result = $this->chargemanager_charges_model->update($charge_id, $update_data, $options);

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            // Update billing group total if value changed
            if (isset($update_data['value'])) {
                $value_difference = $update_data['value'] - $charge->value;
                $this->db->set('total_amount', 'total_amount + ' . $value_difference, false);
                $this->db->set('updated_at', date('Y-m-d H:i:s'));
                $this->db->where('id', $charge->billing_group_id);
                $this->db->update(db_prefix() . 'chargemanager_billing_groups');
            }

            // Refresh billing group status
            if ($charge->billing_group_id) {
                log_activity('ChargeManager Debug: Refreshing billing group status');
                
                try {
                    $this->chargemanager_billing_groups_model->refresh_status($charge->billing_group_id);
                    log_activity('ChargeManager Debug: Billing group status refreshed successfully');
                } catch (Exception $status_exception) {
                    // Check if it's the dateupdated error and log it specifically
                    if (strpos($status_exception->getMessage(), 'dateupdated') !== false) {
                        log_activity('ChargeManager Warning: dateupdated field error during status refresh - this is likely from an external hook/trigger: ' . $status_exception->getMessage());
                    } else {
                        log_activity('ChargeManager Warning: Failed to refresh billing group status: ' . $status_exception->getMessage());
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Cobrança atualizada com sucesso',
                'updated_fields' => array_keys($update_data),
                'invoice_updated' => $result['invoice_updated'] ?? false
            ]);

        } catch (Exception $e) {
            log_activity('ChargeManager Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete charge via AJAX
     */
    public function delete_charge()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'delete')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        $charge_id = $this->input->post('charge_id');

        try {
            // Get charge
            $charge = $this->chargemanager_charges_model->get($charge_id);
            if (!$charge) {
                throw new Exception('Charge not found');
            }

            // Check if charge can be deleted
            if (in_array($charge->status, ['paid', 'received'])) {
                throw new Exception('Cannot delete paid charge');
            }

            // Cancel charge in gateway first
            if (!empty($charge->gateway_charge_id)) {
                // Load Gateway Factory directly
                $gateway_factory_path = FCPATH . 'modules/chargemanager/libraries/payment_gateways/Gateway_factory.php';
                if (file_exists($gateway_factory_path)) {
                    require_once $gateway_factory_path;
                    $gateway_instance = Gateway_factory::create('asaas');
                    
                    if ($gateway_instance) {
                        $gateway_result = $gateway_instance->cancel_charge($charge->gateway_charge_id);
                        
                        if (!$gateway_result['success']) {
                            log_activity('ChargeManager Warning: Failed to cancel charge in gateway: ' . $gateway_result['message']);
                        }
                    }
                }
            }

            // Cancel related invoice if exists
            if (!empty($charge->perfex_invoice_id)) {
                try {
                    $this->load->model('invoices_model');
                    $invoice = $this->invoices_model->get($charge->perfex_invoice_id);
                    
                    if ($invoice && $invoice->status != 2) { // Not paid
                        // Mark invoice as cancelled using the correct field name
                        $this->db->where('id', $charge->perfex_invoice_id);
                        $invoice_updated = $this->db->update(db_prefix() . 'invoices', [
                            'status' => 5 // Cancelled status
                        ]);
                        
                        if (!$invoice_updated) {
                            log_activity('ChargeManager Warning: Failed to cancel invoice #' . $charge->perfex_invoice_id . ' for charge #' . $charge_id);
                        }
                    }
                } catch (Exception $invoice_exception) {
                    // Log invoice cancellation error but don't fail the charge cancellation
                    log_activity('ChargeManager Warning: Error cancelling invoice #' . $charge->perfex_invoice_id . ': ' . $invoice_exception->getMessage());
                }
            }

            // Update charge status to cancelled
            $this->db->where('id', $charge_id);
            $charge_updated = $this->db->update(db_prefix() . 'chargemanager_charges', [
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$charge_updated) {
                throw new Exception('Failed to update charge status to cancelled');
            }

            // Update billing group total amount
            $this->db->set('total_amount', 'total_amount - ' . floatval($charge->value), false);
            $this->db->set('updated_at', date('Y-m-d H:i:s'));
            $this->db->where('id', $charge->billing_group_id);
            $billing_group_updated = $this->db->update(db_prefix() . 'chargemanager_billing_groups');

            if (!$billing_group_updated) {
                log_activity('ChargeManager Warning: Failed to update billing group total for group #' . $charge->billing_group_id);
            }

            log_activity('ChargeManager: Charge #' . $charge_id . ' cancelled successfully');

            // Success response - separate from refresh_status to ensure success even if refresh fails
            $response = [
                'success' => true,
                'message' => 'Cobrança cancelada com sucesso'
            ];

            // Try to refresh billing group status as a final step
            // Don't let errors here affect the main operation
            if ($charge->billing_group_id) {
                try {
                    $this->chargemanager_billing_groups_model->refresh_status($charge->billing_group_id);
                } catch (Exception $status_exception) {
                    // Check if it's the dateupdated error and log it specifically
                    if (strpos($status_exception->getMessage(), 'dateupdated') !== false) {
                        log_activity('ChargeManager Warning: dateupdated field error during status refresh - this is likely from an external hook/trigger: ' . $status_exception->getMessage());
                    } else {
                        log_activity('ChargeManager Warning: Failed to refresh billing group status: ' . $status_exception->getMessage());
                    }
                }
            }

            echo json_encode($response);

        } catch (Exception $e) {
            // Special handling for the dateupdated error - log but don't fail if the main operation succeeded
            if (strpos($e->getMessage(), 'dateupdated') !== false) {
                log_activity('ChargeManager Warning: dateupdated field error caught - this appears to be from an external hook/trigger. The charge cancellation was likely successful despite this error: ' . $e->getMessage());
                
                // Check if the charge was actually cancelled
                $updated_charge = $this->chargemanager_charges_model->get($charge_id);
                if ($updated_charge && $updated_charge->status == 'cancelled') {
                    log_activity('ChargeManager: Charge #' . $charge_id . ' was successfully cancelled despite dateupdated error');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Cobrança cancelada com sucesso (warning: erro externo ignorado)'
                    ]);
                    return;
                }
            }
            
            log_activity('ChargeManager Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get active staff members via AJAX
     */
    public function get_staff()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        if (!has_permission('chargemanager', '', 'create')) {
            ajax_access_denied();
        }
        
        $this->load->model('staff_model');
        $this->db->where('active', 1);
        $this->db->order_by('firstname, lastname', 'ASC');
        $staff = $this->db->get(db_prefix() . 'staff')->result();
        
        echo json_encode($staff);
    }

    /**
     * Set a charge as entry charge via AJAX
     */
    public function set_entry_charge()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'edit')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        $charge_id = $this->input->post('charge_id');

        try {
            if (empty($charge_id)) {
                throw new Exception('Charge ID is required');
            }

            $result = $this->chargemanager_charges_model->set_as_entry_charge($charge_id);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cobrança definida como entrada com sucesso'
                ]);
            } else {
                throw new Exception('Failed to set charge as entry charge');
            }

        } catch (Exception $e) {
            log_activity('ChargeManager Error setting entry charge: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get entry charge for a billing group via AJAX
     */
    public function get_entry_charge()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'view')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        $billing_group_id = $this->input->post('billing_group_id');

        try {
            if (empty($billing_group_id)) {
                throw new Exception('Billing group ID is required');
            }

            $entry_charge = $this->chargemanager_charges_model->get_entry_charge($billing_group_id);

            echo json_encode([
                'success' => true,
                'entry_charge' => $entry_charge
            ]);

        } catch (Exception $e) {
            log_activity('ChargeManager Error getting entry charge: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
} 