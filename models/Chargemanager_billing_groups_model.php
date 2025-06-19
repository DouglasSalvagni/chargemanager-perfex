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

        // Get invoice
        if (!empty($billing_group->invoice_id)) {
            $this->db->where('id', $billing_group->invoice_id);
            $billing_group->invoice = $this->db->get(db_prefix() . 'invoices')->row();
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
     * Calculate billing group status based on charges
     * @param int $billing_group_id
     * @return string
     */
    public function calculate_billing_group_status($billing_group_id)
    {
        // Get charges for billing group
        $this->db->where('billing_group_id', $billing_group_id);
        $charges = $this->db->get(db_prefix() . 'chargemanager_charges')->result();

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

        // Determine status
        if ($paid_charges == $total_charges) {
            return 'paid';
        } elseif ($paid_charges > 0) {
            return 'partial';
        } elseif ($overdue_charges > 0) {
            return 'overdue';
        } elseif ($cancelled_charges == $total_charges) {
            return 'cancelled';
        }

        return 'open';
    }

    /**
     * Get payment summary for billing group
     * @param int $billing_group_id
     * @return array
     */
    public function get_payment_summary($billing_group_id)
    {
        // Get charges
        $this->db->where('billing_group_id', $billing_group_id);
        $charges = $this->db->get(db_prefix() . 'chargemanager_charges')->result();

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
            $summary['total_amount'] += $charge->value;

            switch ($charge->status) {
                case 'paid':
                    $summary['paid_amount'] += $charge->paid_amount ?: $charge->value;
                    $summary['paid_charges']++;
                    break;
                case 'overdue':
                    $summary['overdue_amount'] += $charge->value;
                    $summary['overdue_charges']++;
                    break;
                default:
                    $summary['pending_amount'] += $charge->value;
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
     * Generate invoice for billing group
     * @param int $billing_group_id
     * @param array $options
     * @return array
     */
    public function generate_invoice($billing_group_id, $options = [])
    {
        try {
            // Get billing group with relationships
            $billing_group = $this->get_with_relationships($billing_group_id);

            if (!$billing_group || !$billing_group->client || !$billing_group->contract) {
                throw new Exception(_l('chargemanager_billing_group_incomplete_data'));
            }

            // Check if invoice already exists
            if (!empty($billing_group->invoice_id)) {
                return [
                    'success' => true,
                    'invoice_id' => $billing_group->invoice_id,
                    'message' => _l('chargemanager_invoice_already_exists')
                ];
            }

            // Prepare invoice items based on charges
            $invoice_items = [];
            $order = 1;
            
            if (!empty($billing_group->charges) && is_array($billing_group->charges)) {
                foreach ($billing_group->charges as $charge) {
                    $invoice_items[] = [
                        'description' => sprintf(_l('chargemanager_charge_description'), $charge->billing_type ?? 'BOLETO', $charge->due_date ?? date('Y-m-d')),
                        'long_description' => !empty($billing_group->contract->subject) ? $billing_group->contract->subject : '',
                        'qty' => 1,
                        'rate' => floatval($charge->value ?? 0),
                        'unit' => '',
                        'order' => $order++
                    ];
                }
            }

            // Fallback if no charges found - create one item with total amount
            if (empty($invoice_items)) {
                $invoice_items[] = [
                    'description' => 'Cobrança ChargeManager - Billing Group #' . $billing_group_id,
                    'long_description' => !empty($billing_group->contract->subject) ? $billing_group->contract->subject : '',
                    'qty' => 1,
                    'rate' => floatval($billing_group->total_amount ?? 0),
                    'unit' => '',
                    'order' => 1
                ];
            }

            // Safe access to client properties
            $client = $billing_group->client;
            $billing_street = '';
            $billing_city = '';
            $billing_state = '';
            $billing_zip = '';
            $billing_country = '';

            if (is_object($client)) {
                $billing_street = $client->billing_street ?? $client->address ?? '';
                $billing_city = $client->billing_city ?? $client->city ?? '';
                $billing_state = $client->billing_state ?? $client->state ?? '';
                $billing_zip = $client->billing_zip ?? $client->zip ?? '';
                $billing_country = $client->billing_country ?? $client->country ?? '';
            }

            // Calculate totals
            $subtotal = array_sum(array_column($invoice_items, 'rate'));
            
            // Prepare invoice data with items included - ensure all required fields are set
            $invoice_data = [
                'clientid' => (int)$billing_group->client_id,
                'date' => date('Y-m-d'),
                'duedate' => date('Y-m-d', strtotime('+30 days')),
                'currency' => get_base_currency()->id,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'adjustment' => 0,
                'discount_percent' => 0,
                'discount_total' => 0,
                'discount_type' => '',
                'sale_agent' => get_staff_user_id(),
                'status' => defined('Invoices_model::STATUS_DRAFT') ? Invoices_model::STATUS_DRAFT : 6, // Draft status
                'number_format' => get_option('invoice_number_format'),
                'prefix' => get_option('invoice_prefix'),
                'terms' => $options['terms'] ?? get_option('predefined_terms_invoice'),
                'clientnote' => $options['client_note'] ?? get_option('predefined_clientnote_invoice'),
                'adminnote' => 'Invoice generated by ChargeManager for Billing Group #' . $billing_group_id,
                'billing_street' => $billing_street ?: '',
                'billing_city' => $billing_city ?: '',
                'billing_state' => $billing_state ?: '',
                'billing_zip' => $billing_zip ?: '',
                'billing_country' => $billing_country ?: get_option('invoice_company_country'),
                'shipping_street' => $billing_street ?: '',
                'shipping_city' => $billing_city ?: '',
                'shipping_state' => $billing_state ?: '',
                'shipping_zip' => $billing_zip ?: '',
                'shipping_country' => $billing_country ?: get_option('invoice_company_country'),
                'include_shipping' => 0,
                'show_shipping_on_invoice' => 0,
                'recurring' => 0,
                'cycles' => 0,
                'total_cycles' => 0,
                'is_recurring_from' => null,
                'custom_recurring' => 0,
                'recurring_type' => null,
                'repeat_every_custom' => null,
                'repeat_type_custom' => null,
                'last_recurring_date' => null,
                'cancel_overdue_reminders' => 0,
                'allowed_payment_modes' => serialize([]),
                'token' => app_generate_hash(),
                'newitems' => $invoice_items, // Items are created along with the invoice
                'tags' => '',
                'project_id' => 0
            ];

            // Load invoices model
            $this->load->model('invoices_model');

            // Separate items from invoice data to avoid webhook issues
            $items_to_add = $invoice_data['newitems'];
            unset($invoice_data['newitems']);
            
            // Create invoice without items first
            $invoice_id = $this->invoices_model->add($invoice_data);

            if (!$invoice_id) {
                throw new Exception(_l('chargemanager_error_creating_invoice'));
            }

            // Add items to the created invoice
            if (!empty($items_to_add)) {
                foreach ($items_to_add as $item) {
                    // Prepare item data for database insertion
                    $item_data = [
                        'rel_id' => $invoice_id,
                        'rel_type' => 'invoice',
                        'description' => $item['description'],
                        'long_description' => $item['long_description'] ?? '',
                        'qty' => $item['qty'],
                        'rate' => $item['rate'],
                        'unit' => $item['unit'] ?? '',
                        'item_order' => $item['order'] ?? 1
                    ];

                    $this->db->insert(db_prefix() . 'itemable', $item_data);
                }

                // Update invoice totals after adding items
                $this->invoices_model->update([
                    'subtotal' => $subtotal,
                    'total' => $subtotal
                ], $invoice_id);
            }

            // Update billing group with invoice ID
            $this->update($billing_group_id, ['invoice_id' => $invoice_id]);

            // Update charges with invoice ID
            $this->db->where('billing_group_id', $billing_group_id);
            $this->db->update(db_prefix() . 'chargemanager_charges', [
                'perfex_invoice_id' => $invoice_id,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'invoice_id' => $invoice_id,
                'message' => _l('chargemanager_invoice_created_successfully')
            ];

        } catch (Exception $e) {
            log_activity('ChargeManager Error in generate_invoice: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 