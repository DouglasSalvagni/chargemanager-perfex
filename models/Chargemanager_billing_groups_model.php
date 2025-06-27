<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Chargemanager_billing_groups_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create a new billing group
     * @param array $data
     * @return int|false
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->insert(db_prefix() . 'chargemanager_billing_groups', $data)) {
            return $this->db->insert_id();
        }
        
        return false;
    }

    /**
     * Get a billing group by ID
     * @param int $id
     * @return object|null
     */
    public function get($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'chargemanager_billing_groups')->row();
    }

    /**
     * Update a billing group
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update(db_prefix() . 'chargemanager_billing_groups', $data);
    }

    /**
     * Delete a billing group
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete(db_prefix() . 'chargemanager_billing_groups');
    }

    /**
     * Get all billing groups
     * @param array $where
     * @return array
     */
    public function get_all($where = [])
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get(db_prefix() . 'chargemanager_billing_groups')->result();
    }

    /**
     * Get billing groups count
     * @param array $where
     * @return int
     */
    public function count($where = [])
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        return $this->db->count_all_results(db_prefix() . 'chargemanager_billing_groups');
    }

    /**
     * Get all billing groups for a specific client
     * @param int $client_id
     * @param array $additional_where
     * @return array
     */
    public function get_by_client($client_id, $additional_where = [])
    {
        if (empty($client_id)) {
            return [];
        }

        $this->db->where('client_id', $client_id);
        
        if (!empty($additional_where)) {
            $this->db->where($additional_where);
        }
        
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get(db_prefix() . 'chargemanager_billing_groups')->result();
    }

    /**
     * Get a billing group with its associated charges
     * @param int $billing_group_id
     * @return object|null
     */
    public function get_with_charges($billing_group_id)
    {
        if (empty($billing_group_id)) {
            return null;
        }

        // Get the billing group first
        $billing_group = $this->get($billing_group_id);
        
        if (!$billing_group) {
            return null;
        }

        // Get associated charges
        $this->db->where('billing_group_id', $billing_group_id);
        $this->db->order_by('created_at', 'ASC');
        $charges = $this->db->get(db_prefix() . 'chargemanager_charges')->result();

        // Add charges to billing group object
        $billing_group->charges = $charges ?: [];
        
        return $billing_group;
    }

    /**
     * Get billing group with all relationships including sale agent
     * @param int $billing_group_id
     * @return object|null
     */
    public function get_with_relationships($billing_group_id)
    {
        if (empty($billing_group_id)) {
            return null;
        }

        $billing_group = $this->get_with_charges($billing_group_id);
        
        if (!$billing_group) {
            return null;
        }

        // Get contract
        if (!empty($billing_group->contract_id)) {
            $this->db->where('id', $billing_group->contract_id);
            $billing_group->contract = $this->db->get(db_prefix() . 'contracts')->row();
        }

        // Get client
        if (!empty($billing_group->client_id)) {
            $this->db->where('userid', $billing_group->client_id);
            $billing_group->client = $this->db->get(db_prefix() . 'clients')->row();
        }

        // Get sale agent
        if (!empty($billing_group->sale_agent)) {
            $this->load->model('staff_model');
            $billing_group->sale_agent_info = $this->staff_model->get($billing_group->sale_agent);
        }

        // Get invoices for charges (new logic - multiple invoices per billing group)
        $billing_group->invoices = [];
        if (!empty($billing_group->charges)) {
            foreach ($billing_group->charges as $charge) {
                if (!empty($charge->perfex_invoice_id)) {
                    $this->db->where('id', $charge->perfex_invoice_id);
                    $invoice = $this->db->get(db_prefix() . 'invoices')->row();
                    if ($invoice) {
                        $billing_group->invoices[] = $invoice;
                    }
                }
            }
        }

        return $billing_group;
    }

    /**
     * Validate contract status for billing group creation
     * @param int $contract_id
     * @return array
     */
    public function validate_contract_status($contract_id)
    {
        if (empty($contract_id)) {
            return [
                'success' => false,
                'message' => _l('chargemanager_contract_id_required')
            ];
        }

        // Get contract
        $this->db->where('id', $contract_id);
        $contract = $this->db->get(db_prefix() . 'contracts')->row();

        if (!$contract) {
            return [
                'success' => false,
                'message' => _l('chargemanager_contract_not_found')
            ];
        }

        // Check if signed
        $is_signed = $contract->signed == 1 || $contract->marked_as_signed == 1;
        if (!$is_signed) {
            return [
                'success' => false,
                'message' => _l('chargemanager_contract_not_signed')
            ];
        }

        // Check if expired
        if (!empty($contract->dateend) && $contract->dateend < date('Y-m-d')) {
            return [
                'success' => false,
                'message' => _l('chargemanager_contract_expired')
            ];
        }

        // Check if has value
        if ($contract->contract_value <= 0) {
            return [
                'success' => false,
                'message' => _l('chargemanager_contract_no_value')
            ];
        }

        // Check if already has billing group
        $this->db->where('contract_id', $contract_id);
        $existing = $this->db->get(db_prefix() . 'chargemanager_billing_groups')->row();

        if ($existing) {
            return [
                'success' => false,
                'message' => _l('chargemanager_contract_already_has_billing_group')
            ];
        }

        return [
            'success' => true,
            'contract' => $contract
        ];
    }

    /**
     * Validate charges total against contract value
     * @param int $contract_id
     * @param array $charges
     * @param float $tolerance_percentage
     * @return array
     */
    public function validate_charges_total($contract_id, $charges = [], $tolerance_percentage = 0.01)
    {
        if (empty($contract_id) || empty($charges)) {
            return [
                'success' => false,
                'message' => _l('chargemanager_invalid_data')
            ];
        }

        // Get contract
        $this->db->where('id', $contract_id);
        $contract = $this->db->get(db_prefix() . 'contracts')->row();

        if (!$contract) {
            return [
                'success' => false,
                'message' => _l('chargemanager_contract_not_found')
            ];
        }

        $contract_value = (float) $contract->contract_value;
        $charges_total = array_sum(array_column($charges, 'value'));

        // Calculate tolerance
        $tolerance = $contract_value * $tolerance_percentage;
        $min_acceptable = $contract_value - $tolerance;
        $max_acceptable = $contract_value + $tolerance;

        if ($charges_total < $min_acceptable || $charges_total > $max_acceptable) {
            return [
                'success' => false,
                'message' => _l('chargemanager_charges_total_mismatch'),
                'details' => [
                    'contract_value' => $contract_value,
                    'charges_total' => $charges_total,
                    'difference' => abs($contract_value - $charges_total)
                ]
            ];
        }

        return [
            'success' => true,
            'contract_value' => $contract_value,
            'charges_total' => $charges_total
        ];
    }

    /**
     * Update billing group status
     * @param int $billing_group_id
     * @param string $status
     * @param bool $auto_calculate
     * @return bool
     */
    public function update_status($billing_group_id, $status, $auto_calculate = false)
    {
        if ($auto_calculate) {
            $status = $this->calculate_billing_group_status($billing_group_id);
        }

        return $this->update($billing_group_id, ['status' => $status]);
    }

    /**
     * Calculate billing group status with enhanced business logic
     * @param int $billing_group_id
     * @return string
     */
    public function calculate_billing_group_status($billing_group_id)
    {
        if (empty($billing_group_id)) {
            return 'open';
        }

        // Get billing group and contract data
        $billing_group = $this->get($billing_group_id);
        if (!$billing_group) {
            return 'open';
        }

        $this->db->where('id', $billing_group->contract_id);
        $contract = $this->db->get(db_prefix() . 'contracts')->row();
        $contract_value = $contract ? floatval($contract->contract_value) : 0;

        // Get charges
        $this->load->model('chargemanager_charges_model');
        $charges = $this->chargemanager_charges_model->get_by_billing_group($billing_group_id);

        if (empty($charges)) {
            return 'incomplete';
        }

        // Calculate charge statistics (EXCLUDING cancelled charges)
        $active_charges = array_filter($charges, function($charge) {
            return $charge->status !== 'cancelled';
        });

        if (empty($active_charges)) {
            return 'cancelled'; // Only when ALL charges are cancelled
        }

        $total_active = count($active_charges);
        $paid_charges = 0;
        $overdue_charges = 0;
        $pending_charges = 0;
        $active_value = 0;
        $paid_value = 0;

        foreach ($active_charges as $charge) {
            $charge_value = floatval($charge->value);
            $active_value += $charge_value;

            switch ($charge->status) {
                case 'paid':
                case 'received':
                    $paid_charges++;
                    $paid_value += $charge_value;
                    break;
                case 'overdue':
                    $overdue_charges++;
                    break;
                default:
                    $pending_charges++;
                    break;
            }
        }

        // Determine value relationship with contract
        $tolerance = 0.01;
        $value_comparison = 'exact';
        
        if ($active_value > $contract_value + $tolerance) {
            $value_comparison = 'over';
        } elseif ($active_value < $contract_value - $tolerance) {
            $value_comparison = 'under';
        }

        // Apply status logic with value consideration
        if ($paid_charges == $total_active) {
            // All active charges paid
            switch ($value_comparison) {
                case 'over':
                    return 'completed_over';
                case 'under':
                    return 'completed_under';
                default:
                    return 'completed_exact';
            }
        }

        if ($overdue_charges > 0) {
            // Has overdue charges
            switch ($value_comparison) {
                case 'over':
                    return 'overdue_over';
                case 'under':
                    return 'overdue_under';
                default:
                    return 'overdue_on_track';
            }
        }

        if ($paid_charges > 0) {
            // Partial payments
            switch ($value_comparison) {
                case 'over':
                    return 'partial_over';
                case 'under':
                    return 'partial_under';
                default:
                    return 'partial_on_track';
            }
        }

        // No payments yet
        return $value_comparison === 'under' ? 'incomplete' : 'open';
    }

    /**
     * Get status configuration for display
     * @param string $status
     * @return array
     */
    public function get_status_config($status)
    {
        $configs = [
            // Completed statuses
            'completed_exact' => [
                'label' => 'Concluído',
                'class' => 'label-success',
                'icon' => 'fa-check-circle',
                'editable' => false,
                'description' => 'Todas cobranças pagas, valor exato do contrato'
            ],
            'completed_over' => [
                'label' => 'Concluído (Acima)',
                'class' => 'label-success',
                'icon' => 'fa-arrow-up',
                'editable' => true,
                'description' => 'Todas cobranças pagas, valor acima do contrato'
            ],
            'completed_under' => [
                'label' => 'Concluído (Abaixo)',
                'class' => 'label-warning',
                'icon' => 'fa-arrow-down',
                'editable' => true,
                'description' => 'Todas cobranças pagas, valor abaixo do contrato'
            ],
            
            // Partial statuses
            'partial_on_track' => [
                'label' => 'Parcial',
                'class' => 'label-info',
                'icon' => 'fa-clock-o',
                'editable' => true,
                'description' => 'Algumas cobranças pagas, valor total correto'
            ],
            'partial_over' => [
                'label' => 'Parcial (Acima)',
                'class' => 'label-info',
                'icon' => 'fa-arrow-up',
                'editable' => true,
                'description' => 'Algumas cobranças pagas, valor total acima do contrato'
            ],
            'partial_under' => [
                'label' => 'Parcial (Incompleto)',
                'class' => 'label-warning',
                'icon' => 'fa-exclamation-triangle',
                'editable' => true,
                'description' => 'Algumas cobranças pagas, faltam cobranças'
            ],
            
            // Problem statuses
            'overdue_on_track' => [
                'label' => 'Vencido',
                'class' => 'label-danger',
                'icon' => 'fa-exclamation-circle',
                'editable' => true,
                'description' => 'Cobranças vencidas, valor total correto'
            ],
            'overdue_over' => [
                'label' => 'Vencido (Acima)',
                'class' => 'label-danger',
                'icon' => 'fa-exclamation-circle',
                'editable' => true,
                'description' => 'Cobranças vencidas, valor acima do contrato'
            ],
            'overdue_under' => [
                'label' => 'Vencido (Incompleto)',
                'class' => 'label-danger',
                'icon' => 'fa-exclamation-circle',
                'editable' => true,
                'description' => 'Cobranças vencidas, faltam cobranças'
            ],
            
            // Basic statuses
            'open' => [
                'label' => 'Aberto',
                'class' => 'label-default',
                'icon' => 'fa-folder-open',
                'editable' => true,
                'description' => 'Aguardando pagamentos'
            ],
            'incomplete' => [
                'label' => 'Incompleto',
                'class' => 'label-warning',
                'icon' => 'fa-exclamation-triangle',
                'editable' => true,
                'description' => 'Faltam cobranças para atingir valor do contrato'
            ],
            'cancelled' => [
                'label' => 'Cancelado',
                'class' => 'label-danger',
                'icon' => 'fa-times-circle',
                'editable' => false,
                'description' => 'Todas cobranças canceladas'
            ],
            
            // Legacy statuses (backwards compatibility)
            'completed' => [
                'label' => 'Concluído',
                'class' => 'label-success',
                'icon' => 'fa-check-circle',
                'editable' => false,
                'description' => 'Concluído (status legado)'
            ],
            'partial' => [
                'label' => 'Parcial',
                'class' => 'label-info',
                'icon' => 'fa-clock-o',
                'editable' => true,
                'description' => 'Parcial (status legado)'
            ],
            'overdue' => [
                'label' => 'Vencido',
                'class' => 'label-danger',
                'icon' => 'fa-exclamation-circle',
                'editable' => true,
                'description' => 'Vencido (status legado)'
            ]
        ];

        return $configs[$status] ?? $configs['open'];
    }

    /**
     * Check if billing group can be edited based on status
     * @param string $status
     * @return bool
     */
    public function can_edit_billing_group($status)
    {
        $status_config = $this->get_status_config($status);
        return $status_config['editable'];
    }

    /**
     * Validate billing group completeness before status calculation
     * @param int $billing_group_id
     * @return array
     */
    public function validate_billing_group_completeness($billing_group_id)
    {
        $billing_group = $this->get($billing_group_id);
        if (!$billing_group) {
            return ['valid' => false, 'message' => 'Billing group not found'];
        }

        // Get contract value
        $this->db->where('id', $billing_group->contract_id);
        $contract = $this->db->get(db_prefix() . 'contracts')->row();
        $contract_value = $contract ? floatval($contract->contract_value) : 0;

        // Get active charges value
        $this->load->model('chargemanager_charges_model');
        $charges = $this->chargemanager_charges_model->get_by_billing_group($billing_group_id);
        $active_value = 0;
        
        foreach ($charges as $charge) {
            if ($charge->status !== 'cancelled') {
                $active_value += floatval($charge->value);
            }
        }

        $difference = abs($active_value - $contract_value);
        $tolerance = 0.01;

        return [
            'valid' => $difference <= $tolerance,
            'contract_value' => $contract_value,
            'charges_value' => $active_value,
            'difference' => $difference,
            'message' => $difference > $tolerance ? 
                'Billing group incomplete: charges value differs from contract' : 
                'Billing group is complete'
        ];
    }

    /**
     * Enhanced refresh status with business validations
     * @param int $billing_group_id
     * @return bool
     */
    public function refresh_status($billing_group_id)
    {
        // Calculate new status
        $new_status = $this->calculate_billing_group_status($billing_group_id);
        
        // Additional validation for 'completed_exact' status
        if ($new_status === 'completed_exact') {
            $validation = $this->validate_billing_group_completeness($billing_group_id);
            if (!$validation['valid']) {
                $new_status = 'incomplete'; // Force incomplete status
                log_activity('ChargeManager: Prevented incorrect completed_exact status for billing group #' . $billing_group_id);
            }
        }
        
        return $this->update_status($billing_group_id, $new_status, false);
    }

    /**
     * Get payment summary for billing group based on individual charges/invoices
     * @param int $billing_group_id
     * @return array
     */
    public function get_payment_summary($billing_group_id)
    {
        // Get charges using the charges model
        $this->load->model('chargemanager_charges_model');
        $charges = $this->chargemanager_charges_model->get_by_billing_group($billing_group_id);

        $summary = [
            'total_amount' => 0,
            'paid_amount' => 0,
            'pending_amount' => 0,
            'overdue_amount' => 0,
            'total_charges' => count($charges),
            'paid_charges' => 0,
            'pending_charges' => 0,
            'overdue_charges' => 0
        ];

        foreach ($charges as $charge) {
            $summary['total_amount'] += floatval($charge->value);

            switch ($charge->status) {
                case 'paid':
                    $summary['paid_amount'] += floatval($charge->paid_amount ?: $charge->value);
                    $summary['paid_charges']++;
                    break;
                case 'overdue':
                    $summary['overdue_amount'] += floatval($charge->value);
                    $summary['overdue_charges']++;
                    break;
                default:
                    $summary['pending_amount'] += floatval($charge->value);
                    $summary['pending_charges']++;
                    break;
            }
        }

        return $summary;
    }

    /**
     * Generate individual invoices for all charges in a billing group
     * @param int $billing_group_id
     * @param array $options
     * @return array
     */
    public function generate_invoices_for_charges($billing_group_id, $options = [])
    {
        try {
            // Get all charges for this billing group
            $this->load->model('chargemanager_charges_model');
            $charges = $this->chargemanager_charges_model->get_by_billing_group($billing_group_id);
            
            if (empty($charges)) {
                throw new Exception('No charges found for billing group #' . $billing_group_id);
            }

            $invoices_created = [];
            $errors = [];

            foreach ($charges as $charge) {
                // Skip if invoice already exists
                if (!empty($charge->perfex_invoice_id)) {
                    continue;
                }

                $invoice_result = $this->chargemanager_charges_model->generate_individual_invoice($charge->id, $options);
                
                if ($invoice_result['success']) {
                    $invoices_created[] = [
                        'charge_id' => $charge->id,
                        'invoice_id' => $invoice_result['invoice_id']
                    ];
                } else {
                    $errors[] = 'Charge #' . $charge->id . ': ' . $invoice_result['message'];
                }
            }

            return [
                'success' => count($invoices_created) > 0,
                'invoices_created' => $invoices_created,
                'errors' => $errors,
                'total_invoices' => count($invoices_created),
                'total_errors' => count($errors)
            ];

        } catch (Exception $e) {
            log_activity('ChargeManager Error in generate_invoices_for_charges: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get active staff members for sale agent selection
     * @return array
     */
    public function get_active_staff()
    {
        $this->load->model('staff_model');
        $this->db->where('active', 1);
        $this->db->order_by('firstname, lastname', 'ASC');
        return $this->db->get(db_prefix() . 'staff')->result();
    }

    /**
     * Get the original lead staff assigned to a client
     * @param int $client_id
     * @return int|null Staff ID from the original lead, or null if not found
     */
    public function get_client_original_lead_staff($client_id)
    {
        if (empty($client_id)) {
            return null;
        }

        // First, get the leadid from clients table
        $this->db->select('leadid');
        $this->db->where('userid', $client_id);
        $client = $this->db->get(db_prefix() . 'clients')->row();

        if (!$client || empty($client->leadid)) {
            return null;
        }

        // Get the assigned staff from leads table
        $this->db->select('assigned');
        $this->db->where('id', $client->leadid);
        $lead = $this->db->get(db_prefix() . 'leads')->row();

        if (!$lead || empty($lead->assigned)) {
            return null;
        }

        // Verify that the staff member is still active
        $this->db->where('staffid', $lead->assigned);
        $this->db->where('active', 1);
        $staff = $this->db->get(db_prefix() . 'staff')->row();

        return $staff ? $lead->assigned : null;
    }

    /**
     * Check if charge can be safely deleted
     * @param int $charge_id
     * @return array
     */
    public function can_delete_charge($charge_id)
    {
        $this->load->model('chargemanager_charges_model');
        $charge = $this->chargemanager_charges_model->get($charge_id);
        
        if (!$charge || !$charge->billing_group_id) {
            return ['can_delete' => false, 'reason' => 'Charge not found'];
        }

        // Don't allow deletion if it would make billing group incomplete
        $validation = $this->validate_billing_group_completeness($charge->billing_group_id);
        
        if ($validation['valid']) {
            // If currently complete, check if deletion would break completeness
            $remaining_value = $validation['charges_value'] - floatval($charge->value);
            $difference = abs($remaining_value - $validation['contract_value']);
            
            if ($difference > 0.01) {
                return [
                    'can_delete' => false, 
                    'reason' => 'Deleting this charge would make billing group incomplete',
                    'suggestion' => 'Create a replacement charge first',
                    'current_value' => $validation['charges_value'],
                    'contract_value' => $validation['contract_value'],
                    'charge_value' => floatval($charge->value),
                    'remaining_value' => $remaining_value
                ];
            }
        }

        return ['can_delete' => true];
    }
} 