<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ASAAS Gateway Implementation
 * Implementação específica do gateway ASAAS
 */
class Asaas_gateway implements Gateway_interface
{
    private $CI;
    private $api_key;
    private $environment;
    private $base_url;
    
    public function __construct($custom_config = null)
    {
        $this->CI = &get_instance();
        $this->CI->load->model('chargemanager_model');
        
        if ($custom_config) {
            $this->api_key = $custom_config['api_key'];
            $this->environment = $custom_config['environment'];
        } else {
            $this->api_key = $this->CI->chargemanager_model->get_asaas_setting('api_key');
            $this->environment = $this->CI->chargemanager_model->get_asaas_setting('environment') ?: 'sandbox';
        }
        
        $this->base_url = $this->environment === 'production' 
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';
    }

    /**
     * Test connection to ASAAS
     * @return array
     */
    public function test_connection()
    {
        try {
            $response = $this->make_request('GET', '/myAccount');
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Conexão com ASAAS estabelecida com sucesso',
                    'account_info' => $response['data']
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Falha na conexão: ' . $response['message']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate ASAAS configuration
     * @return bool
     */
    public function validate_config()
    {
        return !empty($this->api_key) && !empty($this->environment);
    }

    /**
     * Get supported billing types
     * @return array
     */
    public function get_supported_billing_types()
    {
        return [
            'BOLETO' => 'Boleto Bancário',
            'CREDIT_CARD' => 'Cartão de Crédito',
            'PIX' => 'PIX'
        ];
    }

    /**
     * Create customer in ASAAS
     * @param array $customer_data
     * @return array
     */
    public function create_customer($customer_data)
    {
        try {
            // Validate required fields
            if (empty($customer_data['name'])) {
                throw new Exception('Nome do cliente é obrigatório');
            }

            // Build payload with only non-empty values (following ASAAS API spec)
            $payload = [
                'name' => trim($customer_data['name'])
            ];

            // Add optional fields only if they have values
            if (!empty($customer_data['email'])) {
                $payload['email'] = trim($customer_data['email']);
            }

            if (!empty($customer_data['cpfCnpj'])) {
                $payload['cpfCnpj'] = $this->sanitize_document($customer_data['cpfCnpj']);
            }

            if (!empty($customer_data['phone'])) {
                $payload['phone'] = $this->sanitize_phone($customer_data['phone']);
            }

            if (!empty($customer_data['mobilePhone'])) {
                $payload['mobilePhone'] = $this->sanitize_phone($customer_data['mobilePhone']);
            }

            if (!empty($customer_data['address'])) {
                $payload['address'] = trim($customer_data['address']);
            }

            if (!empty($customer_data['addressNumber'])) {
                $payload['addressNumber'] = trim($customer_data['addressNumber']);
            }

            if (!empty($customer_data['complement'])) {
                $payload['complement'] = trim($customer_data['complement']);
            }

            // Fix: state should map to province in ASAAS API
            if (!empty($customer_data['state'])) {
                $payload['province'] = trim($customer_data['state']);
            }

            if (!empty($customer_data['city'])) {
                $payload['city'] = trim($customer_data['city']);
            }

            if (!empty($customer_data['postalCode'])) {
                $payload['postalCode'] = $this->sanitize_postal_code($customer_data['postalCode']);
            }

            // Log the request for debugging
            log_activity('ChargeManager: Creating ASAAS customer with payload: ' . json_encode($payload));

            $response = $this->make_request('POST', '/customers', $payload);

            if ($response['success']) {
                log_activity('ChargeManager: ASAAS customer created successfully with ID: ' . $response['data']['id']);
                return [
                    'success' => true,
                    'customer_id' => $response['data']['id'],
                    'message' => 'Cliente criado com sucesso'
                ];
            }

            log_activity('ChargeManager: Failed to create ASAAS customer: ' . $response['message']);
            return [
                'success' => false,
                'message' => $response['message']
            ];

        } catch (Exception $e) {
            log_activity('ChargeManager: Exception creating ASAAS customer: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao criar cliente: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update customer in ASAAS
     * @param string $customer_id
     * @param array $customer_data
     * @return array
     */
    public function update_customer($customer_id, $customer_data)
    {
        try {
            // Validate required fields
            if (empty($customer_data['name'])) {
                throw new Exception('Nome do cliente é obrigatório');
            }

            // Build payload with only non-empty values (following ASAAS API spec)
            $payload = [
                'name' => trim($customer_data['name'])
            ];

            // Add optional fields only if they have values
            if (!empty($customer_data['email'])) {
                $payload['email'] = trim($customer_data['email']);
            }

            if (!empty($customer_data['cpfCnpj'])) {
                $payload['cpfCnpj'] = $this->sanitize_document($customer_data['cpfCnpj']);
            }

            if (!empty($customer_data['phone'])) {
                $payload['phone'] = $this->sanitize_phone($customer_data['phone']);
            }

            if (!empty($customer_data['mobilePhone'])) {
                $payload['mobilePhone'] = $this->sanitize_phone($customer_data['mobilePhone']);
            }

            if (!empty($customer_data['address'])) {
                $payload['address'] = trim($customer_data['address']);
            }

            if (!empty($customer_data['addressNumber'])) {
                $payload['addressNumber'] = trim($customer_data['addressNumber']);
            }

            if (!empty($customer_data['complement'])) {
                $payload['complement'] = trim($customer_data['complement']);
            }

            // Fix: state should map to province in ASAAS API
            if (!empty($customer_data['state'])) {
                $payload['province'] = trim($customer_data['state']);
            }

            if (!empty($customer_data['city'])) {
                $payload['city'] = trim($customer_data['city']);
            }

            if (!empty($customer_data['postalCode'])) {
                $payload['postalCode'] = $this->sanitize_postal_code($customer_data['postalCode']);
            }

            // Log the request for debugging
            log_activity('ChargeManager: Updating ASAAS customer ' . $customer_id . ' with payload: ' . json_encode($payload));

            $response = $this->make_request('POST', "/customers/{$customer_id}", $payload);

            if ($response['success']) {
                log_activity('ChargeManager: ASAAS customer ' . $customer_id . ' updated successfully');
                return [
                    'success' => true,
                    'message' => 'Cliente atualizado com sucesso'
                ];
            }

            log_activity('ChargeManager: Failed to update ASAAS customer ' . $customer_id . ': ' . $response['message']);
            return [
                'success' => false,
                'message' => $response['message']
            ];

        } catch (Exception $e) {
            log_activity('ChargeManager: Exception updating ASAAS customer ' . $customer_id . ': ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao atualizar cliente: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete customer from ASAAS
     * @param string $customer_id
     * @return array
     */
    public function delete_customer($customer_id)
    {
        try {
            $response = $this->make_request('DELETE', "/customers/{$customer_id}");

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Cliente removido com sucesso'
                ];
            }

            return [
                'success' => false,
                'message' => $response['message']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao remover cliente: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get customer from ASAAS
     * @param string $customer_id
     * @return array
     */
    public function get_customer($customer_id)
    {
        try {
            $response = $this->make_request('GET', "/customers/{$customer_id}");

            if ($response['success']) {
                return [
                    'success' => true,
                    'customer' => $response['data']
                ];
            }

            return [
                'success' => false,
                'message' => $response['message']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao buscar cliente: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create charge in ASAAS
     * @param array $charge_data
     * @return array
     */
    public function create_charge($charge_data)
    {
        try {
            $payload = [
                'customer' => $charge_data['customer'],
                'billingType' => $charge_data['billingType'],
                'value' => $charge_data['value'],
                'dueDate' => $charge_data['dueDate'],
                'description' => $charge_data['description'] ?? 'Cobrança via ChargeManager',
                'externalReference' => $charge_data['externalReference'] ?? null
            ];

            // Adicionar campos opcionais se fornecidos
            if (isset($charge_data['discount'])) {
                $payload['discount'] = $charge_data['discount'];
            }
            
            if (isset($charge_data['fine'])) {
                $payload['fine'] = $charge_data['fine'];
            }
            
            if (isset($charge_data['interest'])) {
                $payload['interest'] = $charge_data['interest'];
            }

            if (isset($charge_data['installmentCount'])) {
                $payload['installmentCount'] = $charge_data['installmentCount'];
                $payload['installmentValue'] = $charge_data['installmentValue'] ?? null;
                $payload['totalValue'] = $charge_data['totalValue'] ?? null;
            }

            $response = $this->make_request('POST', '/payments', $payload);

            if ($response['success']) {
                $data = $response['data'];
                
                // Melhorar tratamento do PIX QR Code
                $pix_code = null;
                if (isset($data['pixTransaction']) && isset($data['pixTransaction']['qrCode'])) {
                    $pix_code = $data['pixTransaction']['qrCode']['payload'] ?? null;
                }
                
                return [
                    'success' => true,
                    'charge_id' => $data['id'],
                    'invoice_url' => $data['invoiceUrl'] ?? null,
                    'barcode' => $data['bankSlipUrl'] ?? null,
                    'pix_code' => $pix_code,
                    'status' => $data['status'] ?? null,
                    'message' => 'Cobrança criada com sucesso'
                ];
            }

            return [
                'success' => false,
                'message' => $response['message']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar cobrança: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get charge from ASAAS
     * @param string $charge_id
     * @return array
     */
    public function get_charge($charge_id)
    {
        try {
            $response = $this->make_request('GET', "/payments/{$charge_id}");

            if ($response['success']) {
                return [
                    'success' => true,
                    'charge' => $response['data']
                ];
            }

            return [
                'success' => false,
                'message' => $response['message']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao buscar cobrança: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel charge in ASAAS
     * @param string $charge_id
     * @return array
     */
    public function cancel_charge($charge_id)
    {
        try {
            $response = $this->make_request('DELETE', "/payments/{$charge_id}");

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Cobrança cancelada com sucesso'
                ];
            }

            return [
                'success' => false,
                'message' => $response['message']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao cancelar cobrança: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update charge in ASAAS
     * @param string $charge_id
     * @param array $charge_data
     * @return array
     */
    public function update_charge($charge_id, $charge_data)
    {
        try {
            $response = $this->make_request('POST', "/payments/{$charge_id}", $charge_data);

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Cobrança atualizada com sucesso'
                ];
            }

            return [
                'success' => false,
                'message' => $response['message']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar cobrança: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate webhook payload
     * @param array $payload
     * @return bool
     */
    public function validate_webhook($payload)
    {
        // Basic validation for ASAAS webhook
        return isset($payload['event']) && isset($payload['payment']);
    }

    /**
     * Process webhook event
     * @param array $payload
     * @return array
     */
    public function process_webhook($payload)
    {
        try {
            if (!$this->validate_webhook($payload)) {
                throw new Exception('Invalid webhook payload');
            }

            // Basic processing - detailed processing is handled in Webhook controller
            return [
                'success' => true,
                'event_type' => $payload['event'],
                'payment_id' => $payload['payment']['id']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Make HTTP request to ASAAS API
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    private function make_request($method, $endpoint, $data = null)
    {
        $url = $this->base_url . $endpoint;
        
        // Log the full request for debugging
        log_activity("ChargeManager: Making {$method} request to {$url}" . ($data ? ' with data: ' . json_encode($data) : ''));
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'access_token: ' . $this->api_key
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        // Log response for debugging
        log_activity("ChargeManager: ASAAS API response - HTTP {$http_code}: " . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : ''));
        
        if ($error) {
            log_activity("ChargeManager: CURL Error: {$error}");
            throw new Exception('CURL Error: ' . $error);
        }
        
        $decoded_response = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            return [
                'success' => true,
                'data' => $decoded_response
            ];
        }
        
        // Detailed error handling for ASAAS API
        $error_message = 'HTTP ' . $http_code;
        if (isset($decoded_response['errors']) && is_array($decoded_response['errors'])) {
            $error_details = [];
            foreach ($decoded_response['errors'] as $error) {
                if (isset($error['description'])) {
                    $error_details[] = $error['description'];
                } elseif (isset($error['code'])) {
                    $error_details[] = $error['code'];
                } else {
                    $error_details[] = 'Unknown error';
                }
            }
            $error_message .= ': ' . implode(', ', $error_details);
        } elseif (isset($decoded_response['message'])) {
            $error_message .= ': ' . $decoded_response['message'];
        }
        
        log_activity("ChargeManager: ASAAS API Error - {$error_message}");
        
        return [
            'success' => false,
            'message' => $error_message,
            'http_code' => $http_code,
            'response' => $decoded_response
        ];
    }

    /**
     * Sanitize document (CPF/CNPJ)
     * @param string $document
     * @return string
     */
    private function sanitize_document($document)
    {
        if (empty($document)) {
            return '';
        }
        return preg_replace('/[^0-9]/', '', $document);
    }

    /**
     * Sanitize phone number
     * @param string $phone
     * @return string
     */
    private function sanitize_phone($phone)
    {
        if (empty($phone)) {
            return '';
        }
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Sanitize postal code
     * @param string $postal_code
     * @return string
     */
    private function sanitize_postal_code($postal_code)
    {
        if (empty($postal_code)) {
            return '';
        }
        return preg_replace('/[^0-9]/', '', $postal_code);
    }
} 