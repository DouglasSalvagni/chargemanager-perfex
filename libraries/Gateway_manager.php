<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Gateway Manager Library
 * Interface unificada para gateways de pagamento
 */
class Gateway_manager
{
    private $CI;
    private $default_gateway = 'asaas';
    
    public function __construct()
    {
        $this->CI = &get_instance();
        
        // Load necessary components
        $this->CI->load->model('chargemanager/chargemanager_model');
        $this->CI->load->model('clients_model');
    }

    /**
     * Get primary contact email for client
     * @param int $client_id
     * @return string
     */
    private function get_client_email($client_id)
    {
        // Get primary contact
        $this->CI->db->where('userid', $client_id);
        $this->CI->db->where('is_primary', 1);
        $contact = $this->CI->db->get(db_prefix() . 'contacts')->row();
        
        return $contact ? $contact->email : '';
    }

    /**
     * Get or create customer in gateway
     * @param int|array $client_id Client ID or client data array
     * @param string $gateway Gateway name
     * @return array
     */
    public function get_or_create_customer($client_id, $gateway = null)
    {
        // Extract actual client ID if it's an array/object (following Omnisell pattern)
        if (is_array($client_id)) {
            if (isset($client_id['id'])) {
                $actual_client_id = (int)$client_id['id'];
            } else {
                throw new Exception('Invalid client data: missing ID');
            }
        } else {
            $actual_client_id = (int)$client_id;
        }

        // Use provided gateway or default gateway
        $gateway = $gateway ?: $this->default_gateway;
        
        try {
            // Check existing mapping
            $mapping = $this->CI->chargemanager_model->get_entity_mapping('client', $actual_client_id, $gateway, 'customer');
            
            if ($mapping) {
                return [
                    'success' => true,
                    'customer_id' => $mapping->gateway_entity_id,
                    'action' => 'found'
                ];
            }

            // Load gateway
            $gateway_instance = $this->get_gateway($gateway);
            
            if (!$gateway_instance) {
                throw new Exception('Gateway not available: ' . $gateway);
            }

            // Get client data using clients_model
            $client = $this->CI->clients_model->get($actual_client_id);

            if (!$client) {
                throw new Exception('Client not found: ' . $actual_client_id);
            }

            // Get client email from primary contact
            $client_email = $this->get_client_email($actual_client_id);

            // Create customer in gateway
            $customer_result = $gateway_instance->create_customer([
                'name' => $client->company ?? '',
                'email' => $client_email,
                'cpfCnpj' => $client->vat ?? '',
                'phone' => $client->phonenumber ?? '',
                'mobilePhone' => $client->phonenumber ?? '',
                'address' => $client->billing_street ?? $client->address ?? '',
                'addressNumber' => '',
                'complement' => '',
                'state' => $client->billing_state ?? $client->state ?? '',
                'city' => $client->billing_city ?? $client->city ?? '',
                'postalCode' => $client->billing_zip ?? $client->zip ?? ''
            ]);

            if (!$customer_result['success']) {
                throw new Exception($customer_result['message']);
            }

            // Create mapping
            $this->CI->chargemanager_model->create_entity_mapping([
                'perfex_entity_type' => 'client',
                'perfex_entity_id' => $actual_client_id,
                'gateway' => $gateway,
                'gateway_entity_type' => 'customer',
                'gateway_entity_id' => $customer_result['customer_id']
            ]);

            // Log success
            $this->CI->chargemanager_model->log_sync_success(
                'customer_create',
                $gateway,
                'client',
                $actual_client_id,
                $customer_result['customer_id'],
                'Customer created successfully'
            );

            return [
                'success' => true,
                'customer_id' => $customer_result['customer_id'],
                'action' => 'created'
            ];

        } catch (Exception $e) {
            // Log error
            $this->CI->chargemanager_model->log_sync_error(
                'customer_create',
                $gateway,
                'client',
                $actual_client_id,
                $e->getMessage()
            );

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update customer in gateway
     * @param int|array $client_id Client ID or client data array
     * @param string $gateway Gateway name
     * @return array
     */
    public function update_customer($client_id, $gateway = null)
    {
        // Extract actual client ID if it's an array/object (following Omnisell pattern)
        if (is_array($client_id)) {
            if (isset($client_id['id'])) {
                $actual_client_id = (int)$client_id['id'];
            } else {
                throw new Exception('Invalid client data: missing ID');
            }
        } else {
            $actual_client_id = (int)$client_id;
        }

        // Use provided gateway or default gateway
        $gateway = $gateway ?: $this->default_gateway;
        
        try {
            // Get existing mapping
            $mapping = $this->CI->chargemanager_model->get_entity_mapping('client', $actual_client_id, $gateway, 'customer');
            
            if (!$mapping || empty($mapping->gateway_entity_id)) {
                // No existing customer, create one
                return $this->get_or_create_customer($actual_client_id, $gateway);
            }

            // Load client data
            $client = $this->CI->clients_model->get($actual_client_id);
            
            if (!$client) {
                throw new Exception("Client {$actual_client_id} not found in Perfex");
            }

            // Load gateway
            $gateway_instance = $this->get_gateway($gateway);
            
            if (!$gateway_instance) {
                throw new Exception('Gateway not available: ' . $gateway);
            }

            // Get client email from primary contact
            $client_email = $this->get_client_email($actual_client_id);

            // Update customer in gateway
            $update_result = $gateway_instance->update_customer($mapping->gateway_entity_id, [
                'name' => $client->company ?? '',
                'email' => $client_email,
                'cpfCnpj' => $client->vat ?? '',
                'phone' => $client->phonenumber ?? '',
                'mobilePhone' => $client->phonenumber ?? '',
                'address' => $client->billing_street ?? $client->address ?? '',
                'addressNumber' => '',
                'complement' => '',
                'state' => $client->billing_state ?? $client->state ?? '',
                'city' => $client->billing_city ?? $client->city ?? '',
                'postalCode' => $client->billing_zip ?? $client->zip ?? ''
            ]);

            if (!$update_result['success']) {
                throw new Exception($update_result['message']);
            }

            // Log success
            $this->CI->chargemanager_model->log_sync_success(
                'customer_update',
                $gateway,
                'client',
                $actual_client_id,
                $mapping->gateway_entity_id,
                'Customer updated successfully'
            );

            return [
                'success' => true,
                'customer_id' => $mapping->gateway_entity_id,
                'action' => 'updated'
            ];

        } catch (Exception $e) {
            // Log error
            $this->CI->chargemanager_model->log_sync_error(
                'customer_update',
                $gateway,
                'client',
                $actual_client_id,
                $e->getMessage()
            );

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete customer from gateway
     * @param int|array $client_id Client ID or client data array
     * @param string $gateway Gateway name
     * @return array
     */
    public function delete_customer($client_id, $gateway = null)
    {
        // Extract actual client ID if it's an array/object (following Omnisell pattern)
        if (is_array($client_id)) {
            if (isset($client_id['id'])) {
                $actual_client_id = (int)$client_id['id'];
            } else {
                throw new Exception('Invalid client data: missing ID');
            }
        } else {
            $actual_client_id = (int)$client_id;
        }

        // Use provided gateway or default gateway
        $gateway = $gateway ?: $this->default_gateway;

        try {
            // Get mapping
            $mapping = $this->CI->chargemanager_model->get_entity_mapping('client', $actual_client_id, $gateway, 'customer');
            
            if (!$mapping) {
                return [
                    'success' => true,
                    'message' => 'Customer mapping not found'
                ];
            }

            // Load gateway
            $gateway_instance = $this->get_gateway($gateway);
            
            if (!$gateway_instance) {
                throw new Exception('Gateway not available: ' . $gateway);
            }

            // Delete customer in gateway
            $delete_result = $gateway_instance->delete_customer($mapping->gateway_entity_id);

            // Delete mapping regardless of gateway result
            $this->CI->chargemanager_model->delete_entity_mapping($mapping->id);

            // Log result
            if ($delete_result['success']) {
                $this->CI->chargemanager_model->log_sync_success(
                    'customer_delete',
                    $gateway,
                    'client',
                    $actual_client_id,
                    $mapping->gateway_entity_id,
                    'Customer deleted successfully'
                );
            } else {
                $this->CI->chargemanager_model->log_sync_error(
                    'customer_delete',
                    $gateway,
                    'client',
                    $actual_client_id,
                    'Gateway delete failed: ' . $delete_result['message']
                );
            }

            return [
                'success' => true,
                'message' => 'Customer mapping removed'
            ];

        } catch (Exception $e) {
            // Log error
            $this->CI->chargemanager_model->log_sync_error(
                'customer_delete',
                $gateway,
                'client',
                $actual_client_id,
                $e->getMessage()
            );

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create charge via gateway
     * @param array $charge_data
     * @return array
     */
    public function create_charge($charge_data)
    {
        try {
            $gateway = $charge_data['gateway'] ?? $this->default_gateway;
            
            // Ensure customer exists
            $customer_result = $this->get_or_create_customer($charge_data['client_id'], $gateway);
            
            if (!$customer_result['success']) {
                throw new Exception('Failed to get/create customer: ' . $customer_result['message']);
            }

            // Load gateway
            $gateway_instance = $this->get_gateway($gateway);
            
            if (!$gateway_instance) {
                throw new Exception('Gateway not available: ' . $gateway);
            }

            // Prepare charge data for gateway
            $gateway_charge_data = [
                'customer' => $customer_result['customer_id'],
                'billingType' => $charge_data['billing_type'],
                'value' => $charge_data['value'],
                'dueDate' => $charge_data['due_date'],
                'description' => $charge_data['description'] ?? 'Cobrança via ChargeManager',
                'externalReference' => $charge_data['external_reference'] ?? null
            ];

            // Create charge in gateway
            $charge_result = $gateway_instance->create_charge($gateway_charge_data);

            if (!$charge_result['success']) {
                throw new Exception($charge_result['message']);
            }

            return [
                'success' => true,
                'charge_id' => $charge_result['charge_id'],
                'gateway' => $gateway,
                'invoice_url' => $charge_result['invoice_url'] ?? null,
                'barcode' => $charge_result['barcode'] ?? null,
                'pix_code' => $charge_result['pix_code'] ?? null
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Cancel charge via gateway
     * @param string $charge_id
     * @param string $gateway
     * @return array
     */
    public function cancel_charge($charge_id, $gateway = null)
    {
        try {
            $gateway = $gateway ?: $this->default_gateway;
            
            // Load gateway
            $gateway_instance = $this->get_gateway($gateway);
            
            if (!$gateway_instance) {
                throw new Exception('Gateway not available: ' . $gateway);
            }

            // Cancel charge in gateway
            return $gateway_instance->cancel_charge($charge_id);

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Test connection to gateway
     * @param string $api_key
     * @param string $environment
     * @param string $gateway
     * @return array
     */
    public function test_connection($api_key, $environment, $gateway = null)
    {
        try {
            $gateway = $gateway ?: $this->default_gateway;
            
            // Load gateway with custom config
            $gateway_instance = $this->get_gateway($gateway, [
                'api_key' => $api_key,
                'environment' => $environment
            ]);
            
            if (!$gateway_instance) {
                throw new Exception('Gateway not available: ' . $gateway);
            }

            // Test connection
            return $gateway_instance->test_connection();

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get gateway instance
     * @param string $gateway_name
     * @param array $custom_config
     * @return object|null
     */
    private function get_gateway($gateway_name, $custom_config = null)
    {
        try {
            // Load Gateway Factory - use module_dir_path instead of APPPATH
            $gateway_factory_path = FCPATH . 'modules/chargemanager/libraries/payment_gateways/Gateway_factory.php';
            
            if (file_exists($gateway_factory_path)) {
                require_once $gateway_factory_path;
            } else {
                throw new Exception('Gateway Factory not found');
            }
            
            // Get gateway instance
            return Gateway_factory::create($gateway_name, $custom_config);

        } catch (Exception $e) {
            if (function_exists('log_activity')) {
                log_activity('ChargeManager Gateway Error: ' . $e->getMessage());
            }
            return null;
        }
    }

    /**
     * Get supported gateways
     * @return array
     */
    public function get_supported_gateways()
    {
        try {
            // Load Gateway Factory - use module_dir_path instead of APPPATH
            $gateway_factory_path = FCPATH . 'modules/chargemanager/libraries/payment_gateways/Gateway_factory.php';
            
            if (file_exists($gateway_factory_path)) {
                require_once $gateway_factory_path;
                return Gateway_factory::get_available_gateways();
            } else {
                throw new Exception('Gateway Factory not found');
            }
        } catch (Exception $e) {
            return [];
        }
    }
} 