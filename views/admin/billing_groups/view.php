<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <!-- Billing Group Header -->
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin">
                                    <i class="fa fa-file-text-o"></i>
                                    <?php echo _l('chargemanager_billing_group'); ?> #<?php echo $billing_group->id; ?>
                                </h4>
                                <p class="text-muted">
                                    <?php echo _l('chargemanager_created_at'); ?>:
                                    <?php echo _dt($billing_group->created_at); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <?php
                                // Get status configuration using new system
                                $CI = &get_instance();
                                $CI->load->model('chargemanager_billing_groups_model');
                                $status_config = $CI->chargemanager_billing_groups_model->get_status_config($billing_group->status);
                                ?>
                                <div class="pull-right">
                                    <span class="label <?php echo $status_config['class']; ?> label-lg" style="cursor: pointer;" 
                                          onclick="showStatusLegendModal()" title="<?php echo _l('chargemanager_click_to_see_legend'); ?>">
                                        <i class="fa <?php echo $status_config['icon']; ?>"></i>
                                        <?php echo $status_config['label']; ?>
                                        <i class="fa fa-info-circle" style="margin-left: 5px; opacity: 0.7;"></i>
                                    </span>
                                    <?php if (has_permission('chargemanager', '', 'edit') && $status_config['editable']): ?>
                                        <a href="<?php echo admin_url('chargemanager/billing_groups/edit/' . $billing_group->id); ?>"
                                            class="btn btn-info btn-sm" style="margin-left: 10px;">
                                            <i class="fa fa-edit"></i> <?php echo _l('edit'); ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (in_array($billing_group->status, ['completed_over', 'completed_under', 'partial_under', 'overdue_under', 'incomplete'])): ?>
                                        <br><small class="text-muted" style="margin-top: 5px; display: block;">
                                            <i class="fa fa-info-circle"></i> <?php echo $status_config['description']; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing Group Details -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <i class="fa fa-info-circle"></i> <?php echo _l('chargemanager_billing_group_info'); ?>
                                </h5>
                            </div>
                            <div class="panel-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_client'); ?>:</strong></td>
                                        <td>
                                            <a href="<?php echo admin_url('clients/client/' . $billing_group->client_id); ?>">
                                                <?php echo get_company_name($billing_group->client_id); ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_total_value'); ?>:</strong></td>
                                        <td><?php echo app_format_money($billing_group->total_amount, get_base_currency()); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_contract'); ?>:</strong></td>
                                        <td>
                                            <?php if (!empty($billing_group->contract)): ?>
                                                <a href="<?php echo admin_url('contracts/contract/' . $billing_group->contract->id); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($billing_group->contract->subject); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_sale_agent'); ?>:</strong></td>
                                        <td>
                                            <?php if (!empty($billing_group->sale_agent_info)): ?>
                                                <?php
                                                // Check if this was the original lead agent
                                                $CI = &get_instance();
                                                $CI->load->model('chargemanager/chargemanager_billing_groups_model');
                                                $original_lead_staff = $CI->chargemanager_billing_groups_model->get_client_original_lead_staff($billing_group->client_id);
                                                $is_original_lead_agent = ($original_lead_staff && $original_lead_staff == $billing_group->sale_agent_info->staffid);
                                                ?>
                                                <a href="<?php echo admin_url('staff/profile/' . $billing_group->sale_agent_info->staffid); ?>">
                                                    <?php echo $billing_group->sale_agent_info->firstname . ' ' . $billing_group->sale_agent_info->lastname; ?>
                                                </a>
                                                <?php if ($is_original_lead_agent): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fa fa-info-circle"></i> <?php echo _l('chargemanager_original_lead_agent'); ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted"><?php echo _l('chargemanager_no_sale_agent'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_total_charges'); ?>:</strong></td>
                                        <td><?php echo count($charges); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <i class="fa fa-bar-chart"></i> <?php echo _l('chargemanager_payment_summary'); ?>
                                </h5>
                            </div>
                            <div class="panel-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_total_charges'); ?>:</strong></td>
                                        <td><?php echo count($charges); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_total_paid'); ?>:</strong></td>
                                        <td><?php echo app_format_money($total_paid, get_base_currency()); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_remaining'); ?>:</strong></td>
                                        <td><?php echo app_format_money($billing_group->total_amount - $total_paid, get_base_currency()); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_progress'); ?>:</strong></td>
                                        <td>
                                            <?php
                                            $progress = $billing_group->total_amount > 0 ? ($total_paid / $billing_group->total_amount) * 100 : 0;
                                            // Usar formato inteiro se for número inteiro, senão 1 decimal
                                            $progress_formatted = ($progress == intval($progress)) ? number_format($progress, 0) : number_format($progress, 1);
                                            $progress_id = 'progress-bar-' . $billing_group->id;
                                            ?>
                                            <div class="progress no-margin">
                                                <div id="<?php echo $progress_id; ?>"
                                                    class="progress-bar progress-bar-<?php echo $progress >= 100 ? 'success' : ($progress > 0 ? 'info' : 'warning'); ?>"
                                                    role="progressbar"
                                                    aria-valuenow="<?php echo $progress_formatted; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100"
                                                    style="width: <?php echo min($progress, 100); ?>">
                                                </div>
                                            </div>

                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Associated Contracts -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h5 class="panel-title">
                            <i class="fa fa-file-text"></i> <?php echo _l('chargemanager_associated_contracts'); ?>
                        </h5>
                    </div>
                    <div class="panel-body">
                        <?php if (!empty($contracts)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('contract_subject'); ?></th>
                                            <th><?php echo _l('contract_value'); ?></th>
                                            <th><?php echo _l('contract_datestart'); ?></th>
                                            <th><?php echo _l('contract_dateend'); ?></th>
                                            <th><?php echo _l('contract_type'); ?></th>
                                            <th><?php echo _l('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contracts as $contract): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($contract->subject); ?></td>
                                                <td><?php echo app_format_money($contract->contract_value, get_base_currency()); ?></td>
                                                <td><?php echo _d($contract->datestart); ?></td>
                                                <td><?php echo _d($contract->dateend); ?></td>
                                                <td>
                                                    <?php
                                                    // No Perfex CRM, contract_type é um campo de texto livre
                                                    echo !empty($contract->contract_type) ? htmlspecialchars($contract->contract_type) : '-';
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="<?php echo admin_url('contracts/contract/' . $contract->id); ?>"
                                                        class="btn btn-default btn-xs" target="_blank">
                                                        <i class="fa fa-eye"></i> <?php echo _l('view'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted"><?php echo _l('chargemanager_no_contracts_associated'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Invoices -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h5 class="panel-title">
                            <i class="fa fa-file-text-o"></i> <?php echo _l('chargemanager_invoices'); ?>
                        </h5>
                    </div>
                    <div class="panel-body">
                        <?php if (!empty($invoices)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('invoice_number'); ?></th>
                                            <th><?php echo _l('invoice_date'); ?></th>
                                            <th><?php echo _l('invoice_duedate'); ?></th>
                                            <th><?php echo _l('invoice_total'); ?></th>
                                            <th><?php echo _l('invoice_status'); ?></th>
                                            <th><?php echo _l('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invoices as $invoice): ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo admin_url('invoices/list_invoices/' . $invoice->id); ?>" target="_blank">
                                                        <?php echo format_invoice_number($invoice->id); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo _d($invoice->date); ?></td>
                                                <td><?php echo _d($invoice->duedate); ?></td>
                                                <td><?php echo app_format_money($invoice->total, get_base_currency()); ?></td>
                                                <td>
                                                    <?php echo format_invoice_status($invoice->status, '', true); ?>
                                                </td>
                                                <td>
                                                    <a href="<?php echo admin_url('invoices/list_invoices/' . $invoice->id); ?>"
                                                        class="btn btn-default btn-xs" target="_blank">
                                                        <i class="fa fa-eye"></i> <?php echo _l('view'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted"><?php echo _l('chargemanager_no_invoices_found'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Charges -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h5 class="panel-title">
                            <i class="fa fa-credit-card"></i> <?php echo _l('chargemanager_charges'); ?>
                        </h5>
                    </div>
                    <div class="panel-body">
                        <?php if (!empty($charges)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('chargemanager_charge_id'); ?></th>
                                            <th><?php echo _l('chargemanager_value'); ?></th>
                                            <th><?php echo _l('chargemanager_due_date'); ?></th>
                                            <th><?php echo _l('chargemanager_billing_type'); ?></th>
                                            <th><?php echo _l('chargemanager_status'); ?></th>
                                            <th><?php echo _l('chargemanager_payment_date'); ?></th>
                                            <th><?php echo _l('chargemanager_invoice'); ?></th>
                                            <th><?php echo _l('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($charges as $charge): ?>
                                            <tr <?php echo ($charge->is_entry_charge == 1) ? 'class="entry-charge-row"' : ''; ?>>
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
                                                <td><?php echo app_format_money($charge->value, get_base_currency()); ?></td>
                                                <td><?php echo _d($charge->due_date); ?></td>
                                                <td>
                                                    <?php
                                                    $billing_type_labels = [
                                                        'BOLETO' => '<i class="fa fa-barcode"></i> ' . _l('chargemanager_billing_type_boleto'),
                                                        'PIX' => '<i class="fa fa-qrcode"></i> ' . _l('chargemanager_billing_type_pix'),
                                                        'CREDIT_CARD' => '<i class="fa fa-credit-card"></i> ' . _l('chargemanager_billing_type_credit_card')
                                                    ];
                                                    echo $billing_type_labels[$charge->billing_type] ?? $charge->billing_type;
                                                    ?>
                                                </td>
                                                <td>
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
                                                    <?php echo $charge->paid_at ? _dt($charge->paid_at) : '-'; ?>
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
                                                <td>
                                                    <div class="btn-group">
                                                        <?php if (!empty($charge->invoice_url)): ?>
                                                            <a href="<?php echo $charge->invoice_url; ?>"
                                                                target="_blank" class="btn btn-default btn-xs"
                                                                title="<?php echo _l('chargemanager_view_invoice'); ?>">
                                                                <i class="fa fa-external-link"></i>
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if (!empty($charge->barcode)): ?>
                                                            <button type="button" class="btn btn-info btn-xs"
                                                                onclick="showBarcode('<?php echo $charge->barcode; ?>')"
                                                                title="<?php echo _l('chargemanager_view_barcode'); ?>">
                                                                <i class="fa fa-barcode"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if (!empty($charge->pix_code)): ?>
                                                            <button type="button" class="btn btn-success btn-xs"
                                                                onclick="showPixCode('<?php echo $charge->pix_code; ?>')"
                                                                title="<?php echo _l('chargemanager_view_pix_code'); ?>">
                                                                <i class="fa fa-qrcode"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted"><?php echo _l('chargemanager_no_charges_found'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activity Log -->
                <?php if (!empty($activity_log)): ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h5 class="panel-title">
                                <i class="fa fa-history"></i> <?php echo _l('chargemanager_activity_log'); ?>
                            </h5>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo _l('chargemanager_log_date'); ?></th>
                                            <th><?php echo _l('chargemanager_log_type'); ?></th>
                                            <th><?php echo _l('chargemanager_log_message'); ?></th>
                                            <th><?php echo _l('chargemanager_log_status'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activity_log as $log): ?>
                                            <tr>
                                                <td><?php echo _dt($log->created_at); ?></td>
                                                <td>
                                                    <span class="label label-default">
                                                        <?php echo ucfirst($log->event_type); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log->message); ?></td>
                                                <td>
                                                    <?php if ($log->status === 'success'): ?>
                                                        <span class="label label-success">
                                                            <i class="fa fa-check"></i> <?php echo _l('chargemanager_success'); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="label label-danger">
                                                            <i class="fa fa-times"></i> <?php echo _l('chargemanager_error'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modals for Payment Info -->
<div class="modal fade" id="barcode-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-barcode"></i> <?php echo _l('chargemanager_barcode'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><?php echo _l('chargemanager_barcode_number'); ?>:</label>
                    <div class="input-group">
                        <input type="text" id="barcode-number" class="form-control" readonly>
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-default" onclick="copyToClipboard('barcode-number')">
                                <i class="fa fa-copy"></i>
                            </button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pix-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-qrcode"></i> <?php echo _l('chargemanager_pix_code'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><?php echo _l('chargemanager_pix_copy_paste'); ?>:</label>
                    <div class="input-group">
                        <textarea id="pix-code" class="form-control" rows="4" readonly></textarea>
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-default" onclick="copyToClipboard('pix-code')">
                                <i class="fa fa-copy"></i>
                            </button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Legend Modal -->
<div class="modal fade" id="statusLegendModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-info-circle"></i> <?php echo _l('chargemanager_status_legend'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6><strong><?php echo _l('chargemanager_completed_statuses'); ?></strong></h6>
                        <div class="status-legend-item" style="margin-bottom: 10px;">
                            <span class="label label-success label-billing-status">
                                <i class="fa fa-check-circle"></i> <?php echo _l('chargemanager_status_completed_exact'); ?>
                            </span>
                            <br><small class="status-description"><?php echo _l('chargemanager_status_desc_completed_exact'); ?></small>
                        </div>
                        <div class="status-legend-item" style="margin-bottom: 10px;">
                            <span class="label label-success label-billing-status">
                                <i class="fa fa-arrow-up"></i> <?php echo _l('chargemanager_status_completed_over'); ?>
                            </span>
                            <br><small class="status-description"><?php echo _l('chargemanager_status_desc_completed_over'); ?></small>
                        </div>
                        <div class="status-legend-item" style="margin-bottom: 10px;">
                            <span class="label label-warning label-billing-status">
                                <i class="fa fa-arrow-down"></i> <?php echo _l('chargemanager_status_completed_under'); ?>
                            </span>
                            <br><small class="status-description"><?php echo _l('chargemanager_status_desc_completed_under'); ?></small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <h6><strong><?php echo _l('chargemanager_partial_statuses'); ?></strong></h6>
                        <div class="status-legend-item" style="margin-bottom: 10px;">
                            <span class="label label-info label-billing-status">
                                <i class="fa fa-clock-o"></i> <?php echo _l('chargemanager_status_partial_on_track'); ?>
                            </span>
                            <br><small class="status-description"><?php echo _l('chargemanager_status_desc_partial_on_track'); ?></small>
                        </div>
                        <div class="status-legend-item" style="margin-bottom: 10px;">
                            <span class="label label-info label-billing-status">
                                <i class="fa fa-arrow-up"></i> <?php echo _l('chargemanager_status_partial_over'); ?>
                            </span>
                            <br><small class="status-description"><?php echo _l('chargemanager_status_desc_partial_over'); ?></small>
                        </div>
                        <div class="status-legend-item" style="margin-bottom: 10px;">
                            <span class="label label-warning label-billing-status">
                                <i class="fa fa-exclamation-triangle"></i> <?php echo _l('chargemanager_status_partial_under'); ?>
                            </span>
                            <br><small class="status-description"><?php echo _l('chargemanager_status_desc_partial_under'); ?></small>
                        </div>

                        <h6 style="margin-top: 15px;"><strong><?php echo _l('chargemanager_basic_statuses'); ?></strong></h6>
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
                        <div class="status-legend-item" style="margin-bottom: 10px;">
                            <span class="label label-danger label-billing-status">
                                <i class="fa fa-exclamation-circle"></i> <?php echo _l('chargemanager_status_overdue_on_track'); ?>
                            </span>
                            <br><small class="status-description"><?php echo _l('chargemanager_status_desc_overdue_on_track'); ?></small>
                        </div>
                        <div class="status-legend-item" style="margin-bottom: 10px;">
                            <span class="label label-danger label-billing-status">
                                <i class="fa fa-exclamation-circle"></i> <?php echo _l('chargemanager_status_overdue_over'); ?>
                            </span>
                            <br><small class="status-description"><?php echo _l('chargemanager_status_desc_overdue_over'); ?></small>
                        </div>
                        <div class="status-legend-item" style="margin-bottom: 10px;">
                            <span class="label label-danger label-billing-status">
                                <i class="fa fa-exclamation-circle"></i> <?php echo _l('chargemanager_status_overdue_under'); ?>
                            </span>
                            <br><small class="status-description"><?php echo _l('chargemanager_status_desc_overdue_under'); ?></small>
                        </div>
                        <div class="status-legend-item" style="margin-bottom: 10px;">
                            <span class="label label-danger label-billing-status">
                                <i class="fa fa-times-circle"></i> <?php echo _l('chargemanager_status_cancelled'); ?>
                            </span>
                            <br><small class="status-description"><?php echo _l('chargemanager_status_desc_cancelled'); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Value Indicators Legend -->
                <div class="row" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                    <div class="col-md-12">
                        <h6><strong><?php echo _l('chargemanager_value_indicators'); ?></strong></h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="value-comparison exact">
                                    <i class="fa fa-check-circle"></i>
                                    <strong><?php echo _l('chargemanager_exact_value'); ?>:</strong>
                                    <?php echo _l('chargemanager_exact_value_desc'); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="value-comparison over">
                                    <i class="fa fa-arrow-up"></i>
                                    <strong><?php echo _l('chargemanager_over_value'); ?>:</strong>
                                    <?php echo _l('chargemanager_over_value_desc'); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="value-comparison under">
                                    <i class="fa fa-arrow-down"></i>
                                    <strong><?php echo _l('chargemanager_under_value'); ?>:</strong>
                                    <?php echo _l('chargemanager_under_value_desc'); ?>
                                </div>
                            </div>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            </div>
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


    /* Proteção para o texto da progress bar */
    .progress-bar .progress-text {
        position: relative;
        z-index: 10;
        font-weight: bold;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        display: inline-block !important;
        visibility: visible !important;
    }

    .progress-bar {
        position: relative;
        text-align: center;
        line-height: 20px;
    }

    /* Garantir que o texto seja sempre visível */
    .progress .progress-bar:empty:before {
        content: attr(data-original-progress) '%';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        text-align: center;
        line-height: 20px;
        font-weight: bold;
        z-index: 10;
    }

    /* Fallback adicional para quando o span interno é removido */
    .progress .progress-bar:not(:empty):not(:has(.progress-text)):before {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        text-align: center;
        line-height: 20px;
        font-weight: bold;
        z-index: 10;
        background: transparent;
    }

    /* Status label hover effect */
    .label[onclick]:hover {
        opacity: 0.8;
        transition: opacity 0.2s ease;
    }

    /* Modal styling for better readability */
    .status-legend-item {
        margin-bottom: 12px;
    }

    .label-billing-status {
        font-size: 11px;
        padding: 5px 8px;
        display: inline-block;
        margin-bottom: 3px;
    }

    .status-description {
        color: #666;
        font-style: italic;
        line-height: 1.3;
    }

    /* Value comparison styling */
    .value-comparison {
        padding: 8px;
        margin-bottom: 8px;
        border-radius: 3px;
        background-color: #f9f9f9;
        border-left: 3px solid #ddd;
    }

    .value-comparison.exact {
        border-left-color: #5cb85c;
    }

    .value-comparison.over {
        border-left-color: #f0ad4e;
    }

    .value-comparison.under {
        border-left-color: #d9534f;
    }
</style>

<script>
    function showBarcode(barcode) {
        $('#barcode-number').val(barcode);
        $('#barcode-modal').modal('show');
    }

    function showPixCode(pixCode) {
        $('#pix-code').val(pixCode);
        $('#pix-modal').modal('show');
    }

    function copyToClipboard(fieldId) {
        var field = document.getElementById(fieldId);
        field.select();
        document.execCommand('copy');
        alert_float('success', '<?php echo _l('chargemanager_copied_to_clipboard'); ?>');
    }

    // Show status legend modal
    function showStatusLegendModal() {
        $('#statusLegendModal').modal('show');
    }

    // Inicialização do Progress Bar
    function initProgressBar() {
        var progressBar = $('#<?php echo $progress_id; ?>');
        if (progressBar.length) {
            var progressValue = progressBar.attr('aria-valuenow');
            if (isNaN(progressValue)) progressValue = 0;

            // Atualiza o texto dentro da barra
            progressBar.text(progressValue + '%');

            // Atualiza a largura da barra
            progressBar.css('width', progressValue + '%');
        }
    }

    // Executa quando o documento estiver pronto
    $(document).ready(function() {
        initProgressBar();
    });
</script>