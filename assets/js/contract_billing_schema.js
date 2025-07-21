/**
 * Contract Billing Schema JavaScript
 * Handles dynamic charge creation and schema generation for contracts
 */

$(document).ready(function () {
    'use strict';

    var chargeIndex = 0;
    var contractValue = 0;
    var schemaData = [];

    // Inicializar com o valor do contrato
    function initContractValue() {
        // Obter valor do contrato do campo de entrada
        // Primeiro tenta obter do objeto contract se estiver editando
        if (typeof contract_value !== 'undefined') {
            contractValue = parseFloat(contract_value) || 0;
        } else {
            // Se não estiver definido, tenta obter do campo de entrada
            contractValue = parseFloat($("input[name='contract_value']").val()) || 0;
        }

        $("#contract_value_display").val(formatMoney(contractValue));

        // Se já temos um schema salvo, carregar
        var savedSchema = $("#schema_data").val();
        if (savedSchema) {
            try {
                schemaData = JSON.parse(savedSchema);
                renderSavedSchema();
            } catch (e) {
                console.error("Erro ao carregar schema:", e);
            }
        }

        calculateTotals();
    }

    // Renderizar schema salvo
    function renderSavedSchema() {
        $("#charges-list").empty();
        chargeIndex = 0;

        if (schemaData && schemaData.length > 0) {
            $.each(schemaData, function (i, charge) {
                addCharge(charge);
            });

            updateEntryChargeStatus();
        }
    }

    // Alternar entre configuração manual e automática
    $("#schema_type").on("change", function () {
        var type = $(this).val();

        if (type === "auto") {
            $("#manual-schema-config").hide();
            $("#auto-schema-config").show();
        } else {
            $("#auto-schema-config").hide();
            $("#manual-schema-config").show();
        }

        // A lista de cobranças é sempre visível
        $(".charges-container").show();
    });

    // Gerar schema baseado na frequência
    $("#generate-schema").on("click", function () {
        var frequency = $("#frequency").val();
        var installmentValue = parseFloat($("#installment_value").val()) || 0;
        var firstInstallmentDate = $("#first_installment_date").val();

        if (!frequency || installmentValue <= 0) {
            alert("Por favor, selecione uma frequência e informe o valor da parcela.");
            return;
        }

        if (!firstInstallmentDate) {
            alert("Por favor, selecione a data da primeira parcela.");
            return;
        }

        generateAutoSchema(frequency, installmentValue, firstInstallmentDate);
    });

    // Gerar schema automático
    function generateAutoSchema(frequency, installmentValue, firstInstallmentDate) {
        if (contractValue <= 0 || installmentValue <= 0) {
            alert("Valor do contrato e valor da parcela devem ser maiores que zero.");
            return;
        }

        // Calcular número de parcelas
        var numInstallments = Math.ceil(contractValue / installmentValue);

        // Ajustar valor da última parcela se necessário
        var lastInstallmentValue = contractValue - (installmentValue * (numInstallments - 1));

        // Limpar cobranças existentes
        $("#charges-list").empty();
        schemaData = [];
        chargeIndex = 0;

        // Data base (data da primeira parcela escolhida pelo usuário)
        var baseDate = new Date(firstInstallmentDate);

        // Gerar parcelas
        for (var i = 0; i < numInstallments; i++) {
            var dueDate = new Date(baseDate);

            // Calcular data de vencimento baseada na frequência
            // Para a primeira parcela (i=0), usar a data base
            // Para as demais, calcular baseado na frequência
            if (i > 0) {
                switch (frequency) {
                    case "weekly":
                        dueDate.setDate(baseDate.getDate() + (7 * i));
                        break;
                    case "biweekly":
                        dueDate.setDate(baseDate.getDate() + (14 * i));
                        break;
                    case "monthly":
                        dueDate.setMonth(baseDate.getMonth() + i);
                        break;
                }
            }

            // Formatar data para YYYY-MM-DD
            var formattedDate = dueDate.toISOString().split("T")[0];

            // Valor da parcela (última parcela pode ser diferente)
            var amount = (i === numInstallments - 1) ? lastInstallmentValue : installmentValue;

            // Adicionar cobrança
            addCharge({
                amount: amount,
                due_date: formattedDate,
                billing_type: "BOLETO", // Tipo padrão
                is_entry_charge: (i === 0) ? 1 : 0
            });
        }

        updateEntryChargeStatus();
        calculateTotals();
        updateSchemaJson();
    }

    // Adicionar cobrança
    $("#add-contract-charge").on("click", function () {
        addCharge();
        updateEntryChargeStatus();
        calculateTotals();
        updateSchemaJson();
    });

    // Remover cobrança (evento delegado)
    $(document).on("click", ".remove-charge", function () {
        var $chargeItem = $(this).closest(".charge-item");
        var isEntryCharge = $chargeItem.find(".is-entry-charge").val() === "1";

        // Impedir remoção da cobrança de entrada se houver mais de uma cobrança
        if (isEntryCharge && $(".charge-item").length > 1) {
            alert("A cobrança de entrada não pode ser removida. Para remover, primeiro defina outra cobrança como entrada.");
            return;
        }

        $chargeItem.remove();
        updateEntryChargeStatus();
        calculateTotals();
        updateSchemaJson();
    });

    // Atualizar totais quando valores são alterados
    $(document).on("input", ".charge-amount", function () {
        calculateTotals();
        updateSchemaJson();
    });

    $(document).on("change", ".charge-due-date, .charge-billing-type", function () {
        updateSchemaJson();
    });

    // Adicionar cobrança
    function addCharge(data) {
        chargeIndex++;
        var template = $("#charge-template").html();
        template = template.replace(/{index}/g, chargeIndex);

        var $charge = $(template);
        $("#charges-list").append($charge);

        // Preencher dados se fornecidos
        if (data) {
            $charge.find(".charge-amount").val(data.amount);
            $charge.find(".charge-due-date").val(data.due_date);
            $charge.find(".charge-billing-type").val(data.billing_type);
            $charge.find(".is-entry-charge").val(data.is_entry_charge);

            if (data.is_entry_charge === 1) {
                $charge.addClass("entry-charge-item");
                $charge.find(".entry-charge-badge").show();
            }
        }

        return $charge;
    }

    // Atualizar status de cobrança de entrada
    function updateEntryChargeStatus() {
        var $charges = $(".charge-item");

        // Remover todas as marcações de entrada primeiro
        $charges.removeClass("entry-charge-item");
        $charges.find(".entry-charge-badge").hide();
        $charges.find(".is-entry-charge").val("0");
        $charges.find(".remove-charge").prop("disabled", false).prop("title", "");

        if ($charges.length > 0) {
            // Marcar primeira cobrança como entrada
            var $firstCharge = $charges.first();
            $firstCharge.addClass("entry-charge-item");
            $firstCharge.find(".entry-charge-badge").show();
            $firstCharge.find(".is-entry-charge").val("1");

            // Se houver múltiplas cobranças, desabilitar remoção da entrada
            if ($charges.length > 1) {
                $firstCharge.find(".remove-charge").prop("disabled", true).prop("title", "A cobrança de entrada não pode ser removida");
            }
        }
    }

    // Calcular totais
    function calculateTotals() {
        var totalCharges = 0;

        $(".charge-amount").each(function () {
            var amount = parseFloat($(this).val()) || 0;
            totalCharges += amount;
        });

        $("#total_charges").val(formatMoney(totalCharges));

        var difference = contractValue - totalCharges;
        $("#difference").val(formatMoney(difference));

        updateValidationStatus(difference, totalCharges);
    }

    // Atualizar status de validação
    function updateValidationStatus(difference, totalCharges) {
        var $status = $("#validation-status");

        if (totalCharges === 0) {
            $status.html('<span class="label label-default">Pendente de Validação</span>');
        } else if (Math.abs(difference) < 0.01) { // Considerar precisão de ponto flutuante
            $status.html('<span class="label label-success">Válido</span>');
        } else if (difference > 0) {
            $status.html('<span class="label label-warning">Cobranças abaixo do valor do contrato (R$ ' + formatMoney(Math.abs(difference)) + ' restantes)</span>');
        } else {
            $status.html('<span class="label label-danger">Cobranças excedem o valor do contrato (R$ ' + formatMoney(Math.abs(difference)) + ' a mais)</span>');
        }
    }

    // Atualizar JSON do schema
    function updateSchemaJson() {
        var schema = [];

        $(".charge-item").each(function (index) {
            var $charge = $(this);

            schema.push({
                amount: parseFloat($charge.find(".charge-amount").val()) || 0,
                due_date: $charge.find(".charge-due-date").val(),
                billing_type: $charge.find(".charge-billing-type").val(),
                is_entry_charge: parseInt($charge.find(".is-entry-charge").val()) || 0
            });
        });

        $("#schema_data").val(JSON.stringify(schema));
        schemaData = schema;
    }

    // Formatar valor monetário
    function formatMoney(amount) {
        return parseFloat(amount).toFixed(2);
    }

    // Monitorar mudanças no campo contract_value
    $("input[name='contract_value']").on("input", function () {
        contractValue = parseFloat($(this).val()) || 0;
        $("#contract_value_display").val(formatMoney(contractValue));
        calculateTotals();
    });

    // Inicializar na carga da página
    setTimeout(function () {
        initContractValue();
    }, 500);
});