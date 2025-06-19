<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Settings extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('chargemanager_model');
    }

    /**
     * Main settings page
     */
    public function index()
    {
        if (!has_permission('chargemanager', '', 'view')) {
            access_denied('chargemanager settings');
        }

        // Handle form submission
        if ($this->input->post()) {
            $this->save_settings();
            return;
        }

        // Load current settings
        $settings = $this->get_current_settings();
        
        // Pass individual variables that the view expects
        $data['asaas_api_key'] = $settings['api_key'] ?? '';
        $data['asaas_environment'] = $settings['environment'] ?? 'sandbox';
        $data['webhook_token'] = $settings['webhook_token'] ?? '';
        $data['enabled'] = $settings['enabled'] ?? false;
        
        // Additional settings that the view expects
        $data['auto_sync_clients'] = $this->chargemanager_model->get_asaas_setting('auto_sync_clients') ?? '0';
        $data['auto_create_invoices'] = $this->chargemanager_model->get_asaas_setting('auto_create_invoices') ?? '0';
        $data['debug_mode'] = $this->chargemanager_model->get_asaas_setting('debug_mode') ?? '0';
        $data['default_billing_type'] = $this->chargemanager_model->get_asaas_setting('default_billing_type') ?? 'BOLETO';
        
        // Get recent logs for the view
        $data['recent_logs'] = $this->chargemanager_model->get_sync_logs([], 10);
        
        // Keep the settings array for backward compatibility
        $data['settings'] = $settings;
        $data['title'] = _l('chargemanager_settings');
        
        $this->load->view('admin/settings/index', $data);
    }

    /**
     * Save ASAAS settings
     */
    private function save_settings()
    {
        if (!has_permission('chargemanager', '', 'edit')) {
            access_denied('chargemanager settings edit');
        }

        $settings = [
            'api_key' => $this->input->post('asaas_api_key'),
            'environment' => $this->input->post('asaas_environment'),
            'webhook_token' => $this->input->post('webhook_token'),
            'enabled' => $this->input->post('enabled') ? '1' : '0',
            'auto_sync_clients' => $this->input->post('auto_sync_clients') ? '1' : '0',
            'auto_create_invoices' => $this->input->post('auto_create_invoices') ? '1' : '0',
            'debug_mode' => $this->input->post('debug_mode') ? '1' : '0',
            'default_billing_type' => $this->input->post('default_billing_type') ?: 'BOLETO'
        ];

        try {
            // Validate API key if provided
            if (!empty($settings['api_key'])) {
                $validation_result = $this->validate_api_key($settings['api_key'], $settings['environment']);
                
                if (!$validation_result['success']) {
                    set_alert('danger', $validation_result['message']);
                    redirect(admin_url('chargemanager/settings'));
                    return;
                }
            }

            // Save settings to database
            foreach ($settings as $name => $value) {
                $this->chargemanager_model->save_asaas_setting($name, $value);
            }

            // Generate webhook token if not provided
            if (empty($settings['webhook_token'])) {
                $webhook_token = $this->generate_webhook_token();
                $this->chargemanager_model->save_asaas_setting('webhook_token', $webhook_token);
            }

            set_alert('success', _l('chargemanager_settings_saved_successfully'));
            log_activity('ChargeManager: Settings updated');

        } catch (Exception $e) {
            log_activity('ChargeManager Error: ' . $e->getMessage());
            set_alert('danger', _l('chargemanager_error_saving_settings') . ': ' . $e->getMessage());
        }

        redirect(admin_url('chargemanager/settings'));
    }

    /**
     * Test API connection
     */
    public function test_connection()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'view')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        try {
            $settings = $this->get_current_settings();
            
            if (empty($settings['api_key'])) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('chargemanager_api_key_required')
                ]);
                return;
            }

            $validation_result = $this->validate_api_key($settings['api_key'], $settings['environment']);
            
            echo json_encode($validation_result);

        } catch (Exception $e) {
            log_activity('ChargeManager Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => _l('chargemanager_connection_test_failed') . ': ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get current ASAAS settings
     */
    private function get_current_settings()
    {
        return [
            'api_key' => $this->chargemanager_model->get_asaas_setting('api_key'),
            'environment' => $this->chargemanager_model->get_asaas_setting('environment') ?: 'sandbox',
            'webhook_token' => $this->chargemanager_model->get_asaas_setting('webhook_token'),
            'enabled' => $this->chargemanager_model->get_asaas_setting('enabled') === '1',
            'auto_sync_clients' => $this->chargemanager_model->get_asaas_setting('auto_sync_clients') === '1',
            'auto_create_invoices' => $this->chargemanager_model->get_asaas_setting('auto_create_invoices') === '1',
            'debug_mode' => $this->chargemanager_model->get_asaas_setting('debug_mode') === '1',
            'default_billing_type' => $this->chargemanager_model->get_asaas_setting('default_billing_type') ?: 'BOLETO'
        ];
    }

    /**
     * Generate webhook token
     */
    private function generate_webhook_token()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validate API key with ASAAS
     */
    private function validate_api_key($api_key, $environment)
    {
        try {
            // Load Gateway Manager to test connection
            $this->load->library('chargemanager/Gateway_manager');
            
            // Test the API key by attempting to retrieve account info
            $result = $this->gateway_manager->test_connection($api_key, $environment);
            
            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => _l('chargemanager_api_validation_failed') . ': ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get webhook URL for configuration
     */
    public function get_webhook_url()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $webhook_url = base_url('chargemanager/webhook/handle');
        
        echo json_encode([
            'success' => true,
            'webhook_url' => $webhook_url
        ]);
    }

    /**
     * Clear logs
     */
    public function clear_logs()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'delete')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        try {
            // Clear sync logs
            $this->chargemanager_model->clean_old_sync_logs(0); // 0 days = clear all
            
            log_activity('ChargeManager: All logs cleared');
            
            echo json_encode([
                'success' => true,
                'message' => _l('chargemanager_logs_cleared_successfully')
            ]);

        } catch (Exception $e) {
            log_activity('ChargeManager Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => _l('chargemanager_error_clearing_logs') . ': ' . $e->getMessage()
            ]);
        }
    }
} 