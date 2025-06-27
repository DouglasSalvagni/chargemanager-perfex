<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Module General
$lang['chargemanager'] = 'ChargeManager';
$lang['chargemanager_settings'] = 'ChargeManager Settings';
$lang['chargemanager_billing_groups'] = 'Billing Groups';

// Menu
$lang['chargemanager_menu_settings'] = 'ChargeManager - Settings';

// Settings
$lang['chargemanager_asaas_settings'] = 'ASAAS Settings';
$lang['chargemanager_general_settings'] = 'General Settings';
$lang['chargemanager_api_key'] = 'API Key';
$lang['chargemanager_api_key_placeholder'] = 'Enter your ASAAS API key';
$lang['chargemanager_api_key_required'] = 'API key is required';
$lang['chargemanager_api_key_minlength'] = 'API key must be at least 10 characters';
$lang['chargemanager_environment'] = 'Environment';
$lang['chargemanager_sandbox'] = 'Sandbox (Test)';
$lang['chargemanager_production'] = 'Production';
$lang['chargemanager_environment_help'] = 'Use Sandbox for testing and Production for real transactions';
$lang['chargemanager_webhook_url'] = 'Webhook URL';
$lang['chargemanager_webhook_help'] = 'Configure this URL in ASAAS panel to receive payment notifications';
$lang['chargemanager_show_hide'] = 'Show/Hide';
$lang['chargemanager_save_settings'] = 'Save Settings';
$lang['chargemanager_test_connection'] = 'Test Connection';
$lang['chargemanager_clear_logs'] = 'Clear Logs';
$lang['chargemanager_click_test_connection'] = 'Click "Test Connection" to verify your settings';
$lang['chargemanager_testing'] = 'Testing...';
$lang['chargemanager_connection_failed'] = 'Connection to ASAAS failed';
$lang['chargemanager_confirm_clear_logs'] = 'Are you sure you want to clear all logs?';
$lang['chargemanager_copied_to_clipboard'] = 'Copied to clipboard';

// General Settings
$lang['chargemanager_auto_sync_clients'] = 'Auto Sync Clients';
$lang['chargemanager_auto_sync_clients_help'] = 'Automatically sync clients with gateway';
$lang['chargemanager_auto_create_invoices'] = 'Auto Create Invoices';
$lang['chargemanager_auto_create_invoices_help'] = 'Automatically create invoices when payments are received';
$lang['chargemanager_debug_mode'] = 'Debug Mode';
$lang['chargemanager_debug_mode_help'] = 'Log detailed information for debugging';
$lang['chargemanager_default_billing_type'] = 'Default Billing Type';

// Billing Types
$lang['chargemanager_billing_type'] = 'Billing Type';
$lang['chargemanager_billing_type_boleto'] = 'Bank Slip (Boleto)';
$lang['chargemanager_billing_type_pix'] = 'PIX';
$lang['chargemanager_billing_type_credit_card'] = 'Credit Card';
$lang['chargemanager_select_billing_type'] = 'Select billing type';

// Billing Groups
$lang['chargemanager_create_billing_group'] = 'Create Billing Group';
$lang['chargemanager_billing_group_name'] = 'Group Name';
$lang['chargemanager_total_value'] = 'Total Value';
$lang['chargemanager_due_date'] = 'Due Date';
$lang['chargemanager_description'] = 'Description';
$lang['chargemanager_description_placeholder'] = 'Optional billing group description';
$lang['chargemanager_contracts'] = 'Contracts';
$lang['chargemanager_loading_contracts'] = 'Loading contracts...';
$lang['chargemanager_select_contracts_help'] = 'Select contracts that will be part of this group';
$lang['chargemanager_selected_contracts'] = 'Selected Contracts';
$lang['chargemanager_no_contracts_available'] = 'No contracts available for this client';
$lang['chargemanager_select_at_least_one_contract'] = 'Select at least one contract';

