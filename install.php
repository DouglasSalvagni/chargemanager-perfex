<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$db_prefix = db_prefix();
$charset = $CI->db->char_set;

// 1. chargemanager_billing_groups - Conforme relatório
if (!$CI->db->table_exists($db_prefix . 'chargemanager_billing_groups')) {
    $CI->db->query('CREATE TABLE `' . $db_prefix . "chargemanager_billing_groups` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `client_id` INT(11) NOT NULL,
        `contract_id` INT(11) NOT NULL,
        `invoice_id` INT(11) NULL,
        `status` VARCHAR(50) NOT NULL DEFAULT 'open',
        `total_amount` DECIMAL(15,2) NOT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `client_id` (`client_id`),
        KEY `contract_id` (`contract_id`),
        KEY `invoice_id` (`invoice_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . ";");
}

// 2. chargemanager_charges - Conforme relatório
if (!$CI->db->table_exists($db_prefix . 'chargemanager_charges')) {
    $CI->db->query('CREATE TABLE `' . $db_prefix . "chargemanager_charges` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `perfex_invoice_id` INT(11),
        `gateway_charge_id` VARCHAR(64) NOT NULL,
        `gateway` VARCHAR(50) NULL,
        `billing_group_id` INT(11) NULL,
        `payment_record_id` INT(11) NULL,
        `client_id` INT(11) NOT NULL,
        `value` DECIMAL(15, 2) NOT NULL,
        `due_date` DATE NOT NULL,
        `billing_type` VARCHAR(20) NOT NULL,
        `status` VARCHAR(20) NOT NULL,
        `invoice_url` VARCHAR(255) DEFAULT NULL,
        `barcode` VARCHAR(255) DEFAULT NULL,
        `pix_code` TEXT DEFAULT NULL,
        `paid_at` DATETIME NULL,
        `paid_amount` DECIMAL(15,2) NULL,
        `payment_method` VARCHAR(50) NULL,
        `description` TEXT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `perfex_invoice_id` (`perfex_invoice_id`),
        KEY `client_id` (`client_id`),
        KEY `gateway_charge_id` (`gateway_charge_id`),
        KEY `billing_group_id` (`billing_group_id`),
        KEY `payment_record_id` (`payment_record_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . ";");
}

// 3. chargemanager_entity_mappings - Conforme relatório  
if (!$CI->db->table_exists($db_prefix . 'chargemanager_entity_mappings')) {
    $CI->db->query('CREATE TABLE `' . $db_prefix . "chargemanager_entity_mappings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `perfex_entity_type` VARCHAR(50) NOT NULL,
        `perfex_entity_id` INT(11) NOT NULL,
        `gateway` VARCHAR(50) NOT NULL DEFAULT 'asaas',
        `gateway_entity_type` VARCHAR(50) NOT NULL,
        `gateway_entity_id` VARCHAR(64) NOT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_mapping` (`perfex_entity_type`, `perfex_entity_id`, `gateway`, `gateway_entity_type`),
        KEY `gateway_entity` (`gateway`, `gateway_entity_type`, `gateway_entity_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . ";");
}

// 4. chargemanager_webhook_queue - Conforme relatório
if (!$CI->db->table_exists($db_prefix . 'chargemanager_webhook_queue')) {
    $CI->db->query('CREATE TABLE `' . $db_prefix . "chargemanager_webhook_queue` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `gateway` VARCHAR(50) NOT NULL DEFAULT 'asaas',
        `event_type` VARCHAR(100) NOT NULL,
        `payload` JSON NOT NULL,
        `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
        `attempts` INT(11) NOT NULL DEFAULT 0,
        `max_attempts` INT(11) NOT NULL DEFAULT 3,
        `error_message` TEXT NULL,
        `created_at` DATETIME NOT NULL,
        `processed_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `gateway` (`gateway`),
        KEY `status` (`status`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . ";");
}

// 5. chargemanager_sync_logs - Conforme relatório
if (!$CI->db->table_exists($db_prefix . 'chargemanager_sync_logs')) {
    $CI->db->query('CREATE TABLE `' . $db_prefix . "chargemanager_sync_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `event_type` VARCHAR(100) NOT NULL,
        `gateway` VARCHAR(50) NULL DEFAULT 'asaas',
        `perfex_entity_type` VARCHAR(50) NULL,
        `perfex_entity_id` INT(11) NULL,
        `gateway_entity_id` VARCHAR(64) NULL,
        `status` ENUM('success', 'error') NOT NULL,
        `message` TEXT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `event_type` (`event_type`),
        KEY `gateway` (`gateway`),
        KEY `status` (`status`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . ";");
}

// 6. chargemanager_asaas_settings - Conforme relatório
if (!$CI->db->table_exists($db_prefix . 'chargemanager_asaas_settings')) {
    $CI->db->query('CREATE TABLE `' . $db_prefix . "chargemanager_asaas_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `value` TEXT,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . ";");
}

// Inserir configurações padrão do ASAAS
$default_settings = [
    ['name' => 'api_key', 'value' => ''],
    ['name' => 'environment', 'value' => 'sandbox'],
    ['name' => 'webhook_token', 'value' => ''],
    ['name' => 'enabled', 'value' => '0']
];

foreach ($default_settings as $setting) {
    $CI->db->where('name', $setting['name']);
    $existing = $CI->db->get($db_prefix . 'chargemanager_asaas_settings')->row();
    
    if (!$existing) {
        $CI->db->insert($db_prefix . 'chargemanager_asaas_settings', $setting);
    }
}

// Log da instalação
log_activity('ChargeManager module installed successfully'); 