<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Chargemanager_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // ASAAS SETTINGS METHODS

    /**
     * Get ASAAS setting value
     * @param string $name
     * @return string|null
     */
    public function get_asaas_setting($name)
    {
        $this->db->where('name', $name);
        $result = $this->db->get(db_prefix() . 'chargemanager_asaas_settings')->row();
        
        return $result ? $result->value : null;
    }

    /**
     * Save ASAAS setting
     * @param string $name
     * @param string $value
     * @return bool
     */
    public function save_asaas_setting($name, $value)
    {
        $this->db->where('name', $name);
        $existing = $this->db->get(db_prefix() . 'chargemanager_asaas_settings')->row();

        if ($existing) {
            $this->db->where('name', $name);
            return $this->db->update(db_prefix() . 'chargemanager_asaas_settings', ['value' => $value]);
        } else {
            return $this->db->insert(db_prefix() . 'chargemanager_asaas_settings', [
                'name' => $name,
                'value' => $value
            ]);
        }
    }

    /**
     * Get all ASAAS settings
     * @return array
     */
    public function get_all_asaas_settings()
    {
        $results = $this->db->get(db_prefix() . 'chargemanager_asaas_settings')->result();
        $settings = [];
        
        foreach ($results as $result) {
            $settings[$result->name] = $result->value;
        }
        
        return $settings;
    }

    // ENTITY MAPPINGS METHODS

    /**
     * Create entity mapping
     * @param array $data
     * @return int|false
     */
    public function create_entity_mapping($data)
    {
        // Validate array values to prevent array injection
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = json_encode($value);
            }
        }

        // Ensure perfex_entity_id is integer
        if (isset($data['perfex_entity_id'])) {
            $data['perfex_entity_id'] = (int)$data['perfex_entity_id'];
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->insert(db_prefix() . 'chargemanager_entity_mappings', $data)) {
            return $this->db->insert_id();
        }
        
        return false;
    }

    /**
     * Get entity mapping
     * @param string $perfex_entity_type
     * @param int $perfex_entity_id
     * @param string $gateway
     * @param string $gateway_entity_type
     * @return object|null
     */
    public function get_entity_mapping($perfex_entity_type, $perfex_entity_id, $gateway = 'asaas', $gateway_entity_type = 'customer')
    {
        // Validate parameters to prevent array injection
        if (is_array($perfex_entity_type) || is_array($perfex_entity_id) || is_array($gateway) || is_array($gateway_entity_type)) {
            throw new Exception('Entity mapping parameters cannot be arrays');
        }

        $this->db->where('perfex_entity_type', $perfex_entity_type);
        $this->db->where('perfex_entity_id', (int)$perfex_entity_id);
        $this->db->where('gateway', $gateway);
        $this->db->where('gateway_entity_type', $gateway_entity_type);
        
        return $this->db->get(db_prefix() . 'chargemanager_entity_mappings')->row();
    }

    /**
     * Update entity mapping
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_entity_mapping($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update(db_prefix() . 'chargemanager_entity_mappings', $data);
    }

    /**
     * Delete entity mapping
     * @param int $id
     * @return bool
     */
    public function delete_entity_mapping($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete(db_prefix() . 'chargemanager_entity_mappings');
    }

    /**
     * Get or create customer mapping
     * @param int $client_id
     * @param string $gateway
     * @return string|null Gateway customer ID
     */
    public function get_or_create_customer_mapping($client_id, $gateway = 'asaas')
    {
        // Check if mapping exists
        $mapping = $this->get_entity_mapping('client', $client_id, $gateway, 'customer');
        
        if ($mapping) {
            return $mapping->gateway_entity_id;
        }

        // Create customer in gateway
        $this->load->library('chargemanager/Gateway_manager');
        $customer_result = $this->gateway_manager->get_or_create_customer($client_id);

        if ($customer_result['success']) {
            // Create mapping
            $this->create_entity_mapping([
                'perfex_entity_type' => 'client',
                'perfex_entity_id' => $client_id,
                'gateway' => $gateway,
                'gateway_entity_type' => 'customer',
                'gateway_entity_id' => $customer_result['customer_id']
            ]);

            return $customer_result['customer_id'];
        }

        return null;
    }

    // WEBHOOK QUEUE METHODS

    /**
     * Add webhook to queue
     * @param array $data
     * @return int|false
     */
    public function queue_webhook($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->insert(db_prefix() . 'chargemanager_webhook_queue', $data)) {
            return $this->db->insert_id();
        }
        
        return false;
    }

    /**
     * Get pending webhooks
     * @param int $limit
     * @return array
     */
    public function get_pending_webhooks($limit = 10)
    {
        $this->db->where('status', 'pending');
        $this->db->where('attempts <', 'max_attempts', false);
        $this->db->order_by('created_at', 'ASC');
        $this->db->limit($limit);
        
        return $this->db->get(db_prefix() . 'chargemanager_webhook_queue')->result();
    }

    /**
     * Update webhook status
     * @param int $webhook_id
     * @param string $status
     * @param string $error_message
     * @return bool
     */
    public function update_webhook_status($webhook_id, $status, $error_message = null)
    {
        $update_data = [
            'status' => $status
        ];

        if ($status === 'completed') {
            $update_data['processed_at'] = date('Y-m-d H:i:s');
        }

        if ($error_message) {
            $update_data['error_message'] = $error_message;
        }

        $this->db->where('id', $webhook_id);
        return $this->db->update(db_prefix() . 'chargemanager_webhook_queue', $update_data);
    }

    /**
     * Increment webhook attempts
     * @param int $webhook_id
     * @return bool
     */
    public function increment_webhook_attempts($webhook_id)
    {
        $this->db->where('id', $webhook_id);
        $this->db->set('attempts', 'attempts + 1', false);
        return $this->db->update(db_prefix() . 'chargemanager_webhook_queue');
    }

    /**
     * Clean old webhook records
     * @param int $days_old
     * @return bool
     */
    public function clean_old_webhooks($days_old = 30)
    {
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-' . $days_old . ' days'));
        
        $this->db->where('created_at <', $cutoff_date);
        $this->db->where_in('status', ['completed', 'failed']);
        
        return $this->db->delete(db_prefix() . 'chargemanager_webhook_queue');
    }

    // SYNC LOGS METHODS

    /**
     * Add sync log entry
     * @param array $data
     * @return int|false
     */
    public function add_sync_log($data)
    {
        // Validate array values to prevent array injection
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = json_encode($value);
            }
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->insert(db_prefix() . 'chargemanager_sync_logs', $data)) {
            return $this->db->insert_id();
        }
        
        return false;
    }

    /**
     * Get sync logs
     * @param array $where
     * @param int $limit
     * @return array
     */
    public function get_sync_logs($where = [], $limit = 100)
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get(db_prefix() . 'chargemanager_sync_logs')->result();
    }

    /**
     * Log successful sync
     * @param string $event_type
     * @param string $gateway
     * @param string $perfex_entity_type
     * @param int $perfex_entity_id
     * @param string $gateway_entity_id
     * @param string $message
     * @return int|false
     */
    public function log_sync_success($event_type, $gateway, $perfex_entity_type, $perfex_entity_id, $gateway_entity_id, $message = '')
    {
        // Validate parameters to prevent array injection
        if (is_array($event_type) || is_array($gateway) || is_array($perfex_entity_type) || is_array($perfex_entity_id) || is_array($gateway_entity_id)) {
            throw new Exception('Log sync parameters cannot be arrays');
        }

        return $this->add_sync_log([
            'event_type' => $event_type,
            'gateway' => $gateway,
            'perfex_entity_type' => $perfex_entity_type,
            'perfex_entity_id' => (int)$perfex_entity_id,
            'gateway_entity_id' => $gateway_entity_id,
            'status' => 'success',
            'message' => is_array($message) ? json_encode($message) : $message
        ]);
    }

    /**
     * Log sync error
     * @param string $event_type
     * @param string $gateway
     * @param string $perfex_entity_type
     * @param int $perfex_entity_id
     * @param string $error_message
     * @return int|false
     */
    public function log_sync_error($event_type, $gateway, $perfex_entity_type, $perfex_entity_id, $error_message)
    {
        // Validate parameters to prevent array injection
        if (is_array($event_type) || is_array($gateway) || is_array($perfex_entity_type) || is_array($perfex_entity_id)) {
            throw new Exception('Log sync parameters cannot be arrays');
        }

        return $this->add_sync_log([
            'event_type' => $event_type,
            'gateway' => $gateway,
            'perfex_entity_type' => $perfex_entity_type,
            'perfex_entity_id' => (int)$perfex_entity_id,
            'status' => 'error',
            'message' => is_array($error_message) ? json_encode($error_message) : $error_message
        ]);
    }

    /**
     * Clean old sync logs
     * @param int $days_old
     * @return bool
     */
    public function clean_old_sync_logs($days_old = 90)
    {
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-' . $days_old . ' days'));
        
        $this->db->where('created_at <', $cutoff_date);
        
        return $this->db->delete(db_prefix() . 'chargemanager_sync_logs');
    }

    // BILLING GROUP STATUS METHODS

    /**
     * Refresh billing group status based on charges
     * @param int $billing_group_id
     * @return bool
     */
    public function refresh_billing_group_status($billing_group_id)
    {
        $this->load->model('chargemanager_billing_groups_model');
        return $this->chargemanager_billing_groups_model->refresh_status($billing_group_id);
    }

    // UTILITY METHODS

    /**
     * Check if ChargeManager is enabled
     * @return bool
     */
    public function is_enabled()
    {
        return $this->get_asaas_setting('enabled') === '1';
    }

    /**
     * Check if API is configured
     * @return bool
     */
    public function is_api_configured()
    {
        $api_key = $this->get_asaas_setting('api_key');
        return !empty($api_key);
    }

    /**
     * Get module statistics
     * @return array
     */
    public function get_module_statistics()
    {
        $stats = [];

        // Billing groups count
        $stats['total_billing_groups'] = $this->db->count_all(db_prefix() . 'chargemanager_billing_groups');
        
        // Charges count by status
        $this->db->select('status, COUNT(*) as count, SUM(value) as total_value');
        $this->db->group_by('status');
        $charge_stats = $this->db->get(db_prefix() . 'chargemanager_charges')->result();
        
        $stats['charges_by_status'] = [];
        $stats['total_charges'] = 0;
        $stats['total_value'] = 0;
        
        foreach ($charge_stats as $stat) {
            $stats['charges_by_status'][$stat->status] = [
                'count' => $stat->count,
                'total_value' => $stat->total_value
            ];
            $stats['total_charges'] += $stat->count;
            $stats['total_value'] += $stat->total_value;
        }

        // Webhook queue stats
        $this->db->select('status, COUNT(*) as count');
        $this->db->group_by('status');
        $webhook_stats = $this->db->get(db_prefix() . 'chargemanager_webhook_queue')->result();
        
        $stats['webhooks_by_status'] = [];
        foreach ($webhook_stats as $stat) {
            $stats['webhooks_by_status'][$stat->status] = $stat->count;
        }

        return $stats;
    }

    // PAYMENT MODES METHODS

    /**
     * Get payment mode ID for billing type
     * @param string $billing_type
     * @return int|null
     */
    public function get_payment_mode_id_for_billing_type($billing_type)
    {
        $payment_mode_names = [
            'BOLETO' => 'ChargeManager - Boleto',
            'CREDIT_CARD' => 'ChargeManager - Cartão de Crédito',
            'PIX' => 'ChargeManager - PIX'
        ];
        
        $mode_name = $payment_mode_names[$billing_type] ?? $payment_mode_names['BOLETO'];
        
        $this->db->where('name', $mode_name);
        $this->db->where('active', 1);
        $payment_mode = $this->db->get(db_prefix() . 'payment_modes')->row();
        
        if ($payment_mode) {
            return $payment_mode->id;
        }
        
        // Fallback: buscar qualquer payment mode ativo para faturas
        $this->db->where('active', 1);
        $this->db->where('invoices_only', 1);
        $fallback_mode = $this->db->get(db_prefix() . 'payment_modes')->row();
        
        return $fallback_mode ? $fallback_mode->id : 1; // 1 como último recurso
    }

    /**
     * Get all ChargeManager payment modes
     * @return array
     */
    public function get_chargemanager_payment_modes()
    {
        $this->db->like('name', 'ChargeManager -', 'after');
        $this->db->where('active', 1);
        return $this->db->get(db_prefix() . 'payment_modes')->result();
    }

    /**
     * Ensure ChargeManager payment modes exist
     * @return bool
     */
    public function ensure_payment_modes_exist()
    {
        $this->load->model('payment_modes_model');
        
        $required_modes = [
            [
                'name' => 'ChargeManager - Boleto',
                'description' => 'Pagamento via Boleto Bancário processado pelo ChargeManager/ASAAS',
                'billing_type' => 'BOLETO'
            ],
            [
                'name' => 'ChargeManager - PIX',
                'description' => 'Pagamento via PIX processado pelo ChargeManager/ASAAS',
                'billing_type' => 'PIX'
            ],
            [
                'name' => 'ChargeManager - Cartão de Crédito',
                'description' => 'Pagamento via Cartão de Crédito processado pelo ChargeManager/ASAAS',
                'billing_type' => 'CREDIT_CARD'
            ]
        ];
        
        $created_count = 0;
        
        foreach ($required_modes as $mode_data) {
            // Verificar se já existe
            $existing = $this->db->where('name', $mode_data['name'])
                                ->get(db_prefix() . 'payment_modes')
                                ->row();
            
            if (!$existing) {
                $payment_mode_data = [
                    'name' => $mode_data['name'],
                    'description' => $mode_data['description'],
                    'show_on_pdf' => 1,
                    'selected_by_default' => 0,
                    'invoices_only' => 1,
                    'expenses_only' => 0,
                    'active' => 1
                ];
                
                $mode_id = $this->payment_modes_model->add($payment_mode_data);
                if ($mode_id) {
                    $created_count++;
                    log_activity('ChargeManager: Payment mode "' . $mode_data['name'] . '" created with ID: ' . $mode_id);
                }
            }
        }
        
        return $created_count > 0;
    }
} 