// Billing Group Status
$lang['chargemanager_status'] = 'Status';
$lang['chargemanager_status_open'] = 'Open';
$lang['chargemanager_status_processing'] = 'Processing';
$lang['chargemanager_status_completed'] = 'Completed';
$lang['chargemanager_status_partial'] = 'Partial';
$lang['chargemanager_status_paid'] = 'Paid';
$lang['chargemanager_status_cancelled'] = 'Cancelled';

// Actions
$lang['chargemanager_actions'] = 'Actions';
$lang['chargemanager_view'] = 'View';
$lang['chargemanager_cancel'] = 'Cancel';
$lang['chargemanager_confirm_cancel_billing_group'] = 'Are you sure you want to cancel this billing group?';

// Billing Group Details
$lang['chargemanager_billing_group_details'] = 'Billing Group Details';
$lang['chargemanager_billing_group_info'] = 'Group Information';
$lang['chargemanager_client'] = 'Client';
$lang['chargemanager_created_at'] = 'Created at';
$lang['chargemanager_payment_summary'] = 'Payment Summary';
$lang['chargemanager_total_charges'] = 'Total Charges';
$lang['chargemanager_total_paid'] = 'Total Paid';
$lang['chargemanager_remaining'] = 'Remaining';
$lang['chargemanager_progress'] = 'Progress';
$lang['chargemanager_associated_contracts'] = 'Associated Contracts';
$lang['chargemanager_no_contracts_associated'] = 'No contracts associated';

// Charges
$lang['chargemanager_charges'] = 'Charges';
$lang['chargemanager_charge_id'] = 'Charge ID';
$lang['chargemanager_value'] = 'Value';
$lang['chargemanager_payment_date'] = 'Payment Date';
$lang['chargemanager_invoice'] = 'Invoice';
$lang['chargemanager_no_charges_found'] = 'No charges found';
$lang['chargemanager_view_invoice'] = 'View Invoice';
$lang['chargemanager_view_barcode'] = 'View Barcode';
$lang['chargemanager_view_pix_code'] = 'View PIX Code';

// Charge Status
$lang['chargemanager_charge_status_pending'] = 'Pending';
$lang['chargemanager_charge_status_received'] = 'Received';
$lang['chargemanager_charge_status_overdue'] = 'Overdue';
$lang['chargemanager_charge_status_cancelled'] = 'Cancelled';

// Payment Details
$lang['chargemanager_barcode'] = 'Barcode';
$lang['chargemanager_barcode_number'] = 'Barcode Number';
$lang['chargemanager_pix_code'] = 'PIX Code';
$lang['chargemanager_pix_copy_paste'] = 'PIX Code (Copy & Paste)';

// Logs
$lang['chargemanager_recent_logs'] = 'Recent Logs';
$lang['chargemanager_activity_log'] = 'Activity Log';
$lang['chargemanager_log_date'] = 'Date';
$lang['chargemanager_log_type'] = 'Type';
$lang['chargemanager_log_message'] = 'Message';
$lang['chargemanager_log_status'] = 'Status';
$lang['chargemanager_no_logs'] = 'No logs found';
$lang['chargemanager_success'] = 'Success';
$lang['chargemanager_error'] = 'Error';

// Messages
$lang['chargemanager_settings_saved'] = 'Settings saved successfully';
$lang['chargemanager_billing_group_created'] = 'Billing group created successfully';
$lang['chargemanager_billing_group_cancelled'] = 'Billing group cancelled successfully';
$lang['chargemanager_connection_successful'] = 'Connection established successfully';
$lang['chargemanager_logs_cleared'] = 'Logs cleared successfully';

// Errors
$lang['chargemanager_error_creating_billing_group'] = 'Error creating billing group';
$lang['chargemanager_error_cancelling_billing_group'] = 'Error cancelling billing group';
$lang['chargemanager_error_loading_data'] = 'Error loading data';
$lang['chargemanager_error_saving_settings'] = 'Error saving settings';
$lang['chargemanager_error_testing_connection'] = 'Error testing connection';

