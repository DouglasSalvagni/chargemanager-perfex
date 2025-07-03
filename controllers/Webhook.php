<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Webhook extends CI_Controller
{
    const TOKEN_HEADER = 'X-Webhook-Token';
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('chargemanager_model');
        $this->load->model('chargemanager_charges_model');
        $this->load->library('chargemanager/Gateway_manager');
    }

    /**
     * Handle incoming webhooks
     */
    public function handle()
    {
        try {
            // Log incoming webhook
            log_activity('ChargeManager: Webhook received from ' . $this->input->ip_address());

            // // Validate webhook token
            // if (!$this->validate_webhook_token()) {
            //     $this->json_response(['success' => false, 'message' => 'Unauthorized'], 401);
            //     return;
            // }

            // Get webhook payload
            $payload = $this->get_webhook_payload();
            
            if (!$payload) {
                $this->json_response(['success' => false, 'message' => 'Invalid payload'], 400);
                return;
            }

            // Queue webhook for processing
            $queue_id = $this->queue_webhook($payload);
            
            // Process webhook immediately
            $result = $this->process_webhook($queue_id);

            // Return response based on result
            if ($result['success']) {
                $this->json_response(['success' => true, 'message' => $result['message']]);
            } else {
                // All business logic errors should return 500 to trigger ASAAS retry
                // "Not found" cases are now handled as success in the specific methods
                $this->json_response(['success' => false, 'message' => $result['message']], 500);
            }

        } catch (Exception $e) {
            log_activity('ChargeManager Webhook Error: ' . $e->getMessage());
            $this->json_response(['success' => false, 'message' => 'Internal error'], 500);
        }
    }

    /**
     * Validate webhook token
     */
    private function validate_webhook_token()
    {
        $token_header = $this->input->get_request_header(self::TOKEN_HEADER);
        $stored_token = $this->chargemanager_model->get_asaas_setting('webhook_token');

        return !empty($token_header) && !empty($stored_token) && hash_equals($stored_token, $token_header);
    }

    /**
     * Get webhook payload
     */
    private function get_webhook_payload()
    {
        $raw_input = file_get_contents('php://input');
        
        if (empty($raw_input)) {
            return null;
        }

        $payload = json_decode($raw_input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $payload;
    }

    /**
     * Queue webhook for processing
     */
    private function queue_webhook($payload)
    {
        // Log the payload for debugging
        log_activity('ChargeManager: Webhook payload - Event: ' . ($payload['event'] ?? 'unknown') . 
                    ', Payment ID: ' . ($payload['payment']['id'] ?? 'N/A'));

        $queue_data = [
            'gateway' => 'asaas',
            'event_type' => $payload['event'] ?? 'unknown',
            'payload' => json_encode($payload),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert(db_prefix() . 'chargemanager_webhook_queue', $queue_data);
        return $this->db->insert_id();
    }

    /**
     * Process webhook from queue
     */
    private function process_webhook($queue_id)
    {
        try {
            // Get webhook data
            $this->db->where('id', $queue_id);
            $webhook = $this->db->get(db_prefix() . 'chargemanager_webhook_queue')->row();

            if (!$webhook) {
                return [
                    'success' => false,
                    'message' => 'Webhook not found in queue'
                ];
            }

            // Update status to processing
            $this->db->where('id', $queue_id);
            $this->db->update(db_prefix() . 'chargemanager_webhook_queue', [
                'status' => 'processing',
                'attempts' => $webhook->attempts + 1
            ]);

            $payload = json_decode($webhook->payload, true);
            
            // Process based on event type
            $result = $this->process_event($payload);

            if ($result['success']) {
                // Mark as completed
                $this->db->where('id', $queue_id);
                $this->db->update(db_prefix() . 'chargemanager_webhook_queue', [
                    'status' => 'completed',
                    'processed_at' => date('Y-m-d H:i:s')
                ]);

                log_activity('ChargeManager: Webhook processed successfully - ' . $webhook->event_type);
                return $result;
            } else {
                // Mark as failed but don't throw exception
                $this->db->where('id', $queue_id);
                $this->db->update(db_prefix() . 'chargemanager_webhook_queue', [
                    'status' => 'failed',
                    'error_message' => $result['message']
                ]);

                log_activity('ChargeManager Webhook Error: ' . $result['message']);
                return $result;
            }

        } catch (Exception $e) {
            // Mark as failed
            $this->db->where('id', $queue_id);
            $this->db->update(db_prefix() . 'chargemanager_webhook_queue', [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            log_activity('ChargeManager Webhook Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process webhook event
     */
    private function process_event($payload)
    {
        $event_type = $payload['event'] ?? '';
        
        switch ($event_type) {
            case 'PAYMENT_RECEIVED':
            case 'PAYMENT_CONFIRMED':
                return $this->process_payment_received($payload);
                
            case 'PAYMENT_OVERDUE':
                return $this->process_payment_overdue($payload);
                
            case 'PAYMENT_DELETED':
                return $this->process_payment_deleted($payload);
                
            default:
                return [
                    'success' => true,
                    'message' => 'Event type not handled: ' . $event_type
                ];
        }
    }

    /**
     * Process payment received event
     */
    private function process_payment_received($payload)
    {
        try {
            $payment_data = $payload['payment'] ?? [];
            $charge_id = $payment_data['id'] ?? '';

            if (empty($charge_id)) {
                return [
                    'success' => false,
                    'message' => 'Payment ID not found in webhook payload'
                ];
            }

            // Find the charge in our database
            $this->db->where('gateway_charge_id', $charge_id);
            $charge = $this->db->get(db_prefix() . 'chargemanager_charges')->row();

            if (!$charge) {
                // Charge not found is not an error - it means this charge was not created by our system
                // Return success to prevent ASAAS from retrying
                log_activity('ChargeManager: Webhook received for charge not in our system: ' . $charge_id);
                return [
                    'success' => true,
                    'message' => 'Charge not found in our system - webhook acknowledged'
                ];
            }

            // Update charge status
            $update_data = [
                'status' => 'paid',
                'paid_at' => date('Y-m-d H:i:s'),
                'paid_amount' => $payment_data['value'] ?? $charge->value,
                'payment_method' => $payment_data['billingType'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->where('id', $charge->id);
            $this->db->update(db_prefix() . 'chargemanager_charges', $update_data);

            // Create payment record in Perfex
            if ($charge->perfex_invoice_id) {
                $this->create_payment_record($charge, $payment_data);
            }

            // Update billing group status
            if ($charge->billing_group_id) {
                $this->chargemanager_model->refresh_billing_group_status($charge->billing_group_id);
            }

            return [
                'success' => true,
                'message' => 'Payment processed successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process payment overdue event
     */
    private function process_payment_overdue($payload)
    {
        try {
            $payment_data = $payload['payment'] ?? [];
            $charge_id = $payment_data['id'] ?? '';

            if (empty($charge_id)) {
                return [
                    'success' => false,
                    'message' => 'Payment ID not found in webhook payload'
                ];
            }

            // Find the charge in our database
            $this->db->where('gateway_charge_id', $charge_id);
            $charge = $this->db->get(db_prefix() . 'chargemanager_charges')->row();

            if (!$charge) {
                // Charge not found is not an error - return success to prevent retries
                log_activity('ChargeManager: Overdue webhook received for charge not in our system: ' . $charge_id);
                return [
                    'success' => true,
                    'message' => 'Charge not found in our system - webhook acknowledged'
                ];
            }

            // Update charge status using the model's dedicated method
            $updated = $this->chargemanager_charges_model->update_payment_status($charge_id, 'overdue');

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'Failed to update charge status'
                ];
            }

            // Update related invoice status if exists
            if (!empty($charge->perfex_invoice_id)) {
                $this->load->model('invoices_model');
                
                // Get current invoice to validate
                $invoice = $this->invoices_model->get($charge->perfex_invoice_id);
                
                if ($invoice && $invoice->status != 2) { // Only update if not already paid (status 2)
                    // Update invoice status to overdue (status 4)
                    $this->db->where('id', $charge->perfex_invoice_id);
                    $this->db->update(db_prefix() . 'invoices', ['status' => 4]);
                    
                    log_activity('ChargeManager: Invoice #' . $charge->perfex_invoice_id . ' status updated to overdue due to charge #' . $charge->id);
                }
            }

            return [
                'success' => true,
                'message' => 'Payment marked as overdue'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process payment deleted event
     */
    private function process_payment_deleted($payload)
    {
        try {
            $payment_data = $payload['payment'] ?? [];
            $charge_id = $payment_data['id'] ?? '';

            if (empty($charge_id)) {
                return [
                    'success' => false,
                    'message' => 'Payment ID not found in webhook payload'
                ];
            }

            // Find the charge in our database
            $this->db->where('gateway_charge_id', $charge_id);
            $charge = $this->db->get(db_prefix() . 'chargemanager_charges')->row();

            if (!$charge) {
                // Charge not found is not an error - return success to prevent retries
                log_activity('ChargeManager: Delete webhook received for charge not in our system: ' . $charge_id);
                return [
                    'success' => true,
                    'message' => 'Charge not found in our system - webhook acknowledged'
                ];
            }

            // Update charge status to cancelled
            $this->db->where('id', $charge->id);
            $this->db->update(db_prefix() . 'chargemanager_charges', [
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'message' => 'Payment cancelled'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create payment record in Perfex using payments_model
     */
    private function create_payment_record($charge, $payment_data)
    {
        if (!$charge->perfex_invoice_id) {
            log_activity('ChargeManager Warning: Cannot create payment record - no invoice ID for charge #' . $charge->id);
            return false;
        }

        // Load required models
        $this->load->model('invoices_model');
        $this->load->model('payments_model');
        
        // Get invoice to validate
        $invoice = $this->invoices_model->get($charge->perfex_invoice_id);
        
        if (!$invoice) {
            log_activity('ChargeManager Warning: Invoice not found for payment record - Invoice ID: ' . $charge->perfex_invoice_id);
            return false;
        }

        // Check if payment already exists for this transaction
        $transaction_id = $payment_data['externalReference'] ?? $charge->gateway_charge_id;
        if ($this->payments_model->transaction_exists($transaction_id, $charge->perfex_invoice_id)) {
            log_activity('ChargeManager Warning: Payment already exists for transaction: ' . $transaction_id);
            return false;
        }

        // Prepare payment data following Perfex standards
        $payment_insert_data = [
            'invoiceid' => $charge->perfex_invoice_id,
            'amount' => floatval($payment_data['value'] ?? $charge->value),
            'paymentmode' => $this->get_payment_mode_id($payment_data['billingType'] ?? 'UNDEFINED'),
            'date' => date('Y-m-d'),
            'note' => 'Pagamento via ChargeManager/ASAAS - Gateway ID: ' . $charge->gateway_charge_id,
            'transactionid' => $transaction_id,
            // Prevent automatic email sending as this is from webhook
            'do_not_send_email_template' => true
        ];

        // Use payments_model->add() which handles all the invoice status updates automatically
        $payment_id = $this->payments_model->add($payment_insert_data);

        if ($payment_id) {
            // Update charge with payment record ID
            $this->db->where('id', $charge->id);
            $this->db->update(db_prefix() . 'chargemanager_charges', [
                'payment_record_id' => $payment_id,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            log_activity('ChargeManager: Payment record #' . $payment_id . ' created for charge #' . $charge->id . ' (Invoice #' . $charge->perfex_invoice_id . ')');
            
            return $payment_id;
        } else {
            log_activity('ChargeManager Error: Failed to create payment record for charge #' . $charge->id);
            return false;
        }
    }

    /**
     * Get payment mode ID based on billing type
     */
    private function get_payment_mode_id($billing_type)
    {
        // Garantir que os payment modes existem
        $this->chargemanager_model->ensure_payment_modes_exist();
        
        // Usar o método do modelo para buscar dinamicamente
        $payment_mode_id = $this->chargemanager_model->get_payment_mode_id_for_billing_type($billing_type);
        
        if ($payment_mode_id) {
            return $payment_mode_id;
        }
        
        // Log de aviso se não encontrou payment mode adequado
        log_activity('ChargeManager Warning: Payment mode not found for billing type: ' . $billing_type . ', using fallback');
        
        // Fallback para o primeiro payment mode ativo encontrado
        $this->db->where('active', 1);
        $fallback = $this->db->get(db_prefix() . 'payment_modes')->row();
        
        return $fallback ? $fallback->id : 1;
    }

    /**
     * JSON response helper
     */
    private function json_response($data, $status_code = 200)
    {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }
} 