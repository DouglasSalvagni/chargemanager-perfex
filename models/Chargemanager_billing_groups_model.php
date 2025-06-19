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
     * Get billing group with all relationships
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
     * Calculate billing group status based on individual charge invoices
     * @param int $billing_group_id
     * @return string
     */
    public function calculate_billing_group_status($billing_group_id)
    {
        if (empty($billing_group_id)) {
            return 'open';
        }

        // Get all charges for this billing group
        $this->load->model('chargemanager_charges_model');
        $charges = $this->chargemanager_charges_model->get_by_billing_group($billing_group_id);

        if (empty($charges)) {
            return 'open';
        }

        $total_charges = count($charges);
        $paid_charges = 0;
        $overdue_charges = 0;
        $cancelled_charges = 0;

        foreach ($charges as $charge) {
            switch ($charge->status) {
                case 'paid':
                    $paid_charges++;
                    break;
                case 'overdue':
                    $overdue_charges++;
                    break;
                case 'cancelled':
                    $cancelled_charges++;
                    break;
            }
        }

        // Determine overall status
        if ($paid_charges == $total_charges) {
            return 'completed'; // All charges paid
        }
        
        if ($cancelled_charges == $total_charges) {
            return 'cancelled'; // All charges cancelled
        }
        
        if ($overdue_charges > 0) {
            return 'overdue'; // At least one charge is overdue
        }
        
        if ($paid_charges > 0) {
            return 'partial'; // Some charges paid, some pending
        }

        return 'open'; // Default status
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
     * Refresh billing group status
     * @param int $billing_group_id
     * @return bool
     */
    public function refresh_status($billing_group_id)
    {
        return $this->update_status($billing_group_id, '', true);
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
} 