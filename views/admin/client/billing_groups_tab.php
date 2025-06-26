<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
// Carregar dados dos billing groups para o cliente
$CI = &get_instance();

// Carregar o modelo se ainda não foi carregado
if (!class_exists('Chargemanager_billing_groups_model', false)) {
    $CI->load->model('chargemanager/chargemanager_billing_groups_model');
}

// Obter o ID do cliente da variável global $client
$client_id = isset($client) ? $client->userid : 0;

// Carregar dados dos billing groups para o cliente
$billing_groups = [];
if ($client_id > 0) {
    $billing_groups = $CI->chargemanager_billing_groups_model->get_by_client($client_id);
}
?>

<h4 class="no-margin"><?php echo _l('chargemanager_billing_groups'); ?></h4>
<hr class="hr-panel-heading" />

<!-- Existing Billing Groups -->
<h5><?php echo _l('chargemanager_existing_billing_groups'); ?></h5>
<?php
// Definir cabeçalhos da tabela seguindo padrão Perfex
$table_data = [
    _l('chargemanager_id'),
    _l('chargemanager_status'),
    _l('chargemanager_total_amount'),
    _l('chargemanager_created_at'),
    _l('chargemanager_options')
];

// Renderizar tabela usando função nativa do Perfex (sempre renderizar para AJAX)
render_datatable($table_data, 'billing-groups', ['table-responsive']);
?>
<hr />

<!-- New Billing Group Form -->
<?php if (has_permission('chargemanager', '', 'create')) { ?>
    <div class="panel panel-info">
        <div class="panel-heading">
            <h5 class="panel-title">
                <i class="fa fa-plus"></i> <?php echo _l('chargemanager_new_billing_group'); ?>
            </h5>
        </div>
        <div class="panel-body">
            <?php echo form_open(admin_url('chargemanager/billing_groups/create'), ['id' => 'billing-group-form']); ?>
            <!-- CSRF token is automatically included by form_open() -->
            <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="contract_id"><?php echo _l('chargemanager_contract'); ?> <span class="text-danger">*</span></label>
                        <select name="contract_id" id="contract_id" class="form-control" required>
                            <option value=""><?php echo _l('dropdown_non_selected_tex'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="contract_value"><?php echo _l('chargemanager_contract_value'); ?></label>
                        <div class="input-group">
                            <div class="input-group-addon"><?php echo get_base_currency()->symbol; ?></div>
                            <input type="text" class="form-control" id="contract_value" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sale_agent"><?php echo _l('chargemanager_sale_agent'); ?></label>
                        <select name="sale_agent" id="sale_agent" class="form-control selectpicker" 
                                data-live-search="true" data-none-selected-text="<?php echo _l('chargemanager_select_sale_agent'); ?>">
                            <option value=""><?php echo _l('chargemanager_no_sale_agent'); ?></option>
                            <?php 
                            // Load staff members
                            $CI = &get_instance();
                            $CI->load->model('staff_model');
                            $CI->db->where('active', 1);
                            $CI->db->order_by('firstname, lastname', 'ASC');
                            $staff_members = $CI->db->get(db_prefix() . 'staff')->result();
                            
                            // Get the original lead staff for pre-selection
                            $CI->load->model('chargemanager/chargemanager_billing_groups_model');
                            $original_lead_staff = $CI->chargemanager_billing_groups_model->get_client_original_lead_staff($client_id);
                            
                            foreach($staff_members as $staff): 
                                $selected = ($original_lead_staff && $original_lead_staff == $staff->staffid) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $staff->staffid; ?>" <?php echo $selected; ?>>
                                    <?php echo $staff->firstname . ' ' . $staff->lastname; ?>
                                    <?php if ($original_lead_staff && $original_lead_staff == $staff->staffid): ?>
                                        (<?php echo _l('chargemanager_original_lead_agent'); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <!-- Placeholder for future fields -->
                </div>
            </div>

            <div id="charges-container" class="hide">
                <hr />
                <h5><?php echo _l('chargemanager_charges'); ?></h5>
                <div class="charges-list"></div>
                <div class="text-right mtop15">
                    <button type="button" class="btn btn-info add-charge">
                        <i class="fa fa-plus"></i> <?php echo _l('chargemanager_add_charge'); ?>
                    </button>
                </div>

                <div class="row mtop15">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?php echo _l('chargemanager_total_charges'); ?></label>
                            <div class="input-group">
                                <div class="input-group-addon"><?php echo get_base_currency()->symbol; ?></div>
                                <input type="text" class="form-control" id="total_charges" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?php echo _l('chargemanager_difference'); ?></label>
                            <div class="input-group">
                                <div class="input-group-addon"><?php echo get_base_currency()->symbol; ?></div>
                                <input type="text" class="form-control" id="difference" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?php echo _l('chargemanager_validation_status'); ?></label>
                            <div id="validation-status" class="form-control-static">
                                <span class="label label-default"><?php echo _l('chargemanager_pending_validation'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <hr />
                <div class="text-right">
                    <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                        <i class="fa fa-save"></i> <?php echo _l('chargemanager_create_billing_group'); ?>
                    </button>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
<?php } ?>

<!-- Charge Template -->
<script type="text/html" id="charge-template">
    <div class="charge-item panel panel-default" data-index="{index}">
        <div class="panel-heading">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="panel-title">
                        <i class="fa fa-credit-card"></i> <?php echo _l('chargemanager_charge'); ?> #{index}
                    </h4>
                </div>
                <div class="col-md-4 text-right">
                    <button type="button" class="btn btn-danger btn-xs remove-charge">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo _l('chargemanager_amount'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-addon"><?php echo get_base_currency()->symbol; ?></div>
                            <input type="number" name="charges[{index}][amount]" class="form-control charge-amount" step="0.01" min="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo _l('chargemanager_due_date'); ?> <span class="text-danger">*</span></label>
                        <input type="date" name="charges[{index}][due_date]" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo _l('chargemanager_billing_type'); ?> <span class="text-danger">*</span></label>
                        <select name="charges[{index}][billing_type]" class="form-control" required>
                            <option value=""><?php echo _l('chargemanager_select_billing_type'); ?></option>
                            <option value="PIX"><?php echo _l('chargemanager_billing_type_pix'); ?></option>
                            <option value="BOLETO"><?php echo _l('chargemanager_billing_type_boleto'); ?></option>
                            <option value="CREDIT_CARD"><?php echo _l('chargemanager_billing_type_credit_card'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<?php
hooks()->add_action('app_admin_footer', 'chargemanager_client_tab_view_js');

function chargemanager_client_tab_view_js() {
    // Make CSRF data available to JavaScript
    $CI = &get_instance();
    $csrf_data = [
        'token_name' => $CI->security->get_csrf_token_name(),
        'hash' => $CI->security->get_csrf_hash()
    ];
    echo '<style>
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .loading::after {
            content: " (Loading...)";
            color: #999;
            font-style: italic;
        }
    </style>';
    echo '<script>';
    echo 'var csrfData = ' . json_encode($csrf_data) . ';';
    echo 'console.log("CSRF Data loaded:", csrfData);'; // Debug log
    echo '</script>';
    echo '<script src="' . module_dir_url(CHARGEMANAGER_MODULE_NAME, 'assets/js/billing_groups_tab.js') . '"></script>';
}
?>
