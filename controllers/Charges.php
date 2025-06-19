<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Charges extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('chargemanager_charges_model');
    }

    /**
     * Edit charge form and processing
     */
    public function edit($charge_id = null)
    {
        if (!has_permission('chargemanager', '', 'edit')) {
            access_denied('chargemanager edit');
        }

        if (!$charge_id) {
            show_404();
        }

        // Get charge data
        $charge = $this->chargemanager_charges_model->get($charge_id);
        
        if (!$charge) {
            show_404();
        }

        // Process form submission
        if ($this->input->post()) {
            $this->handle_edit_submission($charge_id);
            return;
        }

        // Load form data
        $data['charge'] = $charge;
        $data['title'] = _l('chargemanager_edit_charge');
        
        // Get client data
        $this->load->model('clients_model');
        $data['client'] = $this->clients_model->get($charge->client_id);
        
        // Get billing group if exists
        if (!empty($charge->billing_group_id)) {
            $this->load->model('chargemanager_billing_groups_model');
            $data['billing_group'] = $this->chargemanager_billing_groups_model->get($charge->billing_group_id);
        }

        // Load billing types
        $data['billing_types'] = [
            'BOLETO' => _l('chargemanager_billing_type_boleto'),
            'PIX' => _l('chargemanager_billing_type_pix'),
            'CREDIT_CARD' => _l('chargemanager_billing_type_credit_card')
        ];

        $this->load->view('admin/charges/edit', $data);
    }

    /**
     * Handle edit form submission
     */
    private function handle_edit_submission($charge_id)
    {
        // Validate form data
        $this->form_validation->set_rules('value', _l('chargemanager_charge_value'), 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('due_date', _l('chargemanager_due_date'), 'required');
        $this->form_validation->set_rules('billing_type', _l('chargemanager_billing_type'), 'required|in_list[BOLETO,PIX,CREDIT_CARD]');

        if (!$this->form_validation->run()) {
            return;
        }

        // Prepare update data
        $update_data = [
            'value' => $this->input->post('value'),
            'due_date' => $this->input->post('due_date'),
            'billing_type' => $this->input->post('billing_type'),
            'description' => $this->input->post('description')
        ];

        // Options
        $options = [
            'allow_past_due_date' => $this->input->post('allow_past_due_date') ? true : false
        ];

        // Update charge using the enhanced update method
        $result = $this->chargemanager_charges_model->update($charge_id, $update_data, $options);

        if ($result['success']) {
            set_alert('success', $result['message']);
            
            // Add additional success info
            if ($result['invoice_updated']) {
                set_alert('info', _l('chargemanager_related_invoice_updated'));
            }
        } else {
            set_alert('danger', $result['message']);
        }

        redirect(admin_url('chargemanager/charges/view/' . $charge_id));
    }

    /**
     * View charge details
     */
    public function view($charge_id = null)
    {
        if (!has_permission('chargemanager', '', 'view')) {
            access_denied('chargemanager view');
        }

        if (!$charge_id) {
            show_404();
        }

        // Get charge with relationships
        $charge = $this->chargemanager_charges_model->get_charge_with_relationships($charge_id);
        
        if (!$charge) {
            show_404();
        }

        $data['charge'] = $charge;
        $data['title'] = _l('chargemanager_view_charge');
        
        // Get client data
        $this->load->model('clients_model');
        $data['client'] = $this->clients_model->get($charge->client_id);
        
        // Get invoice if exists
        if (!empty($charge->perfex_invoice_id)) {
            $this->load->model('invoices_model');
            $data['invoice'] = $this->invoices_model->get($charge->perfex_invoice_id);
        }

        $this->load->view('admin/charges/view', $data);
    }

    /**
     * AJAX endpoint for quick edit
     */
    public function ajax_quick_edit()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'edit')) {
            ajax_access_denied();
        }

        $charge_id = $this->input->post('charge_id');
        $field = $this->input->post('field');
        $value = $this->input->post('value');

        if (!$charge_id || !$field || $value === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }

        // Validate field
        $allowed_fields = ['value', 'due_date', 'billing_type', 'description'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['success' => false, 'message' => 'Field not allowed for quick edit']);
            return;
        }

        // Update charge
        $result = $this->chargemanager_charges_model->update($charge_id, [$field => $value]);

        echo json_encode($result);
    }

    /**
     * Cancel charge
     */
    public function cancel($charge_id = null)
    {
        if (!has_permission('chargemanager', '', 'delete')) {
            access_denied('chargemanager delete');
        }

        if (!$charge_id) {
            show_404();
        }

        $reason = $this->input->post('cancellation_reason') ?: 'Cancelled by user';
        
        $result = $this->chargemanager_charges_model->cancel_charge($charge_id, $reason, get_staff_user_id());

        if ($result['success']) {
            set_alert('success', $result['message']);
        } else {
            set_alert('danger', $result['message']);
        }

        redirect(admin_url('chargemanager/charges/view/' . $charge_id));
    }

    /**
     * List charges with filters
     */
    public function index()
    {
        if (!has_permission('chargemanager', '', 'view')) {
            access_denied('chargemanager view');
        }

        // Get filter parameters
        $client_id = $this->input->get('client_id');
        $status = $this->input->get('status');
        $billing_group_id = $this->input->get('billing_group_id');

        $data['title'] = _l('chargemanager_charges');
        $data['client_id'] = $client_id;
        $data['status'] = $status;
        $data['billing_group_id'] = $billing_group_id;

        // Load clients for filter
        $this->load->model('clients_model');
        $data['clients'] = $this->clients_model->get();

        // Load billing groups for filter
        $this->load->model('chargemanager_billing_groups_model');
        $data['billing_groups'] = $this->chargemanager_billing_groups_model->get_all();

        $this->load->view('admin/charges/list', $data);
    }

    /**
     * AJAX table for charges listing
     */
    public function ajax_table()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('chargemanager', '', 'view')) {
            ajax_access_denied();
        }

        // Table columns
        $aColumns = [
            'c.id',
            'c.client_id',
            'c.value',
            'c.due_date',
            'c.billing_type',
            'c.status',
            'c.created_at'
        ];

        $sIndexColumn = 'c.id';
        $sTable = db_prefix() . 'chargemanager_charges c';

        // JOINs
        $join = [
            'LEFT JOIN ' . db_prefix() . 'clients cl ON cl.userid = c.client_id'
        ];

        // WHERE conditions
        $where = [];

        // Apply filters
        $client_id = $this->input->get('client_id');
        if ($client_id) {
            $where[] = 'AND c.client_id = ' . (int)$client_id;
        }

        $status = $this->input->get('status');
        if ($status) {
            $where[] = 'AND c.status = ' . $this->db->escape($status);
        }

        $billing_group_id = $this->input->get('billing_group_id');
        if ($billing_group_id) {
            $where[] = 'AND c.billing_group_id = ' . (int)$billing_group_id;
        }

        // Additional select fields
        $additionalSelect = [
            'cl.company as client_name'
        ];

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);

        $output = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];

            // ID with link
            $row[] = '<a href="' . admin_url('chargemanager/charges/view/' . $aRow['id']) . '">#' . $aRow['id'] . '</a>';

            // Client
            $row[] = '<a href="' . admin_url('clients/client/' . $aRow['client_id']) . '">' . $aRow['client_name'] . '</a>';

            // Value
            $row[] = app_format_money($aRow['value'], get_base_currency());

            // Due date
            $row[] = _d($aRow['due_date']);

            // Billing type
            $row[] = _l('chargemanager_billing_type_' . strtolower($aRow['billing_type']));

            // Status
            $status_class = $this->get_status_class($aRow['status']);
            $row[] = '<span class="label label-' . $status_class . '">' . _l('chargemanager_status_' . $aRow['status']) . '</span>';

            // Created at
            $row[] = _dt($aRow['created_at']);

            // Actions
            $actions = '<div class="row-options">';
            $actions .= '<a href="' . admin_url('chargemanager/charges/view/' . $aRow['id']) . '">' . _l('view') . '</a>';
            
            if (has_permission('chargemanager', '', 'edit') && !in_array($aRow['status'], ['paid', 'cancelled'])) {
                $actions .= ' | <a href="' . admin_url('chargemanager/charges/edit/' . $aRow['id']) . '">' . _l('edit') . '</a>';
            }
            
            $actions .= '</div>';
            $row[] = $actions;

            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Get status class for badge
     */
    private function get_status_class($status)
    {
        switch ($status) {
            case 'pending':
                return 'warning';
            case 'paid':
                return 'success';
            case 'overdue':
                return 'danger';
            case 'cancelled':
                return 'default';
            case 'partial':
                return 'info';
            default:
                return 'default';
        }
    }
} 