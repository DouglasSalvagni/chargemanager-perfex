<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Gateway Interface
 * Interface padrão para implementação de gateways de pagamento
 */
interface Gateway_interface
{
    /**
     * Test connection to gateway
     * @return array
     */
    public function test_connection();

    /**
     * Validate gateway configuration
     * @return bool
     */
    public function validate_config();

    /**
     * Get supported billing types
     * @return array
     */
    public function get_supported_billing_types();

    /**
     * Create customer in gateway
     * @param array $customer_data
     * @return array
     */
    public function create_customer($customer_data);

    /**
     * Update customer in gateway
     * @param string $customer_id
     * @param array $customer_data
     * @return array
     */
    public function update_customer($customer_id, $customer_data);

    /**
     * Delete customer from gateway
     * @param string $customer_id
     * @return array
     */
    public function delete_customer($customer_id);

    /**
     * Get customer from gateway
     * @param string $customer_id
     * @return array
     */
    public function get_customer($customer_id);

    /**
     * Create charge in gateway
     * @param array $charge_data
     * @return array
     */
    public function create_charge($charge_data);

    /**
     * Get charge from gateway
     * @param string $charge_id
     * @return array
     */
    public function get_charge($charge_id);

    /**
     * Cancel charge in gateway
     * @param string $charge_id
     * @return array
     */
    public function cancel_charge($charge_id);

    /**
     * Update charge in gateway
     * @param string $charge_id
     * @param array $charge_data
     * @return array
     */
    public function update_charge($charge_id, $charge_data);

    /**
     * Validate webhook payload
     * @param array $payload
     * @return bool
     */
    public function validate_webhook($payload);

    /**
     * Process webhook event
     * @param array $payload
     * @return array
     */
    public function process_webhook($payload);
} 