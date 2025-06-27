<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <!-- Header -->
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin">
                                    <i class="fa fa-edit"></i>
                                    <?php echo _l('chargemanager_edit_billing_group'); ?> #<?php echo $billing_group->id; ?>
                                </h4>
                                <p class="text-muted">
                                    <?php echo _l('chargemanager_client'); ?>:
                                    <a href="<?php echo admin_url('clients/client/' . $billing_group->client_id); ?>">
                                        <?php echo $client ? $client->company : 'N/A'; ?>
                                    </a>
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="<?php echo admin_url('chargemanager/billing_groups/view/' . $billing_group->id); ?>"
                                    class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> <?php echo _l('back'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <?php if (is_admin()): ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h5 class="panel-title">
                                <i class="fa fa-info-circle"></i> <?php echo _l('chargemanager_basic_information'); ?>
                                <small class="text-muted">(<?php echo _l('admin_only'); ?>)</small> <span class="label label-danger">Refatorar para que edite também o vendedor vinculado aos invoices?</span>
                            </h5>
                        </div>
                        <div class="panel-body">
                            <?php echo form_open(admin_url('chargemanager/billing_groups/update/' . $billing_group->id), ['id' => 'billing-group-basic-form']); ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sale_agent"><?php echo _l('chargemanager_sale_agent'); ?></label>
                                        <select name="sale_agent" id="sale_agent" class="form-control selectpicker"
                                            data-live-search="true">
                                            <option value=""><?php echo _l('chargemanager_no_sale_agent'); ?></option>
                                            <?php if (!empty($staff_members)): ?>
                                                <?php foreach ($staff_members as $staff): ?>
                                                    <option value="<?php echo $staff['staffid']; ?>"
                                                        <?php echo ($billing_group->sale_agent == $staff['staffid']) ? 'selected' : ''; ?>>
                                                        <?php echo $staff['firstname'] . ' ' . $staff['lastname']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="status"><?php echo _l('chargemanager_status'); ?></label>
                                        <select name="status" id="status" class="form-control">
                                            <?php
                                            // Get all possible statuses
                                            $CI = &get_instance();
                                            $CI->load->model('chargemanager_billing_groups_model');
                                            $all_statuses = [
                                                'open', 'incomplete', 'cancelled',
                                                'partial_on_track', 'partial_over', 'partial_under',
                                                'overdue_on_track', 'overdue_over', 'overdue_under',
                                                'completed_exact', 'completed_over', 'completed_under'
                                            ];
                                            
                                            foreach ($all_statuses as $status_key) {
                                                $status_config = $CI->chargemanager_billing_groups_model->get_status_config($status_key);
                                                $selected = ($billing_group->status == $status_key) ? 'selected' : '';
                                                echo '<option value="' . $status_key . '" ' . $selected . '>' . $status_config['label'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-save"></i> <?php echo _l('chargemanager_update_basic_info'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                                    <?php echo form_close(); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Status Legend -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h5 class="panel-title">
                        <i class="fa fa-info-circle"></i> <?php echo _l('chargemanager_status_legend'); ?>
                        <button type="button" class="btn btn-xs btn-default pull-right" onclick="toggleStatusLegend()">
                            <i class="fa fa-eye" id="legend-toggle-icon"></i> <span id="legend-toggle-text"><?php echo _l('chargemanager_show_legend'); ?></span>
                        </button>
                    </h5>
                </div>
                <div class="panel-body" id="status-legend-content" style="display: none;">
                    <div class="row">
                        <div class="col-md-4">
                            <h6><strong><?php echo _l('chargemanager_completed_statuses'); ?></strong></h6>
                            <div class="status-legend-item" style="margin-bottom: 8px;">
                                <span class="label label-success label-billing-status">
                                    <i class="fa fa-check-circle"></i> <?php echo _l('chargemanager_status_completed_exact'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_completed_exact'); ?></small>
                            </div>
                            <div class="status-legend-item" style="margin-bottom: 8px;">
                                <span class="label label-success label-billing-status">
                                    <i class="fa fa-arrow-up"></i> <?php echo _l('chargemanager_status_completed_over'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_completed_over'); ?></small>
                            </div>
                            <div class="status-legend-item" style="margin-bottom: 8px;">
                                <span class="label label-warning label-billing-status">
                                    <i class="fa fa-arrow-down"></i> <?php echo _l('chargemanager_status_completed_under'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_completed_under'); ?></small>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <h6><strong><?php echo _l('chargemanager_partial_statuses'); ?></strong></h6>
                            <div class="status-legend-item" style="margin-bottom: 8px;">
                                <span class="label label-info label-billing-status">
                                    <i class="fa fa-clock-o"></i> <?php echo _l('chargemanager_status_partial_on_track'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_partial_on_track'); ?></small>
                            </div>
                            <div class="status-legend-item" style="margin-bottom: 8px;">
                                <span class="label label-info label-billing-status">
                                    <i class="fa fa-arrow-up"></i> <?php echo _l('chargemanager_status_partial_over'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_partial_over'); ?></small>
                            </div>
                            <div class="status-legend-item" style="margin-bottom: 8px;">
                                <span class="label label-warning label-billing-status">
                                    <i class="fa fa-exclamation-triangle"></i> <?php echo _l('chargemanager_status_partial_under'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_partial_under'); ?></small>
                            </div>
                            
                            <h6 style="margin-top: 10px;"><strong><?php echo _l('chargemanager_basic_statuses'); ?></strong></h6>
                            <div class="status-legend-item" style="margin-bottom: 5px;">
                                <span class="label label-default label-billing-status">
                                    <i class="fa fa-folder-open"></i> <?php echo _l('chargemanager_status_open'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_open'); ?></small>
                            </div>
                            <div class="status-legend-item" style="margin-bottom: 5px;">
                                <span class="label label-warning label-billing-status">
                                    <i class="fa fa-exclamation-triangle"></i> <?php echo _l('chargemanager_status_incomplete'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_incomplete'); ?></small>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <h6><strong><?php echo _l('chargemanager_problem_statuses'); ?></strong></h6>
                            <div class="status-legend-item" style="margin-bottom: 8px;">
                                <span class="label label-danger label-billing-status">
                                    <i class="fa fa-exclamation-circle"></i> <?php echo _l('chargemanager_status_overdue_on_track'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_overdue_on_track'); ?></small>
                            </div>
                            <div class="status-legend-item" style="margin-bottom: 8px;">
                                <span class="label label-danger label-billing-status">
                                    <i class="fa fa-exclamation-circle"></i> <?php echo _l('chargemanager_status_overdue_over'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_overdue_over'); ?></small>
                            </div>
                            <div class="status-legend-item" style="margin-bottom: 8px;">
                                <span class="label label-danger label-billing-status">
                                    <i class="fa fa-exclamation-circle"></i> <?php echo _l('chargemanager_status_overdue_under'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_overdue_under'); ?></small>
                            </div>
                            <div class="status-legend-item" style="margin-bottom: 8px;">
                                <span class="label label-danger label-billing-status">
                                    <i class="fa fa-times-circle"></i> <?php echo _l('chargemanager_status_cancelled'); ?>
                                </span>
                                <br><small class="status-description"><?php echo _l('chargemanager_status_desc_cancelled'); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Editability Info -->
                    <div class="row" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                        <div class="col-md-12">
                            <h6><strong><?php echo _l('chargemanager_editability_rules'); ?></strong></h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="alert alert-success" style="padding: 10px;">
                                        <i class="fa fa-edit"></i> <strong><?php echo _l('chargemanager_editable_statuses'); ?>:</strong><br>
                                        <small><?php echo _l('chargemanager_editable_statuses_desc'); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-danger" style="padding: 10px;">
                                        <i class="fa fa-lock"></i> <strong><?php echo _l('chargemanager_non_editable_statuses'); ?>:</strong><br>
                                        <small><?php echo _l('chargemanager_non_editable_statuses_desc'); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="panel panel-info">
                            <div class="panel-body">
                                <div class="text-center">
                                    <h4 class="no-margin"><?php echo app_format_money($billing_group->total_amount, get_base_currency()); ?></h4>
                                    <small><?php echo _l('chargemanager_total_amount'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-success">
                            <div class="panel-body">
                                <div class="text-center">
                                    <h4 class="no-margin"><?php echo app_format_money($total_paid, get_base_currency()); ?></h4>
                                    <small><?php echo _l('chargemanager_total_paid'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-warning">
                            <div class="panel-body">
                                <div class="text-center">
                                    <h4 class="no-margin"><?php echo app_format_money($billing_group->total_amount - $total_paid, get_base_currency()); ?></h4>
                                    <small><?php echo _l('chargemanager_remaining'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="text-center">
                                    <h4 class="no-margin"><?php echo count($charges); ?></h4>
                                    <small><?php echo _l('chargemanager_total_charges'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contract Information -->
                <?php if ($contract): ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h5 class="panel-title">
                                <i class="fa fa-file-text"></i> <?php echo _l('chargemanager_contract_info'); ?>
                            </h5>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong><?php echo _l('contract_subject'); ?>:</strong>
                                    <a href="<?php echo admin_url('contracts/contract/' . $contract->id); ?>" target="_blank">
                                        <?php echo htmlspecialchars($contract->subject); ?>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <strong><?php echo _l('contract_value'); ?>:</strong>
                                    <?php echo app_format_money($contract->contract_value, get_base_currency()); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong><?php echo _l('chargemanager_difference'); ?>:</strong>
                                    <?php
                                    $difference = $billing_group->total_amount - $contract->contract_value;
                                    $difference_class = $difference > 0 ? 'text-warning' : ($difference < 0 ? 'text-danger' : 'text-success');
                                    ?>
                                    <span class="<?php echo $difference_class; ?>">
                                        <?php echo app_format_money($difference, get_base_currency()); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Charges Management -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="panel-title">
                                    <i class="fa fa-credit-card"></i> <?php echo _l('chargemanager_charges_management'); ?>
                                </h5>
                            </div>
                            <div class="col-md-4 text-right">
                                <button type="button" class="btn btn-success btn-sm" onclick="addNewCharge()">
                                    <i class="fa fa-plus"></i> <?php echo _l('chargemanager_add_charge'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="charges-table">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('chargemanager_charge_id'); ?></th>
                                        <th><?php echo _l('chargemanager_value'); ?></th>
                                        <th><?php echo _l('chargemanager_due_date'); ?></th>
                                        <th><?php echo _l('chargemanager_billing_type'); ?></th>
                                        <th><?php echo _l('chargemanager_status'); ?></th>
                                        <th><?php echo _l('chargemanager_invoice'); ?></th>
                                        <th><?php echo _l('chargemanager_entry_charge'); ?></th>
                                        <th><?php echo _l('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($charges as $charge): ?>
                                        <tr id="charge-row-<?php echo $charge->id; ?>" <?php echo ($charge->is_entry_charge == 1) ? 'class="entry-charge-row"' : ''; ?>>
                                            <td>
                                                <strong>#<?php echo $charge->id; ?></strong>
                                                <?php if ($charge->is_entry_charge == 1): ?>
                                                    <span class="label label-primary" style="margin-left: 5px;" title="<?php echo _l('chargemanager_entry_charge'); ?>">
                                                        <i class="fa fa-star"></i> <?php echo _l('chargemanager_entry'); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <br>
                                                <small class="text-muted"><?php echo $charge->gateway_charge_id ?? '-'; ?></small>
                                            </td>
                                            <td class="charge-value"><?php echo app_format_money($charge->value, get_base_currency()); ?></td>
                                            <td class="charge-due-date"><?php echo _d($charge->due_date); ?></td>
                                            <td class="charge-billing-type">
                                                <?php
                                                $billing_type_labels = [
                                                    'BOLETO' => '<i class="fa fa-barcode"></i> ' . _l('chargemanager_billing_type_boleto'),
                                                    'PIX' => '<i class="fa fa-qrcode"></i> ' . _l('chargemanager_billing_type_pix'),
                                                    'CREDIT_CARD' => '<i class="fa fa-credit-card"></i> ' . _l('chargemanager_billing_type_credit_card')
                                                ];
                                                echo $billing_type_labels[$charge->billing_type] ?? $charge->billing_type;
                                                ?>
                                            </td>
                                            <td class="charge-status">
                                                <?php
                                                $charge_status_class = '';
                                                $charge_status_text = '';
                                                switch ($charge->status) {
                                                    case 'pending':
                                                        $charge_status_class = 'label-warning';
                                                        $charge_status_text = _l('chargemanager_charge_status_pending');
                                                        break;
                                                    case 'received':
                                                    case 'paid':
                                                        $charge_status_class = 'label-success';
                                                        $charge_status_text = _l('chargemanager_charge_status_received');
                                                        break;
                                                    case 'overdue':
                                                        $charge_status_class = 'label-danger';
                                                        $charge_status_text = _l('chargemanager_charge_status_overdue');
                                                        break;
                                                    case 'cancelled':
                                                        $charge_status_class = 'label-default';
                                                        $charge_status_text = _l('chargemanager_charge_status_cancelled');
                                                        break;
                                                    default:
                                                        $charge_status_class = 'label-default';
                                                        $charge_status_text = ucfirst($charge->status);
                                                        break;
                                                }
                                                ?>
                                                <span class="label <?php echo $charge_status_class; ?>">
                                                    <?php echo $charge_status_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($charge->perfex_invoice_id): ?>
                                                    <a href="<?php echo admin_url('invoices/list_invoices/' . $charge->perfex_invoice_id); ?>"
                                                        target="_blank" class="text-success">
                                                        <i class="fa fa-file-text-o"></i> <?php echo format_invoice_number($charge->perfex_invoice_id); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($charge->is_entry_charge == 1): ?>
                                                    <span class="label label-success">
                                                        <i class="fa fa-check"></i> <?php echo _l('chargemanager_yes'); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-primary btn-xs"
                                                        onclick="setAsEntryCharge(<?php echo $charge->id; ?>)"
                                                        title="<?php echo _l('chargemanager_set_as_entry'); ?>">
                                                        <i class="fa fa-star"></i> <?php echo _l('chargemanager_set_entry'); ?>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if (!empty($charge->invoice_url)): ?>
                                                        <a href="<?php echo $charge->invoice_url; ?>"
                                                            target="_blank" class="btn btn-default btn-xs"
                                                            title="<?php echo _l('chargemanager_view_invoice'); ?>">
                                                            <i class="fa fa-external-link"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if (in_array($charge->status, ['pending', 'overdue'])): ?>
                                                        <button type="button" class="btn btn-info btn-xs"
                                                            onclick="editCharge(<?php echo $charge->id; ?>)"
                                                            title="<?php echo _l('edit'); ?>">
                                                            <i class="fa fa-edit"></i>
                                                        </button>

                                                        <button type="button" class="btn btn-danger btn-xs"
                                                            onclick="deleteCharge(<?php echo $charge->id; ?>)"
                                                            title="<?php echo _l('delete'); ?>">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Charge Modal -->
<div class="modal fade" id="addChargeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('chargemanager_add_charge'); ?></h4>
            </div>
            <form id="addChargeForm">
                <div class="modal-body">
                    <input type="hidden" name="billing_group_id" value="<?php echo $billing_group->id; ?>">

                    <div class="form-group">
                        <label for="add_value"><?php echo _l('chargemanager_value'); ?> <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_value" name="value" step="0.01" min="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="add_due_date"><?php echo _l('chargemanager_due_date'); ?> <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="add_due_date" name="due_date" required>
                    </div>

                    <div class="form-group">
                        <label for="add_billing_type"><?php echo _l('chargemanager_billing_type'); ?> <span class="text-danger">*</span></label>
                        <select class="form-control" id="add_billing_type" name="billing_type" required>
                            <option value=""><?php echo _l('chargemanager_select_billing_type'); ?></option>
                            <option value="BOLETO"><?php echo _l('chargemanager_billing_type_boleto'); ?></option>
                            <option value="PIX"><?php echo _l('chargemanager_billing_type_pix'); ?></option>
                            <option value="CREDIT_CARD"><?php echo _l('chargemanager_billing_type_credit_card'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="add_description"><?php echo _l('chargemanager_description'); ?></label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                    <button type="submit" class="btn btn-success"><?php echo _l('chargemanager_add_charge'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Charge Modal -->
<div class="modal fade" id="editChargeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('chargemanager_edit_charge'); ?></h4>
            </div>
            <form id="editChargeForm">
                <div class="modal-body">
                    <input type="hidden" name="charge_id" id="edit_charge_id">

                    <div class="form-group">
                        <label for="edit_value"><?php echo _l('chargemanager_value'); ?> <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_value" name="value" step="0.01" min="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_due_date"><?php echo _l('chargemanager_due_date'); ?> <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_due_date" name="due_date" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_billing_type"><?php echo _l('chargemanager_billing_type'); ?> <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_billing_type" name="billing_type" required>
                            <option value="BOLETO"><?php echo _l('chargemanager_billing_type_boleto'); ?></option>
                            <option value="PIX"><?php echo _l('chargemanager_billing_type_pix'); ?></option>
                            <option value="CREDIT_CARD"><?php echo _l('chargemanager_billing_type_credit_card'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_description"><?php echo _l('chargemanager_description'); ?></label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                    <button type="submit" class="btn btn-info"><?php echo _l('chargemanager_update_charge'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php init_tail(); ?>

<style>
    .entry-charge-row {
        background-color: #f8f9fa !important;
        border-left: 4px solid #007bff !important;
    }

    .entry-charge-row:hover {
        background-color: #e9ecef !important;
    }

    .label-primary {
        background-color: #007bff;
        font-size: 10px;
    }

    .entry-charge-row .label-primary {
        animation: pulse-entry 2s infinite;
    }

    @keyframes pulse-entry {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }

        100% {
            opacity: 1;
        }
    }
</style>

<script>
    // Charges data for JavaScript
    var chargesData = <?php echo json_encode($charges); ?>;

    // Add new charge
    function addNewCharge() {
        $('#addChargeModal').modal('show');
        // Set minimum date to today
        $('#add_due_date').attr('min', new Date().toISOString().split('T')[0]);
    }

    // Edit charge
    function editCharge(chargeId) {
        var charge = chargesData.find(c => c.id == chargeId);
        if (!charge) {
            alert('Charge not found');
            return;
        }

        $('#edit_charge_id').val(charge.id);
        $('#edit_value').val(charge.value);
        $('#edit_due_date').val(charge.due_date);
        $('#edit_billing_type').val(charge.billing_type);
        $('#edit_description').val(charge.description || '');

        $('#editChargeModal').modal('show');
    }

    // Delete charge
    function deleteCharge(chargeId) {
        if (!confirm('<?php echo _l('chargemanager_confirm_delete_charge'); ?>')) {
            return;
        }

        $.ajax({
            url: '<?php echo admin_url('chargemanager/billing_groups/delete_charge'); ?>',
            type: 'POST',
            data: {
                charge_id: chargeId,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                    // Remove row from table
                    $('#charge-row-' + chargeId).fadeOut(function() {
                        $(this).remove();
                        updateTotals();
                    });
                } else {
                    alert_float('danger', response.message);
                }
            },
            error: function() {
                alert_float('danger', 'An error occurred');
            }
        });
    }

    // Handle add charge form submission
    $('#addChargeForm').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        formData += '&<?php echo $this->security->get_csrf_token_name(); ?>=<?php echo $this->security->get_csrf_hash(); ?>';

        $.ajax({
            url: '<?php echo admin_url('chargemanager/billing_groups/add_charge'); ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                    $('#addChargeModal').modal('hide');
                    // Reload page to show new charge
                    location.reload();
                } else {
                    alert_float('danger', response.message);
                }
            },
            error: function() {
                alert_float('danger', 'An error occurred');
            }
        });
    });

    // Handle edit charge form submission
    $('#editChargeForm').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        formData += '&<?php echo $this->security->get_csrf_token_name(); ?>=<?php echo $this->security->get_csrf_hash(); ?>';

        $.ajax({
            url: '<?php echo admin_url('chargemanager/billing_groups/edit_charge'); ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                    $('#editChargeModal').modal('hide');
                    // Reload page to show updated charge
                    location.reload();
                } else {
                    alert_float('danger', response.message);
                }
            },
            error: function() {
                alert_float('danger', 'An error occurred');
            }
        });
    });

    // Reset forms when modals are hidden
    $('#addChargeModal').on('hidden.bs.modal', function() {
        $('#addChargeForm')[0].reset();
    });

    $('#editChargeModal').on('hidden.bs.modal', function() {
        $('#editChargeForm')[0].reset();
    });

    function updateTotals() {
        // This could be enhanced to update totals without page reload
        // For now, we'll rely on page reload after changes
    }

    // Set charge as entry charge
    function setAsEntryCharge(chargeId) {
        if (!confirm('<?php echo _l('chargemanager_confirm_set_entry_charge'); ?>')) {
            return;
        }

        $.ajax({
            url: '<?php echo admin_url('chargemanager/billing_groups/set_entry_charge'); ?>',
            type: 'POST',
            data: {
                charge_id: chargeId,
                '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                    // Reload page to show updated entry charge
                    location.reload();
                } else {
                    alert_float('danger', response.message);
                }
            },
            error: function() {
                alert_float('danger', 'An error occurred');
            }
        });
    }

    // Toggle status legend
    function toggleStatusLegend() {
        var content = document.getElementById('status-legend-content');
        var icon = document.getElementById('legend-toggle-icon');
        var text = document.getElementById('legend-toggle-text');
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.className = 'fa fa-eye-slash';
            text.textContent = '<?php echo _l('chargemanager_hide_legend'); ?>';
        } else {
            content.style.display = 'none';
            icon.className = 'fa fa-eye';
            text.textContent = '<?php echo _l('chargemanager_show_legend'); ?>';
        }
    }
</script>