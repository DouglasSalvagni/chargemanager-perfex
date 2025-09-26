<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: ChargeManager  
Description: Sistema simplificado de cobrança com integração ASAAS para Perfex CRM
Version: 1.0.0
Author: ChargeManager Team
*/

define('CHARGEMANAGER_MODULE_NAME', 'chargemanager');

// Register activation hook
register_activation_hook(CHARGEMANAGER_MODULE_NAME, 'chargemanager_module_activation_hook');

function chargemanager_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

$CI = &get_instance();

/**
 * Load the module helpers
 */
$CI->load->helper(CHARGEMANAGER_MODULE_NAME . '/chargemanager');
$CI->load->helper(CHARGEMANAGER_MODULE_NAME . '/contract_billing_schema');

hooks()->add_action('app_init', 'chargemanager_load_translations');

function chargemanager_load_translations()
{
    $CI = &get_instance();

    $language = $GLOBALS['language'];

    // Se o idioma for português Brasil, carrega a variante correta
    if ($language == 'portuguese_br') {
        $language = 'portuguese_br';
    } else if ($language == 'portuguese') {
        $language = 'portuguese';
    }

    $CI->lang->load('chargemanager_lang', $language, false, true, FCPATH . 'modules/chargemanager/');
}

// Add admin menu items - SIMPLIFICADO conforme relatório
hooks()->add_action('admin_init', 'chargemanager_module_init_menu_items');

function chargemanager_module_init_menu_items()
{
    $CI = &get_instance();

    if (is_admin()) {
        $CI->app_menu->add_sidebar_menu_item('chargemanager', [
            'name'     => _l('chargemanager'),
            'href'     => admin_url('chargemanager/settings'),
            'icon'     => 'fa fa-credit-card',
            'position' => 15,
        ]);

        // Apenas Settings conforme relatório
        $CI->app_menu->add_sidebar_children_item('chargemanager', [
            'slug'     => 'chargemanager-settings',
            'name'     => _l('chargemanager_settings'),
            'href'     => admin_url('chargemanager/settings'),
            'position' => 1,
        ]);
    }
}

// Add permissions
hooks()->add_action('admin_init', 'chargemanager_permissions');

function chargemanager_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'     => _l('permission_view') . ' (' . _l('permission_global') . ')',
        'view_own' => _l('permission_view_own'),
        'create'   => _l('permission_create'),
        'edit'     => _l('permission_edit'),
        'delete'   => _l('permission_delete')
    ];

    register_staff_capabilities('chargemanager', $capabilities, _l('chargemanager'));
}

// Client module integration - mantido conforme relatório
hooks()->add_action('after_client_added', 'chargemanager_after_client_added');
hooks()->add_action('client_updated', 'chargemanager_after_client_updated');
hooks()->add_action('after_client_deleted', 'chargemanager_after_client_deleted');


function chargemanager_after_client_added($client_id)
{
    $CI = &get_instance();
    $CI->load->library(CHARGEMANAGER_MODULE_NAME . '/Gateway_manager');

    try {
        // Create customer using unified Gateway Manager
        $CI->gateway_manager->get_or_create_customer($client_id);
    } catch (Exception $e) {
        // Log error but don't break the client creation process
        log_activity('ChargeManager: Failed to sync new client to gateway - ' . $e->getMessage());
    }
}

function chargemanager_after_client_updated($client_id)
{
    $CI = &get_instance();
    $CI->load->library(CHARGEMANAGER_MODULE_NAME . '/Gateway_manager');

    try {
        // Update customer using unified Gateway Manager
        $CI->gateway_manager->update_customer($client_id);
    } catch (Exception $e) {
        // Log error but don't break the client update process
        log_activity('ChargeManager: Failed to sync updated client to gateway - ' . $e->getMessage());
    }
}

function chargemanager_after_client_deleted($client_id)
{
    $CI = &get_instance();
    $CI->load->library(CHARGEMANAGER_MODULE_NAME . '/Gateway_manager');

    try {
        // Delete customer using unified Gateway Manager
        $CI->gateway_manager->delete_customer($client_id);
    } catch (Exception $e) {
        // Log error but don't break the client deletion process
        log_activity('ChargeManager: Failed to delete client from gateway - ' . $e->getMessage());
    }
}

// Add billing groups tab to client profile - mantido conforme relatório
hooks()->add_filter('customer_profile_tabs', 'chargemanager_add_billing_groups_tab');

function chargemanager_add_billing_groups_tab($tabs)
{
    $CI = &get_instance();

    // Only show tab if user has permission to view chargemanager
    if (has_permission('chargemanager', '', 'view')) {
        $tabs['billing_groups'] = [
            'slug'     => 'billing_groups',
            'name' => _l('chargemanager_billing_groups'),
            'icon' => 'fa fa-credit-card',
            'view' => 'chargemanager/admin/client/billing_groups_tab',
            'position' => 15,
        ];
    }

    return $tabs;
}



// Webhook handling - mantido conforme relatório
hooks()->add_action('app_init', 'chargemanager_handle_webhooks');

function chargemanager_handle_webhooks()
{
    $CI = &get_instance();

    // Check if this is a webhook request
    if (strpos($CI->uri->uri_string(), 'chargemanager/webhook') !== false) {
        // Remove output buffering for webhooks
        if (ob_get_level()) {
            ob_end_clean();
        }
    }
}

// Load admin assets
hooks()->add_action('app_admin_head', 'chargemanager_load_admin_assets');

function chargemanager_load_admin_assets()
{
    $CI = &get_instance();

    // Load assets only on relevant pages
    $uri = $CI->uri->uri_string();
    $load_assets = false;

    // Load on chargemanager pages
    if (strpos($uri, 'chargemanager') !== false) {
        $load_assets = true;
    }

    // Load on client pages for billing groups tab
    if (strpos($uri, 'clients/client/') !== false) {
        $load_assets = true;
    }

    if ($load_assets) {
        echo '<link href="' . module_dir_url(CHARGEMANAGER_MODULE_NAME, 'assets/css/chargemanager.css') . '" rel="stylesheet" type="text/css" />';
        echo '<style>
        /* Additional enhanced status styling */
        .label-billing-status { display: inline-flex; align-items: center; gap: 5px; }
        .status-tooltip { cursor: help; }
        </style>';
    }
}

// Payment processing - para payments records conforme relatório
hooks()->add_action('after_payment_added', 'chargemanager_after_payment_added');

function chargemanager_after_payment_added($payment_id)
{
    // Process payment linking to charges if needed
    $CI = &get_instance();
    $CI->load->model('chargemanager/chargemanager_charges_model');

    try {
        $CI->chargemanager_charges_model->link_payment_to_charges($payment_id);
    } catch (Exception $e) {
        log_activity('ChargeManager: Failed to link payment to charges - ' . $e->getMessage());
    }
}


hooks()->add_action('app_init', 'contract_table_filters');

function contract_table_filters()
{
    $CI = &get_instance();
    // require_once(__DIR__ . '/contract-table-filters.php');
}