// Validation
$lang['chargemanager_validation_required'] = 'This field is required';
$lang['chargemanager_validation_numeric'] = 'This field must be numeric';
$lang['chargemanager_validation_email'] = 'Invalid email format';
$lang['chargemanager_validation_date'] = 'Invalid date';
$lang['chargemanager_validation_min_value'] = 'Value must be greater than zero';

// Permissions
$lang['chargemanager_permission_view'] = 'View ChargeManager';
$lang['chargemanager_permission_create'] = 'Create Billing Groups';
$lang['chargemanager_permission_edit'] = 'Edit Billing Groups';
$lang['chargemanager_permission_delete'] = 'Delete Billing Groups';
$lang['chargemanager_permission_settings'] = 'Configure ChargeManager';

// Webhooks
$lang['chargemanager_webhook_received'] = 'Webhook received';
$lang['chargemanager_webhook_processed'] = 'Webhook processed successfully';
$lang['chargemanager_webhook_failed'] = 'Failed to process webhook';
$lang['chargemanager_webhook_invalid'] = 'Invalid webhook';

// Client Tab
$lang['chargemanager_client_tab'] = 'Billing Groups';
$lang['chargemanager_client_tab_help'] = 'Manage billing groups for this client';

// New Billing Group Form
$lang['chargemanager_existing_billing_groups'] = 'Existing Billing Groups';
$lang['chargemanager_new_billing_group'] = 'New Billing Group';
$lang['chargemanager_contract'] = 'Contract';
$lang['chargemanager_contract_value'] = 'Contract Value';
$lang['chargemanager_add_charge'] = 'Add Charge';
$lang['chargemanager_charge'] = 'Charge';
$lang['chargemanager_amount'] = 'Amount';
$lang['chargemanager_difference'] = 'Difference';
$lang['chargemanager_validation_status'] = 'Validation Status';
$lang['chargemanager_pending_validation'] = 'Pending Validation';

// Table Headers
$lang['chargemanager_id'] = 'ID';
$lang['chargemanager_options'] = 'Options';

// Validation Messages
$lang['chargemanager_client_id_required'] = 'Client ID is required';
$lang['chargemanager_contract_required'] = 'Contract is required';
$lang['chargemanager_contract_not_belongs_client'] = 'Contract does not belong to selected client';
$lang['chargemanager_contract_already_in_billing_group'] = 'Contract is already in another active billing group';
$lang['chargemanager_charges_required'] = 'At least one charge is required';
$lang['chargemanager_charge_amount_invalid'] = 'Charge %d: Amount is required and must be greater than 0';
$lang['chargemanager_charge_due_date_invalid'] = 'Charge %d: Due date is required';
$lang['chargemanager_charge_billing_type_invalid'] = 'Charge %d: Billing type is required';
$lang['chargemanager_total_amount_mismatch'] = 'Total charges must equal contract value';

// New validation messages for improved billing group creation
$lang['chargemanager_error_no_charges_provided'] = 'No charges were provided';
$lang['chargemanager_error_due_date_required'] = 'Charge %d: Due date is required';
$lang['chargemanager_error_due_date_past'] = 'Charge %d: Due date (%s) cannot be in the past';
$lang['chargemanager_error_invalid_amount'] = 'Charge %d: Amount must be a valid number and greater than zero';
$lang['chargemanager_error_billing_type_required'] = 'Charge %d: Billing type is required';
$lang['chargemanager_error_invalid_billing_type'] = 'Charge %d: Billing type must be BOLETO, PIX or CREDIT_CARD';
$lang['chargemanager_error_saving_charge_to_db_number'] = 'Error saving charge %d to database';

