/**
 * Billing Groups Tab JavaScript
 * Handles dynamic charge creation and form validation
 */

$(document).ready(function() {
    'use strict';
    
    var chargeIndex = 0;
    var contractValue = 0;
    var nextChargeNumber = 1; // Para controlar a numeração sequencial
    
    // Initialize DataTable for existing billing groups (with delay for tab loading)
    setTimeout(function() {
        initializeBillingGroupsTable();
    }, 200);
    
    // Load contracts for the client (with small delay to ensure DOM is ready)
    setTimeout(function() {
        loadClientContracts();
    }, 300);
    
    // Contract selection change
    $('#contract_id').on('change', function() {
        var contractId = $(this).val();
        if (contractId) {
            loadContractDetails(contractId);
        } else {
            resetForm();
        }
    });
    
    // Add charge button
    $('.add-charge').on('click', function() {
        addCharge();
    });
    
    // Remove charge button (delegated event)
    $(document).on('click', '.remove-charge', function() {
        removeCharge($(this));
    });
    
    // Charge amount change (delegated event)
    $(document).on('input', '.charge-amount', function() {
        calculateTotals();
    });
    
    // Form submission
    $('#billing-group-form').on('submit', function(e) {
        e.preventDefault();
        
        if (validateFormEnhanced()) {
            submitForm();
        }
    });
    
    /**
     * Initialize DataTable using Perfex native function
     */
    function initializeBillingGroupsTable() {
        // Check if table exists
        if ($('.table-billing-groups').length === 0) {
            console.log('Billing groups table not found'); // Debug log
            return;
        }
        
        try {
            console.log('Initializing billing groups DataTable using Perfex native function'); // Debug log
            
            // Get client ID for filtering
            var clientId = $('input[name="client_id"]').val();
            
            // Build AJAX URL with client filter
            var ajaxUrl = admin_url + 'chargemanager/billing_groups/ajax_billing_groups_table';
            if (clientId) {
                ajaxUrl += '?client_id=' + encodeURIComponent(clientId);
            }
            
            // Initialize DataTable using Perfex native function
            window.billingGroupsTable = initDataTable(
                '.table-billing-groups',           // Seletor da tabela
                ajaxUrl,                           // URL AJAX
                [4],                               // Coluna de ações não ordenável (índice 4)
                [4],                               // Coluna de ações não pesquisável (índice 4)
                undefined,                         // DOM configuration (usar undefined)
                [0, 'desc']                        // Ordenação inicial [coluna, direção]
            );
            
            console.log('DataTable initialized successfully using Perfex native function'); // Debug log
        } catch (error) {
            console.error('Error initializing DataTable:', error); // Debug log
        }
    }

    /**
     * Load contracts for the current client
     */
    function loadClientContracts() {
        var clientId = $('input[name="client_id"]').val();
        
        if (!clientId) {
            console.log('No client ID found'); // Debug log
            return;
        }
        
        if ($('#contract_id').length === 0) {
            console.log('Contract select element not found'); // Debug log
            return;
        }
        
        console.log('Loading contracts for client:', clientId); // Debug log
        
        var requestData = { 
            client_id: clientId
        };
        
        // Add CSRF token if available
        if (typeof csrfData !== "undefined") {
            requestData[csrfData.token_name] = csrfData.hash;
        }
        
        $.ajax({
            url: admin_url + 'chargemanager/billing_groups/get_client_contracts',
            type: 'POST',
            data: requestData,
            dataType: 'json',
            beforeSend: function() {
                $('#contract_id').addClass('loading');
            },
            success: function(response) {
                console.log('AJAX Response:', response); // Debug log
                
                if (response.success) {
                    var options = '<option value="">Select Contract</option>';
                    
                    var contracts = response.data && response.data.contracts ? response.data.contracts : response.contracts;
                    
                    console.log('Contracts found:', contracts); // Debug log
                    
                    if (contracts && contracts.length > 0) {
                        $.each(contracts, function(index, contract) {
                            options += '<option value="' + contract.id + '">' + 
                                      contract.subject + ' (#' + contract.id + ') - ' + contract.contract_value +
                                      '</option>';
                        });
                        console.log('Options generated:', options); // Debug log
                    } else {
                        options += '<option value="" disabled>No contracts available</option>';
                        console.log('No contracts available'); // Debug log
                    }
                    
                    $('#contract_id').html(options);
                } else {
                    console.log('Error response:', response); // Debug log
                    alert_float('danger', response.message || 'Error loading contracts');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr, status, error); // Debug log
                alert_float('danger', 'Error loading contracts: ' + error);
            },
            complete: function() {
                $('#contract_id').removeClass('loading');
            }
        });
    }
    
    /**
     * Load contract details when selected
     */
    function loadContractDetails(contractId) {
        var requestData = { 
            contract_id: contractId
        };
        
        // Add CSRF token if available
        if (typeof csrfData !== "undefined") {
            requestData[csrfData.token_name] = csrfData.hash;
        }
        
        $.ajax({
            url: admin_url + 'chargemanager/billing_groups/get_contract_details',
            type: 'POST',
            data: requestData,
            dataType: 'json',
            beforeSend: function() {
                $('#contract_id').addClass('loading');
            },
            success: function(response) {
                if (response.success) {
                    contractValue = parseFloat(response.contract.contract_value) || 0;
                    $('#contract_value').val(formatMoney(contractValue));
                    $('#charges-container').removeClass('hide');
                    
                    // Add first charge automatically if none exist
                    if ($('.charge-item').length === 0) {
                        addCharge();
                    }
                    
                    calculateTotals();
                } else {
                    alert_float('danger', response.message || 'Error loading contract details');
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', 'Error loading contract details: ' + error);
            },
            complete: function() {
                $('#contract_id').removeClass('loading');
            }
        });
    }
    
    /**
     * Add a new charge
     */
    function addCharge() {
        chargeIndex++;
        var template = $('#charge-template').html();
        template = template.replace(/{index}/g, chargeIndex);
        template = template.replace(/{actualIndex}/g, nextChargeNumber);
        
        $('.charges-list').append(template);
        
        // Mark first charge as entry charge
        updateEntryChargeStatus();
        
        // Increment next charge number
        nextChargeNumber++;
        
        calculateTotals();
    }
    
    /**
     * Remove a charge
     */
    function removeCharge($button) {
        var $chargeItem = $button.closest('.charge-item');
        var isEntryCharge = $chargeItem.hasClass('entry-charge-item');
        
        // Prevent removal of entry charge if there are other charges
        if (isEntryCharge && $('.charge-item').length > 1) {
            alert_float('warning', 'A cobrança de entrada não pode ser removida. Para remover, primeiro defina outra cobrança como entrada.');
            return;
        }
        
        $chargeItem.remove();
        
        // Update entry charge status after removal
        updateEntryChargeStatus();
        
        // Renumber all charges
        renumberCharges();
        
        calculateTotals();
    }
    
    /**
     * Calculate totals and validation
     */
    function calculateTotals() {
        var totalCharges = 0;
        
        $('.charge-amount').each(function() {
            var amount = parseFloat($(this).val()) || 0;
            totalCharges += amount;
        });
        
        $('#total_charges').val(formatMoney(totalCharges));
        
        var difference = contractValue - totalCharges;
        $('#difference').val(formatMoney(difference));
        
        updateValidationStatus(difference, totalCharges);
    }
    
    /**
     * Update validation status
     */
    function updateValidationStatus(difference, totalCharges) {
        var $status = $('#validation-status');
        var $submitBtn = $('#submit-btn');
        
        if (totalCharges === 0) {
            $status.html('<span class="label label-default">Pending Validation</span>');
            $submitBtn.prop('disabled', true);
        } else if (Math.abs(difference) < 0.01) { // Consider floating point precision
            $status.html('<span class="label label-success">Valid</span>');
            $submitBtn.prop('disabled', false);
        } else if (difference > 0) {
            $status.html('<span class="label label-warning">Charges below contract value (R$ ' + formatMoney(Math.abs(difference)) + ' remaining)</span>');
            $submitBtn.prop('disabled', true);
        } else {
            $status.html('<span class="label label-danger">Charges exceed contract value (R$ ' + formatMoney(Math.abs(difference)) + ' over)</span>');
            $submitBtn.prop('disabled', true);
        }
    }
    
    /**
     * Validate form
     */
    function validateFormEnhanced() {
        var errors = [];
        
        // Contract validation
        if (!$('#contract_id').val()) {
            errors.push('Please select a contract');
        }
        
        // Sale agent validation (opcional)
        var saleAgent = $('#sale_agent').val();
        if (saleAgent && !$.isNumeric(saleAgent)) {
            errors.push('Invalid sale agent selected');
        }
        
        // Charges validation
        var charges = $('.charge-item');
        if (charges.length === 0) {
            errors.push('At least one charge is required');
        }
        
        charges.each(function(index) {
            var $charge = $(this);
            var chargeNum = index + 1;
            
            var amount = $charge.find('.charge-amount').val();
            var dueDate = $charge.find('input[type="date"]').val();
            var billingType = $charge.find('select').val();
            
            if (!amount || parseFloat(amount) <= 0) {
                errors.push('Charge ' + chargeNum + ': Amount is required and must be greater than 0');
            }
            
            if (!dueDate) {
                errors.push('Charge ' + chargeNum + ': Due date is required');
            }
            
            if (!billingType) {
                errors.push('Charge ' + chargeNum + ': Billing type is required');
            }
        });
        
        // Total validation
        var totalCharges = 0;
        $('.charge-amount').each(function() {
            totalCharges += parseFloat($(this).val()) || 0;
        });
        
        var difference = Math.abs(contractValue - totalCharges);
        if (difference >= 0.01) {
            errors.push('Total charges must equal contract value');
        }
        
        if (errors.length > 0) {
            alert_float('danger', errors.join('<br/>'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Submit form
     */
    function submitForm() {
        var formData = $('#billing-group-form').serialize();
        
        $.ajax({
            url: admin_url + 'chargemanager/billing_groups/create',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#submit-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating...');
            },
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message || 'Billing group created successfully');
                    resetForm();
                    
                    // Reload DataTable if it exists
                    if (typeof window.billingGroupsTable !== 'undefined') {
                        window.billingGroupsTable.ajax.reload();
                    }
                } else {
                    alert_float('danger', response.message || 'Error creating billing group');
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', 'Error creating billing group: ' + error);
            },
            complete: function() {
                $('#submit-btn').html('<i class="fa fa-save"></i> Create Billing Group');
            }
        });
    }
    
    /**
     * Reset form
     */
    function resetForm() {
        $('#billing-group-form')[0].reset();
        $('#contract_value').val('');
        $('#charges-container').addClass('hide');
        $('.charges-list').empty();
        $('#total_charges').val('');
        $('#difference').val('');
        $('#validation-status').html('<span class="label label-default">Pending Validation</span>');
        $('#submit-btn').prop('disabled', true);
        chargeIndex = 0;
        contractValue = 0;
        nextChargeNumber = 1; // Reset charge numbering
    }
    
    /**
     * Update entry charge status
     */
    function updateEntryChargeStatus() {
        var $charges = $('.charge-item');
        
        // Remove all entry charge styling first
        $charges.removeClass('entry-charge-item');
        $charges.find('.entry-charge-badge').hide();
        $charges.find('.remove-charge').prop('disabled', false).prop('title', '');
        
        if ($charges.length > 0) {
            // Mark first charge as entry charge
            var $firstCharge = $charges.first();
            $firstCharge.addClass('entry-charge-item');
            $firstCharge.find('.entry-charge-badge').show();
            
            // If there are multiple charges, disable removal of entry charge
            if ($charges.length > 1) {
                $firstCharge.find('.remove-charge').prop('disabled', true).prop('title', 'A cobrança de entrada não pode ser removida');
            }
        }
    }
    
    /**
     * Renumber all charges sequentially
     */
    function renumberCharges() {
        var $charges = $('.charge-item');
        nextChargeNumber = 1;
        
        $charges.each(function(index) {
            var $charge = $(this);
            var newNumber = index + 1;
            
            // Update display number
            $charge.find('.panel-title').html(
                '<i class="fa fa-credit-card"></i> Cobrança #' + newNumber +
                '<span class="entry-charge-badge" style="' + (index === 0 ? '' : 'display: none;') + '">' +
                '<span class="label label-primary" style="margin-left: 10px;">' +
                '<i class="fa fa-star"></i> Entrada' +
                '</span></span>'
            );
            
            // Update form field names
            $charge.find('input[name*="[amount]"]').attr('name', 'charges[' + index + '][amount]');
            $charge.find('input[name*="[due_date]"]').attr('name', 'charges[' + index + '][due_date]');
            $charge.find('select[name*="[billing_type]"]').attr('name', 'charges[' + index + '][billing_type]');
            
            // Update data attributes
            $charge.attr('data-actual-index', newNumber);
        });
        
        // Update next charge number for new charges
        nextChargeNumber = $charges.length + 1;
    }
    
    /**
     * Format money
     */
    function formatMoney(amount) {
        return parseFloat(amount).toFixed(2);
    }
}); 