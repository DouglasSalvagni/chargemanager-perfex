<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <i class="fa fa-cog"></i> <?php echo _l('chargemanager_settings'); ?>
                        </h4>
                        <hr class="hr-panel-heading" />

                        <!-- Connection Status -->
                        <div class="row">
                            <div class="col-md-12">
                                <div id="connection-status" class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> <?php echo _l('chargemanager_click_test_connection'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Form -->
                        <?php echo form_open('admin/chargemanager/settings', ['id' => 'chargemanager-settings-form']); ?>

                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fa fa-credit-card"></i> <?php echo _l('chargemanager_asaas_settings'); ?></h5>

                                <div class="form-group">
                                    <label for="asaas_api_key" class="control-label">
                                        <?php echo _l('chargemanager_api_key'); ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" id="asaas_api_key" name="asaas_api_key"
                                        class="form-control"
                                        value="<?php echo set_value('asaas_api_key', $asaas_api_key); ?>"
                                        placeholder="<?php echo _l('chargemanager_api_key_placeholder'); ?>">
                                    <button type="button" class="btn btn-link btn-sm" onclick="togglePasswordVisibility('asaas_api_key')">
                                        <i class="fa fa-eye"></i> <?php echo _l('chargemanager_show_hide'); ?>
                                    </button>
                                </div>

                                <div class="form-group">
                                    <label for="asaas_environment" class="control-label">
                                        <?php echo _l('chargemanager_environment'); ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select id="asaas_environment" name="asaas_environment" class="form-control selectpicker">
                                        <option value="sandbox" <?php echo set_select('asaas_environment', 'sandbox', ($asaas_environment === 'sandbox')); ?>>
                                            <?php echo _l('chargemanager_sandbox'); ?>
                                        </option>
                                        <option value="production" <?php echo set_select('asaas_environment', 'production', ($asaas_environment === 'production')); ?>>
                                            <?php echo _l('chargemanager_production'); ?>
                                        </option>
                                    </select>
                                    <small class="help-block"><?php echo _l('chargemanager_environment_help'); ?></small>
                                </div>

                                <div class="form-group">
                                    <label for="webhook_url" class="control-label">
                                        <?php echo _l('chargemanager_webhook_url'); ?>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" id="webhook_url" class="form-control"
                                            value="<?php echo site_url('chargemanager/webhook'); ?>" readonly>
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-default" onclick="copyToClipboard('webhook_url')">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </span>
                                    </div>
                                    <small class="help-block"><?php echo _l('chargemanager_webhook_help'); ?></small>
                                </div>

                                <div class="form-group">
                                    <label for="webhook_token" class="control-label">
                                        <?php echo _l('chargemanager_webhook_token'); ?>
                                    </label>
                                    <input type="password" id="webhook_token" name="webhook_token"
                                        class="form-control"
                                        value="<?php echo set_value('webhook_token', $webhook_token); ?>"
                                        placeholder="<?php echo _l('chargemanager_webhook_token_placeholder'); ?>">
                                    <button type="button" class="btn btn-link btn-sm" onclick="togglePasswordVisibility('webhook_token')">
                                        <i class="fa fa-eye"></i> <?php echo _l('chargemanager_show_hide'); ?>
                                    </button>
                                    <small class="help-block"><?php echo _l('chargemanager_webhook_token_help'); ?></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5><i class="fa fa-cog"></i> <?php echo _l('chargemanager_general_settings'); ?></h5>

                                <div class="form-group">
                                    <div class="checkbox checkbox-primary">
                                        <input type="checkbox" id="enabled" name="enabled"
                                            value="1" <?php echo ($enabled == '1' || $enabled === true) ? 'checked' : ''; ?>>
                                        <label for="enabled">
                                            <?php echo _l('chargemanager_enable_integration'); ?>
                                        </label>
                                        <small class="help-block"><?php echo _l('chargemanager_enable_integration_help'); ?></small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="checkbox checkbox-primary">
                                        <input type="checkbox" id="auto_sync_clients" name="auto_sync_clients"
                                            value="1" <?php echo ($auto_sync_clients == '1') ? 'checked' : ''; ?>>
                                        <label for="auto_sync_clients">
                                            <?php echo _l('chargemanager_auto_sync_clients'); ?>
                                        </label>
                                        <small class="help-block"><?php echo _l('chargemanager_auto_sync_clients_help'); ?></small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="checkbox checkbox-primary">
                                        <input type="checkbox" id="auto_create_invoices" name="auto_create_invoices"
                                            value="1" <?php echo ($auto_create_invoices == '1') ? 'checked' : ''; ?>>
                                        <label for="auto_create_invoices">
                                            <?php echo _l('chargemanager_auto_create_invoices'); ?>
                                        </label>
                                        <small class="help-block"><?php echo _l('chargemanager_auto_create_invoices_help'); ?></small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="checkbox checkbox-primary">
                                        <input type="checkbox" id="debug_mode" name="debug_mode"
                                            value="1" <?php echo ($debug_mode == '1') ? 'checked' : ''; ?>>
                                        <label for="debug_mode">
                                            <?php echo _l('chargemanager_debug_mode'); ?>
                                        </label>
                                        <small class="help-block"><?php echo _l('chargemanager_debug_mode_help'); ?></small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="default_billing_type" class="control-label">
                                        <?php echo _l('chargemanager_default_billing_type'); ?>
                                    </label>
                                    <select id="default_billing_type" name="default_billing_type" class="form-control selectpicker">
                                        <option value="BOLETO" <?php echo set_select('default_billing_type', 'BOLETO', ($default_billing_type === 'BOLETO')); ?>>
                                            <?php echo _l('chargemanager_billing_type_boleto'); ?>
                                        </option>
                                        <option value="PIX" <?php echo set_select('default_billing_type', 'PIX', ($default_billing_type === 'PIX')); ?>>
                                            <?php echo _l('chargemanager_billing_type_pix'); ?>
                                        </option>
                                        <option value="CREDIT_CARD" <?php echo set_select('default_billing_type', 'CREDIT_CARD', ($default_billing_type === 'CREDIT_CARD')); ?>>
                                            <?php echo _l('chargemanager_billing_type_credit_card'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="btn-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save"></i> <?php echo _l('chargemanager_save_settings'); ?>
                                    </button>

                                    <button type="button" id="test-connection-btn" class="btn btn-info">
                                        <i class="fa fa-plug"></i> <?php echo _l('chargemanager_test_connection'); ?>
                                    </button>

                                    <button type="button" id="clear-logs-btn" class="btn btn-warning">
                                        <i class="fa fa-trash"></i> <?php echo _l('chargemanager_clear_logs'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <?php echo form_close(); ?>

                        <!-- Sync Logs -->
                        <div class="row" style="margin-top: 30px;">
                            <div class="col-md-12">
                                <h5><i class="fa fa-list"></i> <?php echo _l('chargemanager_recent_logs'); ?></h5>
                                <div class="table-responsive">
                                    <table class="table table-striped" id="sync-logs-table">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('chargemanager_log_date'); ?></th>
                                                <th><?php echo _l('chargemanager_log_type'); ?></th>
                                                <th><?php echo _l('chargemanager_log_message'); ?></th>
                                                <th><?php echo _l('chargemanager_log_status'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recent_logs)): ?>
                                                <?php foreach ($recent_logs as $log): ?>
                                                    <tr>
                                                        <td><?php echo date('d/m/Y H:i:s', strtotime($log->created_at)); ?></td>
                                                        <td>
                                                            <span class="label label-default">
                                                                <?php echo ucfirst($log->event_type ?? 'sync'); ?>
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
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">
                                                        <?php echo _l('chargemanager_no_logs'); ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<!-- JavaScript -->
<script>
    $(document).ready(function() {
        // Initialize form validation
        $('#chargemanager-settings-form').appFormValidator({
            rules: {
                asaas_api_key: {
                    required: true,
                    minlength: 10
                }
            },
            messages: {
                asaas_api_key: {
                    required: '<?php echo _l('chargemanager_api_key_required'); ?>',
                    minlength: '<?php echo _l('chargemanager_api_key_minlength'); ?>'
                }
            }
        });

        // Test connection
        $('#test-connection-btn').click(function() {
            var $btn = $(this);
            var $status = $('#connection-status');

            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('chargemanager_testing'); ?>');

            $.post(admin_url + 'chargemanager/settings/test_connection', function(response) {
                if (response.success) {
                    $status.removeClass('alert-info alert-danger').addClass('alert-success')
                        .html('<i class="fa fa-check-circle"></i> ' + response.message);
                } else {
                    $status.removeClass('alert-info alert-success').addClass('alert-danger')
                        .html('<i class="fa fa-exclamation-circle"></i> ' + response.message);
                }
            }).fail(function() {
                $status.removeClass('alert-info alert-success').addClass('alert-danger')
                    .html('<i class="fa fa-exclamation-circle"></i> <?php echo _l('chargemanager_connection_failed'); ?>');
            }).always(function() {
                $btn.prop('disabled', false).html('<i class="fa fa-plug"></i> <?php echo _l('chargemanager_test_connection'); ?>');
            });
        });

        // Clear logs
        $('#clear-logs-btn').click(function() {
            if (confirm('<?php echo _l('chargemanager_confirm_clear_logs'); ?>')) {
                var $btn = $(this);

                $btn.prop('disabled', true);

                $.post(admin_url + 'chargemanager/settings/clear_logs', function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert_float('danger', response.message);
                    }
                }).always(function() {
                    $btn.prop('disabled', false);
                });
            }
        });
    });

    // Toggle password visibility
    function togglePasswordVisibility(fieldId) {
        var field = document.getElementById(fieldId);
        var button = field.nextElementSibling.querySelector('i');

        if (field.type === 'password') {
            field.type = 'text';
            button.className = 'fa fa-eye-slash';
        } else {
            field.type = 'password';
            button.className = 'fa fa-eye';
        }
    }

    // Copy to clipboard
    function copyToClipboard(fieldId) {
        var field = document.getElementById(fieldId);
        field.select();
        document.execCommand('copy');
        alert_float('success', '<?php echo _l('chargemanager_copied_to_clipboard'); ?>');
    }
</script>