// Entry Charge Feature
$lang['chargemanager_entry_charge'] = 'Entry Charge';
$lang['chargemanager_entry'] = 'Entry';
$lang['chargemanager_yes'] = 'Yes';
$lang['chargemanager_no'] = 'No';
$lang['chargemanager_set_entry'] = 'Set Entry';
$lang['chargemanager_set_as_entry'] = 'Set as Entry Charge';
$lang['chargemanager_confirm_set_entry_charge'] = 'Are you sure you want to set this charge as the entry charge? This will remove the entry flag from other charges.';
$lang['chargemanager_entry_charge_set'] = 'Entry charge set successfully';
$lang['chargemanager_entry_charge_auto_assigned'] = 'Entry charge automatically assigned to the earliest due date charge';
$lang['chargemanager_confirm_delete_charge'] = 'Are you sure you want to delete this charge?';
$lang['chargemanager_update_charge'] = 'Update Charge';
$lang['chargemanager_edit_charge'] = 'Edit Charge';
$lang['chargemanager_error_creating_charge_number'] = 'Error creating charge %d in gateway: %s';
$lang['chargemanager_error_charge_exception'] = 'Unexpected error in charge %d: %s';
$lang['chargemanager_error_no_charges_created'] = 'No charges were created successfully. Billing group was removed.';
$lang['chargemanager_error_unexpected'] = 'Unexpected error';
$lang['chargemanager_billing_group_created_successfully'] = 'Billing group created successfully';
$lang['chargemanager_billing_group_created_successfully_with_id'] = 'Billing group #%d created successfully';
$lang['chargemanager_charges_created_count'] = '%d charge(s) created successfully';
$lang['chargemanager_charges_failed_count'] = '%d charge(s) failed';

// Invoice Generation Messages
$lang['chargemanager_invoice_created_with_id'] = 'Invoice #%d created successfully';
$lang['chargemanager_invoice_generation_failed_but_charges_created'] = 'Charges created, but there was a problem generating the invoice (will be retried)';
$lang['chargemanager_billing_group_incomplete_data'] = 'Incomplete billing group data (client or contract missing)';
$lang['chargemanager_invoice_already_exists'] = 'Invoice already exists for this billing group';
$lang['chargemanager_error_creating_invoice'] = 'Error creating invoice';
$lang['chargemanager_invoice_created_successfully'] = 'Invoice created successfully';
$lang['chargemanager_charge_description'] = 'Charge %s - Due: %s';
$lang['chargemanager_view_billing_group'] = 'View Billing Group';

// Contract Loading Messages
$lang['chargemanager_contracts_loaded_successfully'] = 'Contracts loaded successfully';
$lang['chargemanager_no_available_contracts'] = 'No contracts available for this client';
$lang['chargemanager_error_loading_contracts'] = 'Error loading contracts';
$lang['chargemanager_contract_id_required'] = 'Contract ID is required';
$lang['chargemanager_contract_not_found'] = 'Contract not found';
$lang['chargemanager_contract_invalid'] = 'Invalid contract (not signed, expired or no value)';
$lang['chargemanager_error_loading_contract'] = 'Error loading contract details';

// Sale Agent related translations
$lang['chargemanager_sale_agent'] = 'Sale Agent';
$lang['chargemanager_select_sale_agent'] = 'Select Sale Agent';
$lang['chargemanager_no_sale_agent'] = 'No Sale Agent';
$lang['chargemanager_sale_agent_required'] = 'Sale Agent is required';
$lang['chargemanager_invalid_sale_agent'] = 'Invalid Sale Agent selected';
$lang['chargemanager_sale_agent_info'] = 'Sale Agent Information';
$lang['chargemanager_assigned_to'] = 'Assigned to';

// Additional translations for edit view
$lang['chargemanager_basic_information'] = 'Basic Information';
$lang['chargemanager_update_basic_info'] = 'Update Basic Info';
$lang['chargemanager_status_open'] = 'Open';
$lang['chargemanager_status_partial'] = 'Partial';
$lang['chargemanager_status_completed'] = 'Completed';
$lang['chargemanager_status_overdue'] = 'Overdue';
$lang['chargemanager_status_cancelled'] = 'Cancelled';

// Original Lead Agent
$lang['chargemanager_original_lead_agent'] = 'Original Lead Agent'; 