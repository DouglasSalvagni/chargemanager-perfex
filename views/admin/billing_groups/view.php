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
                                    <?php echo htmlspecialchars($billing_group->billing_group_name); ?>
                                </h4>
                                <p class="text-muted">
                                    <?php echo _l('chargemanager_created_at'); ?>:
                                    <?php echo _dt($billing_group->created_at); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <?php
                                $status_class = '';
                                $status_text = '';
                                switch ($billing_group->status) {
                                    case 'open':
                                        $status_class = 'label-warning';
                                        $status_text = _l('chargemanager_status_open');
                                        break;
                                    case 'partial':
                                        $status_class = 'label-info';
                                        $status_text = _l('chargemanager_status_partial');
                                        break;
                                    case 'paid':
                                        $status_class = 'label-success';
                                        $status_text = _l('chargemanager_status_paid');
                                        break;
                                    case 'cancelled':
                                        $status_class = 'label-danger';
                                        $status_text = _l('chargemanager_status_cancelled');
                                        break;
                                }
                                ?>
                                <span class="label <?php echo $status_class; ?> label-lg">
                                    <?php echo $status_text; ?>
                                </span>
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
                                        <td>R$ <?php echo number_format($billing_group->total_value, 2, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_due_date'); ?>:</strong></td>
                                        <td><?php echo _d($billing_group->due_date); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_billing_type'); ?>:</strong></td>
                                        <td>
                                            <?php
                                            $billing_types = [
                                                'BOLETO' => _l('chargemanager_billing_type_boleto'),
                                                'PIX' => _l('chargemanager_billing_type_pix'),
                                                'CREDIT_CARD' => _l('chargemanager_billing_type_credit_card')
                                            ];
                                            echo $billing_types[$billing_group->billing_type] ?? $billing_group->billing_type;
                                            ?>
                                        </td>
                                    </tr>
                                    <?php if (!empty($billing_group->description)): ?>
                                        <tr>
                                            <td><strong><?php echo _l('chargemanager_description'); ?>:</strong></td>
                                            <td><?php echo nl2br(htmlspecialchars($billing_group->description)); ?></td>
                                        </tr>
                                    <?php endif; ?>
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
                                        <td>R$ <?php echo number_format($total_paid, 2, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_remaining'); ?>:</strong></td>
                                        <td>R$ <?php echo number_format($billing_group->total_value - $total_paid, 2, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('chargemanager_progress'); ?>:</strong></td>
                                        <td>
                                            <?php
                                            $progress = $billing_group->total_value > 0 ? ($total_paid / $billing_group->total_value) * 100 : 0;
                                            ?>
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-<?php echo $progress >= 100 ? 'success' : ($progress > 0 ? 'info' : 'warning'); ?>"
                                                    style="width: <?php echo min($progress, 100); ?>%">
                                                    <?php echo number_format($progress, 1); ?>%
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
                                                <td>R$ <?php echo number_format($contract->contract_value, 2, ',', '.'); ?></td>
                                                <td><?php echo _d($contract->datestart); ?></td>
                                                <td><?php echo _d($contract->dateend); ?></td>
                                                <td><?php echo get_contract_type_by_id($contract->contract_type); ?></td>
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
                                            <th><?php echo _l('chargemanager_status'); ?></th>
                                            <th><?php echo _l('chargemanager_payment_date'); ?></th>
                                            <th><?php echo _l('chargemanager_invoice'); ?></th>
                                            <th><?php echo _l('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($charges as $charge): ?>
                                            <tr>
                                                <td>
                                                    <small class="text-muted"><?php echo $charge->gateway_charge_id ?? '-'; ?></small>
                                                </td>
                                                <td>R$ <?php echo number_format($charge->value, 2, ',', '.'); ?></td>
                                                <td><?php echo _d($charge->due_date); ?></td>
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
                                                    }
                                                    ?>
                                                    <span class="label <?php echo $charge_status_class; ?>">
                                                        <?php echo $charge_status_text; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $charge->payment_date ? _dt($charge->payment_date) : '-'; ?>
                                                </td>
                                                <td>
                                                    <?php if ($charge->invoice_id): ?>
                                                        <a href="<?php echo admin_url('invoices/list_invoices/' . $charge->invoice_id); ?>"
                                                            target="_blank" class="text-success">
                                                            <i class="fa fa-file-text-o"></i> #<?php echo $charge->invoice_id; ?>
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
                                                        <?php echo ucfirst($log->sync_type); ?>
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

<?php init_tail(); ?>

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
</script>