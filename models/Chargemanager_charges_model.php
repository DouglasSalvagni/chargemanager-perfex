<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Chargemanager_charges_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create a new charge
     * @param array $data
     * @return int|false
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->insert(db_prefix() . 'chargemanager_charges', $data)) {
            return $this->db->insert_id();
        }
        
        return false;
    }

    /**
     * Get a charge by ID
     * @param int $id
     * @return object|null
     */
    public function get($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'chargemanager_charges')->row();
    }

    /**
     * Update a charge with validations and hooks
     * @param int $charge_id
     * @param array $data
     * @param array $options
     * @return array|bool - Returns array with detailed result or bool for simple updates
     */
    public function update($charge_id, $data, $options = [])
    {
        // If options are provided or data contains complex fields, use full update logic
        $complex_fields = ['value', 'due_date', 'billing_type', 'description', 'client_id', 'status'];
        $is_complex_update = !empty($options) || array_intersect(array_keys($data), $complex_fields);
        
        // Simple update for internal use (backward compatibility)
        if (!$is_complex_update) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $charge_id);
            return $this->db->update(db_prefix() . 'chargemanager_charges', $data);
        }

        // Complex update with validations and hooks
        try {
            // Get current charge data
            $current_charge = $this->get($charge_id);
            
            if (!$current_charge) {
                throw new Exception('Charge not found: ' . $charge_id);
            }

            // Check if charge can be edited based on status
            if (in_array($current_charge->status, ['paid', 'cancelled'])) {
                throw new Exception('Cannot edit charge with status: ' . $current_charge->status);
            }

            // Validate required fields if provided
            $allowed_fields = [
                'value', 'due_date', 'billing_type', 'description', 
                'client_id', 'status', 'gateway_charge_id'
            ];

            $update_data = [];
            foreach ($data as $field => $value) {
                if (in_array($field, $allowed_fields)) {
                    $update_data[$field] = $value;
                }
            }

            // Validate specific fields
            if (isset($update_data['value'])) {
                if (!is_numeric($update_data['value']) || $update_data['value'] <= 0) {
                    throw new Exception('Invalid charge value');
                }
                $update_data['value'] = floatval($update_data['value']);
            }

            if (isset($update_data['due_date'])) {
                if (!strtotime($update_data['due_date'])) {
                    throw new Exception('Invalid due date format');
                }
                // Don't allow setting due date in the past unless explicitly allowed
                if (!($options['allow_past_due_date'] ?? false) && strtotime($update_data['due_date']) < strtotime('today')) {
                    throw new Exception('Due date cannot be in the past');
                }
            }

            if (isset($update_data['billing_type'])) {
                $valid_types = ['BOLETO', 'PIX', 'CREDIT_CARD'];
                if (!in_array($update_data['billing_type'], $valid_types)) {
                    throw new Exception('Invalid billing type');
                }
            }

            // Hook: Before charge edit
            if (function_exists('hooks')) {
                $hook_data = [
                    'charge_id' => $charge_id,
                    'current_data' => $current_charge,
                    'update_data' => $update_data,
                    'options' => $options
                ];
                hooks()->do_action('before_chargemanager_charge_edit', $hook_data);
                
                // Allow hooks to modify update data
                $update_data = hooks()->apply_filters('chargemanager_charge_edit_data', $update_data, $current_charge);
            }

            // Perform the database update
            $update_data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $charge_id);
            $updated = $this->db->update(db_prefix() . 'chargemanager_charges', $update_data);

            if (!$updated) {
                throw new Exception('Failed to update charge in database');
            }

            // Get updated charge data
            $updated_charge = $this->get($charge_id);

            // Update related invoice if exists and if relevant fields changed
            $invoice_update_needed = false;
            $invoice_affecting_fields = ['value', 'due_date', 'description', 'billing_type'];
            
            foreach ($invoice_affecting_fields as $field) {
                if (isset($update_data[$field]) && $current_charge->$field != $updated_charge->$field) {
                    $invoice_update_needed = true;
                    break;
                }
            }

            $invoice_update_result = null;
            if ($invoice_update_needed && !empty($updated_charge->perfex_invoice_id)) {
                $invoice_update_result = $this->update_related_invoice($charge_id, $updated_charge, $current_charge);
            }

            // Log activity
            log_activity('ChargeManager: Charge #' . $charge_id . ' updated successfully');

            // Hook: After charge edit - with error suppression for external modules
            if (function_exists('hooks')) {
                try {
                    // Temporarily suppress warnings from external modules
                    $original_error_reporting = error_reporting();
                    error_reporting($original_error_reporting & ~E_WARNING & ~E_DEPRECATED);
                    
                    $hook_data = [
                        'charge_id' => $charge_id,
                        'previous_data' => $current_charge,
                        'current_data' => $updated_charge,
                        'updated_fields' => array_keys($update_data),
                        'invoice_updated' => $invoice_update_result !== null,
                        'invoice_update_result' => $invoice_update_result
                    ];
                    hooks()->do_action('after_chargemanager_charge_edit', $hook_data);
                    
                    // Restore original error reporting
                    error_reporting($original_error_reporting);
                    
                } catch (Exception $hook_exception) {
                    // Log hook errors but don't fail the update
                    log_activity('ChargeManager Warning: Hook error in after_chargemanager_charge_edit: ' . $hook_exception->getMessage());
                    
                    // Restore error reporting in case of exception
                    if (isset($original_error_reporting)) {
                        error_reporting($original_error_reporting);
                    }
                }
            }

            // Update billing group status if applicable
            if (!empty($updated_charge->billing_group_id)) {
                $this->load->model('chargemanager_billing_groups_model');
                $this->chargemanager_billing_groups_model->refresh_status($updated_charge->billing_group_id);
            }

            return [
                'success' => true,
                'message' => 'Charge updated successfully',
                'charge_id' => $charge_id,
                'updated_fields' => array_keys($update_data),
                'invoice_updated' => $invoice_update_result !== null,
                'invoice_update_result' => $invoice_update_result
            ];

        } catch (Exception $e) {
            log_activity('ChargeManager Error in update: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete a charge
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete(db_prefix() . 'chargemanager_charges');
    }

    /**
     * Get all charges
     * @param array $where
     * @return array
     */
    public function get_all($where = [])
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get(db_prefix() . 'chargemanager_charges')->result();
    }

    /**
     * Get charges by billing group
     * @param int $billing_group_id
     * @return array
     */
    public function get_by_billing_group($billing_group_id)
    {
        if (empty($billing_group_id)) {
            return [];
        }

        $this->db->where('billing_group_id', $billing_group_id);
        $this->db->order_by('due_date', 'ASC');
        return $this->db->get(db_prefix() . 'chargemanager_charges')->result();
    }

    /**
     * Get charge by gateway ID
     * @param string $gateway_charge_id
     * @param string $gateway
     * @return object|null
     */
    public function get_by_gateway_id($gateway_charge_id, $gateway = 'asaas')
    {
        $this->db->where('gateway_charge_id', $gateway_charge_id);
        $this->db->where('gateway', $gateway);
        return $this->db->get(db_prefix() . 'chargemanager_charges')->row();
    }

    /**
     * Create multiple charges via gateway
     * @param array $charges_data Array of charge data
     * @return array
     */
    public function create_batch_charges($charges_data)
    {
        try {
            $this->load->library('chargemanager/Gateway_manager');
            
            $created_charges = [];
            $errors = [];

            foreach ($charges_data as $charge_data) {
                try {
                    // Create charge via gateway
                    $gateway_result = $this->gateway_manager->create_charge($charge_data);
                    
                    if ($gateway_result['success']) {
                        // Save charge to database
                        $db_charge_data = [
                            'gateway_charge_id' => $gateway_result['charge_id'],
                            'gateway' => $gateway_result['gateway'] ?? 'asaas',
                            'billing_group_id' => $charge_data['billing_group_id'] ?? null,
                            'client_id' => $charge_data['client_id'],
                            'value' => $charge_data['value'],
                            'due_date' => $charge_data['due_date'],
                            'billing_type' => $charge_data['billing_type'],
                            'status' => 'pending',
                            'invoice_url' => $gateway_result['invoice_url'] ?? null,
                            'barcode' => $gateway_result['barcode'] ?? null,
                            'pix_code' => $gateway_result['pix_code'] ?? null
                        ];

                        $charge_id = $this->create($db_charge_data);
                        
                        if ($charge_id) {
                            $created_charges[] = $charge_id;
                        } else {
                            $errors[] = _l('chargemanager_error_saving_charge_to_db');
                        }
                    } else {
                        $errors[] = $gateway_result['message'];
                    }

                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }

            return [
                'success' => count($created_charges) > 0,
                'created_charges' => $created_charges,
                'errors' => $errors,
                'total_created' => count($created_charges),
                'total_errors' => count($errors)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'created_charges' => [],
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Update charge payment status via webhook
     * @param string $gateway_charge_id
     * @param string $status
     * @param array $payment_data
     * @return bool
     */
    public function update_payment_status($gateway_charge_id, $status, $payment_data = [])
    {
        try {
            // Find charge by gateway ID
            $charge = $this->get_by_gateway_id($gateway_charge_id);
            
            if (!$charge) {
                throw new Exception('Charge not found: ' . $gateway_charge_id);
            }

            // Prepare update data
            $update_data = [
                'status' => $status
            ];

            // Add payment specific data
            if ($status === 'paid' && !empty($payment_data)) {
                $update_data['paid_at'] = date('Y-m-d H:i:s');
                $update_data['paid_amount'] = $payment_data['value'] ?? $charge->value;
                $update_data['payment_method'] = $payment_data['billingType'] ?? null;
            }

            // Update charge
            $updated = $this->update($charge->id, $update_data);

            if ($updated) {
                // Log activity
                log_activity('ChargeManager: Charge #' . $charge->id . ' status updated to ' . $status);
                
                // Update billing group status if applicable
                if (!empty($charge->billing_group_id)) {
                    $this->load->model('chargemanager_billing_groups_model');
                    $this->chargemanager_billing_groups_model->refresh_status($charge->billing_group_id);
                }
                
                return true;
            }

            return false;

        } catch (Exception $e) {
            log_activity('ChargeManager Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Link payment record to charges
     * @param int $payment_id
     * @return bool
     */
    public function link_payment_to_charges($payment_id)
    {
        try {
            // Load payment model
            $this->load->model('payments_model');
            $payment = $this->payments_model->get($payment_id);

            if (!$payment) {
                return false;
            }

            // Find charges for this invoice
            $this->db->where('perfex_invoice_id', $payment->invoiceid);
            $this->db->where('payment_record_id IS NULL');
            $charges = $this->db->get(db_prefix() . 'chargemanager_charges')->result();

            if (empty($charges)) {
                return false;
            }

            // Link payment to charges
            $payment_amount = (float) $payment->amount;
            $linked_amount = 0;

            foreach ($charges as $charge) {
                if ($linked_amount >= $payment_amount) {
                    break;
                }

                $charge_amount = (float) $charge->value;
                $amount_to_link = min($charge_amount, $payment_amount - $linked_amount);

                // Update charge with payment record
                $this->update($charge->id, [
                    'payment_record_id' => $payment_id,
                    'paid_amount' => $amount_to_link,
                    'status' => ($amount_to_link >= $charge_amount) ? 'paid' : 'partial'
                ]);

                $linked_amount += $amount_to_link;
            }

            return true;

        } catch (Exception $e) {
            log_activity('ChargeManager Error linking payment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get charges by client
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
        return $this->db->get(db_prefix() . 'chargemanager_charges')->result();
    }

    /**
     * Get overdue charges
     * @param int $days_overdue
     * @return array
     */
    public function get_overdue_charges($days_overdue = 0)
    {
        $overdue_date = date('Y-m-d', strtotime('-' . $days_overdue . ' days'));
        
        $this->db->where('due_date <', $overdue_date);
        $this->db->where_in('status', ['pending', 'partial']);
        $this->db->order_by('due_date', 'ASC');
        
        return $this->db->get(db_prefix() . 'chargemanager_charges')->result();
    }

    /**
     * Mark charges as overdue
     * @param array $charge_ids
     * @return bool
     */
    public function mark_as_overdue($charge_ids)
    {
        if (empty($charge_ids)) {
            return false;
        }

        $this->db->where_in('id', $charge_ids);
        $this->db->update(db_prefix() . 'chargemanager_charges', [
            'status' => 'overdue',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Get charge statistics for a client
     * @param int $client_id
     * @return array
     */
    public function get_client_statistics($client_id)
    {
        if (empty($client_id)) {
            return [];
        }

        $this->db->select('
            COUNT(*) as total_charges,
            SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_charges,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_charges,
            SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue_charges,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_charges,
            SUM(value) as total_value,
            SUM(CASE WHEN status = "paid" THEN paid_amount ELSE 0 END) as paid_value,
            SUM(CASE WHEN status IN ("pending", "partial") THEN value ELSE 0 END) as pending_value,
            SUM(CASE WHEN status = "overdue" THEN value ELSE 0 END) as overdue_value
        ');
        $this->db->where('client_id', $client_id);
        
        return $this->db->get(db_prefix() . 'chargemanager_charges')->row_array();
    }

    /**
     * Cancel a charge
     * @param int $charge_id
     * @param string $reason
     * @param int $cancelled_by
     * @return array
     */
    public function cancel_charge($charge_id, $reason = '', $cancelled_by = null)
    {
        try {
            $charge = $this->get($charge_id);
            
            if (!$charge) {
                throw new Exception(_l('chargemanager_charge_not_found'));
            }

            if ($charge->status === 'paid') {
                throw new Exception(_l('chargemanager_cannot_cancel_paid_charge'));
            }

            // Cancel in gateway first
            $this->load->library('chargemanager/Gateway_manager');
            $gateway_result = $this->gateway_manager->cancel_charge($charge->gateway_charge_id, $charge->gateway);

            if (!$gateway_result['success']) {
                throw new Exception($gateway_result['message']);
            }

            // Update charge in database
            $update_data = [
                'status' => 'cancelled',
                'cancelled_at' => date('Y-m-d H:i:s'),
                'cancelled_by' => $cancelled_by,
                'cancellation_reason' => $reason
            ];

            $updated = $this->update($charge_id, $update_data);

            if (!$updated) {
                throw new Exception(_l('chargemanager_error_updating_charge'));
            }

            // Update billing group status
            if (!empty($charge->billing_group_id)) {
                $this->load->model('chargemanager_billing_groups_model');
                $this->chargemanager_billing_groups_model->refresh_status($charge->billing_group_id);
            }

            return [
                'success' => true,
                'message' => _l('chargemanager_charge_cancelled_successfully')
            ];

        } catch (Exception $e) {
            log_activity('ChargeManager Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create individual invoice for a charge
     * @param int $charge_id
     * @param array $options
     * @return array
     */
    public function generate_individual_invoice($charge_id, $options = [])
    {
        try {
            // Get charge with billing group and client data
            $charge = $this->get_charge_with_relationships($charge_id);
            
            if (!$charge) {
                throw new Exception('Charge not found: ' . $charge_id);
            }

            // Check if invoice already exists
            if (!empty($charge->perfex_invoice_id)) {
                return [
                    'success' => true,
                    'invoice_id' => $charge->perfex_invoice_id,
                    'message' => 'Invoice already exists for this charge'
                ];
            }

            // Load required models
            $this->load->model('invoices_model');
            $this->load->model('clients_model');

            // Get client data
            $client = $this->clients_model->get($charge->client_id);
            if (!$client) {
                throw new Exception('Client not found: ' . $charge->client_id);
            }

            // Get contract data if available
            $contract = null;
            if (!empty($charge->billing_group) && !empty($charge->billing_group->contract_id)) {
                $this->db->where('id', $charge->billing_group->contract_id);
                $contract = $this->db->get(db_prefix() . 'contracts')->row();
            }

            // Get sale_agent from billing group
            $sale_agent = 0;
            if (!empty($charge->billing_group) && !empty($charge->billing_group->sale_agent)) {
                $sale_agent = $charge->billing_group->sale_agent;
            } else {
                // Fallback to current staff user if no sale agent is set
                $sale_agent = get_staff_user_id() ?: 0;
            }

            // Prepare complete invoice item with all required fields to avoid warnings
            $invoice_items = [
                [
                    'description' => sprintf('Cobrança %s - Vencimento: %s', 
                        $charge->billing_type, 
                        date('d/m/Y', strtotime($charge->due_date))
                    ),
                    'long_description' => $contract ? $contract->subject : 'Cobrança via ChargeManager',
                    'qty' => 1,
                    'rate' => floatval($charge->value),
                    'unit' => '', // Required field to avoid warning
                    'order' => 1, // Required field to avoid warning  
                    'taxname' => [], // Required for tax calculations
                    // Additional fields that might be expected
                    'item_order' => 1
                ]
            ];

            // Prepare complete invoice data using Perfex native numbering system
            $invoice_data = [
                'clientid' => (int)$charge->client_id,
                'date' => date('Y-m-d'),
                'duedate' => $charge->due_date,
                'currency' => get_base_currency()->id,
                'subtotal' => floatval($charge->value),
                'total' => floatval($charge->value),
                'total_tax' => 0.00,
                'adjustment' => 0,
                'discount_percent' => 0.00,
                'discount_total' => 0.00,
                'discount_type' => '',
                'sale_agent' => $sale_agent,
                'status' => 1, // STATUS_UNPAID - Perfex will handle numbering automatically
                'adminnote' => 'Invoice generated by ChargeManager for Charge #' . $charge_id,
                'clientnote' => trim($options['client_note'] ?? get_option('predefined_clientnote_invoice') ?? ''),
                'terms' => trim($options['terms'] ?? get_option('predefined_terms_invoice') ?? ''),
                'allowed_payment_modes' => serialize([]),
                // Client billing information using correct field names - ensure all are strings
                'billing_street' => trim($client->address ?? ''),
                'billing_city' => trim($client->city ?? ''),
                'billing_state' => trim($client->state ?? ''),
                'billing_zip' => trim($client->zip ?? ''),
                'billing_country' => (int)($client->country ?? get_option('invoice_company_country') ?? 0),
                // Shipping same as billing
                'shipping_street' => trim($client->address ?? ''),
                'shipping_city' => trim($client->city ?? ''),
                'shipping_state' => trim($client->state ?? ''),
                'shipping_zip' => trim($client->zip ?? ''),
                'shipping_country' => (int)($client->country ?? get_option('invoice_company_country') ?? 0),
                'include_shipping' => 0,
                'show_shipping_on_invoice' => 1,
                'show_quantity_as' => 1,
                // Recurring fields
                'recurring' => 0,
                'recurring_type' => null,
                'custom_recurring' => 0,
                'cycles' => 0,
                'total_cycles' => 0,
                'is_recurring_from' => null,
                'last_recurring_date' => null,
                'cancel_overdue_reminders' => 0,
                'project_id' => 0,
                'subscription_id' => 0
            ];

            // Add items to invoice data using Perfex standard
            $invoice_data['newitems'] = $invoice_items;

            // Temporarily disable webhook errors to avoid warnings from external modules
            $original_error_reporting = error_reporting();
            error_reporting($original_error_reporting & ~E_WARNING);

            // Create invoice with items using Perfex standard method
            // The invoices_model->add() will automatically handle:
            // 1. Generate hash with app_generate_hash()
            // 2. Set prefix and number_format from options
            // 3. For non-draft invoices, the number is set to 0 initially
            // 4. Then increment_next_number() is called which updates the counter
            // 5. We need to manually set the actual number after creation
            $invoice_id = $this->invoices_model->add($invoice_data);

            // Restore original error reporting
            error_reporting($original_error_reporting);

            if (!$invoice_id) {
                // Log the invoice data for debugging
                log_activity('ChargeManager Debug: Failed to create invoice with data: ' . json_encode($invoice_data));
                throw new Exception('Failed to create invoice - check logs for details');
            }

            // The Perfex invoices_model->add() method has a bug where it doesn't set the number
            // for non-draft invoices. It only calls increment_next_number() but doesn't update
            // the actual invoice record. We need to fix this manually.
            
            // Get the current next number (it was already incremented by add() method)
            $current_next_number = get_option('next_invoice_number');
            $actual_invoice_number = $current_next_number - 1; // The number that should be used
            
            // Update the invoice with the correct number
            $this->db->where('id', $invoice_id);
            $this->db->update(db_prefix() . 'invoices', [
                'number' => $actual_invoice_number
            ]);

            // Update charge with invoice ID
            $this->update($charge_id, ['perfex_invoice_id' => $invoice_id]);

            // Get the generated invoice to log the number
            $created_invoice = $this->invoices_model->get($invoice_id);
            $invoice_number = format_invoice_number($invoice_id);

            // Log activity with the actual invoice number
            log_activity('ChargeManager: Individual invoice ' . $invoice_number . ' created for charge #' . $charge_id);

            return [
                'success' => true,
                'invoice_id' => $invoice_id,
                'invoice_number' => $invoice_number,
                'message' => 'Invoice created successfully with number: ' . $invoice_number
            ];

        } catch (Exception $e) {
            log_activity('ChargeManager Error in generate_individual_invoice: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update related invoice when charge is edited
     * @param int $charge_id
     * @param object $updated_charge
     * @param object $previous_charge
     * @return array|null
     */
    private function update_related_invoice($charge_id, $updated_charge, $previous_charge)
    {
        try {
            if (empty($updated_charge->perfex_invoice_id)) {
                return null;
            }

            // Load required models
            $this->load->model('invoices_model');

            // Get current invoice
            $invoice = $this->invoices_model->get($updated_charge->perfex_invoice_id);
            if (!$invoice) {
                throw new Exception('Related invoice not found: ' . $updated_charge->perfex_invoice_id);
            }

            // Check if invoice can be updated (not paid)
            if ($invoice->status == 2) { // STATUS_PAID
                return [
                    'success' => false,
                    'message' => 'Cannot update paid invoice'
                ];
            }

            // Prepare minimal invoice update data to avoid warnings
            $invoice_update_data = [];
            $fields_updated = [];

            // Update due date if changed
            if ($updated_charge->due_date != $previous_charge->due_date) {
                $invoice_update_data['duedate'] = $updated_charge->due_date;
                $fields_updated[] = 'duedate';
            }

            // Update amounts if value changed
            if ($updated_charge->value != $previous_charge->value) {
                $new_value = floatval($updated_charge->value);
                $invoice_update_data['subtotal'] = $new_value;
                $invoice_update_data['total'] = $new_value;
                $fields_updated[] = 'amounts';
            }

            // Update invoice items if description or value changed
            $items_update_needed = (
                $updated_charge->value != $previous_charge->value ||
                $updated_charge->billing_type != $previous_charge->billing_type ||
                $updated_charge->due_date != $previous_charge->due_date
            );

            if ($items_update_needed) {
                // Get current invoice items
                $this->db->where('rel_id', $updated_charge->perfex_invoice_id);
                $this->db->where('rel_type', 'invoice');
                $current_items = $this->db->get(db_prefix() . 'itemable')->result();

                if (!empty($current_items)) {
                    // Update the first item (should be the charge item)
                    $item = $current_items[0];
                    
                    $new_description = sprintf('Cobrança %s - Vencimento: %s', 
                        $updated_charge->billing_type, 
                        date('d/m/Y', strtotime($updated_charge->due_date))
                    );

                    $item_update_data = [
                        'description' => $new_description,
                        'rate' => floatval($updated_charge->value),
                        'qty' => 1
                    ];

                    $this->db->where('id', $item->id);
                    $this->db->update(db_prefix() . 'itemable', $item_update_data);
                    $fields_updated[] = 'items';
                }
            }

            // Update invoice only if there are changes to make
            if (!empty($invoice_update_data)) {
                // Use direct database update to avoid validation issues
                $this->db->where('id', $updated_charge->perfex_invoice_id);
                $updated = $this->db->update(db_prefix() . 'invoices', $invoice_update_data);

                if (!$updated) {
                    throw new Exception('Failed to update related invoice');
                }
            }

            log_activity('ChargeManager: Invoice #' . $updated_charge->perfex_invoice_id . ' updated due to charge #' . $charge_id . ' edit');

            return [
                'success' => true,
                'message' => 'Related invoice updated successfully',
                'invoice_id' => $updated_charge->perfex_invoice_id,
                'updated_fields' => $fields_updated
            ];

        } catch (Exception $e) {
            log_activity('ChargeManager Error updating related invoice: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update related invoice: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get charge with billing group and client relationships
     * @param int $charge_id
     * @return object|null
     */
    public function get_charge_with_relationships($charge_id)
    {
        if (empty($charge_id)) {
            return null;
        }

        // Get the charge
        $charge = $this->get($charge_id);
        
        if (!$charge) {
            return null;
        }

        // Get billing group if exists
        if (!empty($charge->billing_group_id)) {
            $this->db->where('id', $charge->billing_group_id);
            $charge->billing_group = $this->db->get(db_prefix() . 'chargemanager_billing_groups')->row();
        }

        return $charge;
    }
} 