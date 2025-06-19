<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Gateway Factory
 * Factory pattern para criar instâncias de gateways
 */
class Gateway_factory
{
    /**
     * Create gateway instance
     * @param string $gateway_name
     * @param array $custom_config
     * @return Gateway_interface|null
     */
    public static function create($gateway_name, $custom_config = null)
    {
        try {
            $gateway_class = ucfirst(strtolower($gateway_name)) . '_gateway';
            $gateway_file = __DIR__ . '/' . $gateway_class . '.php';

            if (!file_exists($gateway_file)) {
                throw new Exception('Gateway file not found: ' . $gateway_file);
            }

            // Load interface
            require_once __DIR__ . '/Gateway_interface.php';
            
            // Load gateway class
            require_once $gateway_file;

            if (!class_exists($gateway_class)) {
                throw new Exception('Gateway class not found: ' . $gateway_class);
            }

            // Create instance
            $gateway_instance = new $gateway_class($custom_config);

            // Verify interface implementation
            if (!($gateway_instance instanceof Gateway_interface)) {
                throw new Exception('Gateway must implement Gateway_interface');
            }

            return $gateway_instance;

        } catch (Exception $e) {
            log_activity('Gateway Factory Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get available gateways
     * @return array
     */
    public static function get_available_gateways()
    {
        return [
            'asaas' => [
                'name' => 'ASAAS',
                'description' => 'Gateway de pagamento ASAAS',
                'implemented' => true,
                'supported_types' => ['BOLETO', 'CREDIT_CARD', 'PIX'],
                'website' => 'https://www.asaas.com'
            ],
            'mercadopago' => [
                'name' => 'Mercado Pago',
                'description' => 'Gateway Mercado Pago (Futuro)',
                'implemented' => false,
                'supported_types' => [],
                'website' => 'https://www.mercadopago.com.br'
            ],
            'pagseguro' => [
                'name' => 'PagSeguro',
                'description' => 'Gateway PagSeguro (Futuro)',
                'implemented' => false,
                'supported_types' => [],
                'website' => 'https://pagseguro.uol.com.br'
            ]
        ];
    }

    /**
     * Check if gateway is available
     * @param string $gateway_name
     * @return bool
     */
    public static function is_available($gateway_name)
    {
        $gateways = self::get_available_gateways();
        return isset($gateways[$gateway_name]) && $gateways[$gateway_name]['implemented'];
    }

    /**
     * Get implemented gateways only
     * @return array
     */
    public static function get_implemented_gateways()
    {
        $all_gateways = self::get_available_gateways();
        return array_filter($all_gateways, function($gateway) {
            return $gateway['implemented'];
        });
    }

    /**
     * Get gateway supported billing types
     * @param string $gateway_name
     * @return array
     */
    public static function get_gateway_billing_types($gateway_name)
    {
        $gateways = self::get_available_gateways();
        
        if (!isset($gateways[$gateway_name])) {
            return [];
        }

        return $gateways[$gateway_name]['supported_types'];
    }
} 