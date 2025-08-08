<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Contract Billing Schema Helper
 * Adiciona campos para configuração de cobranças na página de edição de contratos
 */

// Criar tabela para armazenar os schemas de cobrança
function create_contract_billing_schema_table()
{
    $CI = &get_instance();

    // Verificar se a tabela já existe
    if (!$CI->db->table_exists(db_prefix() . 'chargemanager_contract_billing_schemas')) {
        $CI->db->query("
            CREATE TABLE `" . db_prefix() . "chargemanager_contract_billing_schemas` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `contract_id` int(11) NOT NULL,
              `schema_type` varchar(20) NOT NULL DEFAULT 'manual', /* 'auto' ou 'manual' */
              `frequency` varchar(20) NULL DEFAULT NULL, /* 'weekly', 'biweekly', 'monthly', etc. */
              `installment_value` decimal(15,2) NULL DEFAULT NULL,
              `first_installment_date` date NULL DEFAULT NULL, /* Data da primeira parcela */
              `schema_data` LONGTEXT NULL, /* JSON com o schema de cobranças */
              `created_at` datetime NOT NULL,
              `updated_at` datetime NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `contract_id` (`contract_id`),
              CONSTRAINT `contract_billing_schema_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `" . db_prefix() . "contracts` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        log_activity('Contract Billing Schema: Tabela criada com sucesso');
    }

    // Verificar se a tabela antiga existe e migrar dados se necessário
    if ($CI->db->table_exists(db_prefix() . 'contract_billing_schemas')) {
        // Verificar se há dados para migrar
        $old_schemas = $CI->db->get(db_prefix() . 'contract_billing_schemas')->result_array();

        if (!empty($old_schemas)) {
            foreach ($old_schemas as $schema) {
                // Verificar se já existe na nova tabela
                $CI->db->where('contract_id', $schema['contract_id']);
                $existing = $CI->db->get(db_prefix() . 'chargemanager_contract_billing_schemas')->row();

                if (!$existing) {
                    // Inserir na nova tabela
                    $CI->db->insert(db_prefix() . 'chargemanager_contract_billing_schemas', [
                        'contract_id' => $schema['contract_id'],
                        'schema_type' => $schema['schema_type'],
                        'frequency' => $schema['frequency'],
                        'installment_value' => $schema['installment_value'],
                        'first_installment_date' => $schema['first_installment_date'] ?? null,
                        'schema_data' => $schema['schema_data'],
                        'created_at' => $schema['created_at'],
                        'updated_at' => $schema['updated_at']
                    ]);
                }
            }

            log_activity('Contract Billing Schema: Dados migrados da tabela antiga para a nova');
        }
    }
}

// Registrar a criação da tabela na inicialização do app
hooks()->add_action('app_init', 'create_contract_billing_schema_table');

/**
 * Adiciona campos para configuração de cobranças na página de edição de contratos
 */
function add_contract_billing_schema_fields($contract = null)
{
    $CI = &get_instance();
    $contract_id = isset($contract) ? $contract->id : null;

    // Verificar se o contrato está assinado
    $is_contract_signed = false;
    if ($contract) {
        $is_contract_signed = ($contract->signed == 1) || ($contract->marked_as_signed == 1);
    }

    // Carregar schema existente se estiver editando um contrato
    $schema = null;
    if ($contract_id) {
        $CI->db->where('contract_id', $contract_id);
        $schema = $CI->db->get(db_prefix() . 'chargemanager_contract_billing_schemas')->row();
    }

    // Determinar o tipo de schema (auto ou manual)
    $schema_type = $schema ? $schema->schema_type : 'manual';
    $frequency = $schema ? $schema->frequency : '';
    $installment_value = $schema ? $schema->installment_value : '';
    $schema_data = $schema ? $schema->schema_data : '[]';

    // Adicionar CSS
    echo '<style>
        .billing-schema-container {
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background-color: #f9f9f9;
        }
        .billing-schema-container.frozen {
            background-color: #f5f5f5;
            border-color: #ccc;
            opacity: 0.8;
        }
        .billing-schema-container.frozen input,
        .billing-schema-container.frozen select,
        .billing-schema-container.frozen button {
            pointer-events: none;
            opacity: 0.6;
        }
        .frozen-notice {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .frozen-notice i {
            margin-right: 5px;
        }
        .schema-type-selector {
            margin-bottom: 15px;
        }
        .schema-config {
            margin-bottom: 15px;
        }
        .charges-list {
            margin-top: 15px;
        }
        .charge-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            background-color: #fff;
        }
        .entry-charge-item {
            border-left: 4px solid #007bff !important;
            background-color: #f8f9fa;
        }
        .entry-charge-badge {
            margin-left: 10px;
            color: #f8f9fa;
            background-color: #007bff;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .remove-charge {
            margin-top: 5px;
        }
        .remove-charge:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .validation-status {
            margin-top: 10px;
        }
    </style>';

    // Início do container principal
    echo '<div class="billing-schema-container' . ($is_contract_signed ? ' frozen' : '') . '">';
    echo '<h4 class="font-medium text-muted">Configuração de Cobranças</h4>';
    echo '<hr class="hr-panel-separator" />';

    // Mostrar aviso se o contrato estiver assinado
    if ($is_contract_signed) {
        echo '<div class="frozen-notice">';
        echo '<i class="fa fa-lock"></i>';
        echo '<strong>Contrato Assinado:</strong> A configuração de cobranças não pode ser editada pois o contrato já foi assinado.';
        echo '</div>';
    }

    // Campo oculto para armazenar o schema JSON
    echo '<input type="hidden" name="schema_data" id="schema_data" value="' . htmlspecialchars($schema_data) . '">';

    // Seletor de tipo de schema
    echo '<div class="schema-type-selector">';
    echo '<div class="row">';
    echo '<div class="col-md-12">';
    echo '<div class="form-group">';
    echo '<label for="schema_type" class="control-label">Tipo de Configuração</label>';
    echo '<select name="schema_type" id="schema_type" class="form-control selectpicker">';
    echo '<option value="manual" ' . ($schema_type == 'manual' ? 'selected' : '') . '>Manual (definir cada cobrança)</option>';
    echo '<option value="auto" ' . ($schema_type == 'auto' ? 'selected' : '') . '>Automático (baseado em frequência)</option>';
    echo '</select>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>'; // Fim do seletor de tipo

    // Configuração automática (frequência)
    echo '<div id="auto-schema-config" class="schema-config" style="' . ($schema_type == 'auto' ? '' : 'display: none;') . '">';
    echo '<div class="row">';

    // Frequência
    echo '<div class="col-md-6">';
    echo '<div class="form-group">';
    echo '<label for="frequency" class="control-label">Frequência</label>';
    echo '<select name="frequency" id="frequency" class="form-control selectpicker">';
    echo '<option value="">Selecione</option>';
    echo '<option value="weekly" ' . ($frequency == 'weekly' ? 'selected' : '') . '>Semanal</option>';
    echo '<option value="biweekly" ' . ($frequency == 'biweekly' ? 'selected' : '') . '>Quinzenal</option>';
    echo '<option value="monthly" ' . ($frequency == 'monthly' ? 'selected' : '') . '>Mensal</option>';
    echo '</select>';
    echo '</div>';
    echo '</div>';

    // Valor da parcela
    echo '<div class="col-md-6">';
    echo '<div class="form-group">';
    echo '<label for="installment_value" class="control-label">Valor por Parcela</label>';
    echo '<div class="input-group">';
    echo '<div class="input-group-addon">' . get_base_currency()->symbol . '</div>';
    echo '<input type="number" name="installment_value" id="installment_value" class="form-control" step="0.01" value="' . $installment_value . '">';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Data da primeira parcela
    $first_installment_date = $schema ? $schema->first_installment_date : date('Y-m-d', strtotime('+1 week'));
    echo '<div class="col-md-6">';
    echo '<div class="form-group">';
    echo '<label for="first_installment_date" class="control-label">Data da 1ª Parcela</label>';
    echo '<input type="date" name="first_installment_date" id="first_installment_date" class="form-control" value="' . $first_installment_date . '">';
    echo '</div>';
    echo '</div>';

    // Botão para gerar schema
    echo '<div class="col-md-6">';
    echo '<div class="form-group" style="margin-top: 25px;">';
    echo '<button type="button" id="generate-schema" class="btn btn-info">Gerar Parcelas</button>';
    echo '</div>';
    echo '</div>';

    echo '</div>'; // Fim da row
    echo '</div>'; // Fim da configuração automática

    // Configuração manual (apenas o botão de adicionar cobrança)
    echo '<div id="manual-schema-config" class="schema-config" style="' . ($schema_type == 'manual' ? '' : 'display: none;') . '">';
    echo '<div class="text-right" style="margin-top: 10px;">';
    echo '<button type="button" id="add-contract-charge" class="btn btn-info btn-sm">';
    echo '<i class="fa fa-plus"></i> Adicionar Cobrança';
    echo '</button>';
    echo '</div>';
    echo '</div>'; // Fim da configuração manual

    // Área comum para exibição de informações e cobranças
    echo '<div class="charges-container" style="margin-top: 20px;">';

    // Informações de valores
    echo '<div class="row">';
    echo '<div class="col-md-4">';
    echo '<div class="form-group">';
    echo '<label for="contract_value_display" class="control-label">Valor do Contrato</label>';
    echo '<div class="input-group">';
    echo '<div class="input-group-addon">' . get_base_currency()->symbol . '</div>';
    echo '<input type="text" id="contract_value_display" class="form-control" readonly>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Total das cobranças
    echo '<div class="col-md-4">';
    echo '<div class="form-group">';
    echo '<label for="total_charges" class="control-label">Total das Cobranças</label>';
    echo '<div class="input-group">';
    echo '<div class="input-group-addon">' . get_base_currency()->symbol . '</div>';
    echo '<input type="text" id="total_charges" class="form-control" readonly>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Diferença
    echo '<div class="col-md-4">';
    echo '<div class="form-group">';
    echo '<label for="difference" class="control-label">Diferença</label>';
    echo '<div class="input-group">';
    echo '<div class="input-group-addon">' . get_base_currency()->symbol . '</div>';
    echo '<input type="text" id="difference" class="form-control" readonly>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>'; // Fim da row de informações

    // Status de validação
    echo '<div class="row">';
    echo '<div class="col-md-12">';
    echo '<div class="validation-status" id="validation-status">';
    echo '<span class="label label-default">Pendente de Validação</span>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Lista de cobranças
    echo '<div class="charges-list" id="contract-charges-list" style="margin-top: 15px;">';
    // As cobranças serão adicionadas dinamicamente via JavaScript
    echo '</div>';

    echo '</div>'; // Fim da charges-container

    echo '</div>'; // Fim do container principal

    // Template para item de cobrança (será usado pelo JavaScript)
    $currency_symbol = get_base_currency()->symbol;
    echo '<script type="text/html" id="contract-charge-template">
        <div class="charge-item" data-index="{index}">
            <div class="row">
                <div class="col-md-8">
                    <h5>
                        <i class="fa fa-credit-card"></i> Cobrança #{index}
                        <span class="entry-charge-badge" style="display: none;">
                            <i class="fa fa-star"></i> Entrada
                        </span>
                    </h5>
                </div>
                <div class="col-md-4 text-right">
                    <button type="button" class="btn btn-danger btn-xs remove-charge">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Valor</label>
                        <div class="input-group">
                            <div class="input-group-addon">' . htmlspecialchars($currency_symbol) . '</div>
                            <input type="number" class="form-control charge-amount" step="0.01" value="">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Data de Vencimento</label>
                        <input type="date" class="form-control charge-due-date" value="">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tipo de Cobrança</label>
                        <select class="form-control charge-billing-type">
                            <option value="">Selecione</option>
                            <option value="PIX">PIX</option>
                            <option value="BOLETO">Boleto Bancário</option>
                            <option value="CREDIT_CARD">Cartão de Crédito</option>
                        </select>
                    </div>
                </div>
            </div>
            <input type="hidden" class="is-entry-charge" value="0">
        </div>
    </script>';

    // Marcar que o JavaScript deve ser carregado
    if (!defined('CONTRACT_BILLING_SCHEMA_JS_LOADED')) {
        define('CONTRACT_BILLING_SCHEMA_JS_LOADED', true);
        hooks()->add_action('app_admin_footer', 'contract_billing_schema_js');
    }
}

/**
 * Carregar o arquivo JavaScript no footer
 */
function contract_billing_schema_js()
{
    // Obter informações do contrato se estivermos na página de edição
    $is_contract_signed = false;
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $CI = &get_instance();
        $CI->db->where('id', $_GET['id']);
        $contract = $CI->db->get(db_prefix() . 'contracts')->row();
        
        if ($contract) {
            $is_contract_signed = ($contract->signed == 1 || $contract->marked_as_signed == 1);
        }
    }
    
    echo '<script>';
    echo 'window.contractBillingConfig = window.contractBillingConfig || {};';
    echo 'window.contractBillingConfig.isContractSigned = ' . ($is_contract_signed ? 'true' : 'false') . ';';
    echo '</script>';
    echo '<script src="' . module_dir_url('chargemanager', 'assets/js/contract_billing_schema.js') . '"></script>';
}

// Registrar o hook para adicionar campos na edição de contratos
hooks()->add_action('contract_extra_fields', 'add_contract_billing_schema_fields', 20);

/**
 * Processar e salvar os dados do schema de cobrança antes de adicionar um contrato
 */
function process_contract_billing_schema_on_add($data)
{
    // Extrair campos do schema
    $schema_fields = [
        'schema_type',
        'frequency',
        'installment_value',
        'first_installment_date',
        'schema_data'
    ];

    $schema_data = [];
    foreach ($schema_fields as $field) {
        if (isset($data[$field])) {
            $schema_data[$field] = $data[$field];
            unset($data[$field]); // Remover do array de dados para evitar erro SQL
        }
    }

    // Armazenar para uso após a criação do contrato
    $GLOBALS['contract_billing_schema_data'] = $schema_data;

    return $data;
}

/**
 * Salvar o schema de cobrança após a criação do contrato
 */
function save_contract_billing_schema_after_add($contract_id)
{
    if (isset($GLOBALS['contract_billing_schema_data']) && !empty($GLOBALS['contract_billing_schema_data'])) {
        $schema_data = $GLOBALS['contract_billing_schema_data'];

        save_contract_billing_schema($contract_id, $schema_data);

        // Limpar a variável global
        unset($GLOBALS['contract_billing_schema_data']);
    }
}

/**
 * Processar e salvar os dados do schema de cobrança ao atualizar um contrato
 * 
 * @param array $data Os dados do contrato
 * @return array Os dados do contrato filtrados
 */
function process_contract_billing_schema_on_update($data, $id = null)
{
    // Obter o contract_id do array de dados
    $contract_id = null;
    if (isset($id)) {
        $contract_id = $id;
    }

    // Extrair campos do schema
    $schema_fields = [
        'schema_type',
        'frequency',
        'installment_value',
        'first_installment_date',
        'schema_data'
    ];

    $schema_data = [];
    foreach ($schema_fields as $field) {
        if (isset($data[$field])) {
            $schema_data[$field] = $data[$field];
            unset($data[$field]); // Remover do array de dados para evitar erro SQL
        }
    }

    // Se ainda não temos um contract_id, não podemos salvar o schema
    if ($contract_id === null) {
        return $data;
    }

    // Salvar schema se houver dados
    if (!empty($schema_data)) {
        save_contract_billing_schema($contract_id, $schema_data);
    }

    return $data;
}

/**
 * Salvar schema de cobrança na tabela
 */
function save_contract_billing_schema($contract_id, $schema_data)
{
    $CI = &get_instance();

    // Verificar se já existe um schema para este contrato
    $CI->db->where('contract_id', $contract_id);
    $existing = $CI->db->get(db_prefix() . 'chargemanager_contract_billing_schemas')->row();

    $data = [
        'schema_type' => $schema_data['schema_type'] ?? 'manual',
        'frequency' => $schema_data['frequency'] ?? null,
        'installment_value' => $schema_data['installment_value'] ?? null,
        'first_installment_date' => $schema_data['first_installment_date'] ?? null,
        'schema_data' => $schema_data['schema_data'] ?? null,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if ($existing) {
        // Atualizar schema existente
        $CI->db->where('id', $existing->id);
        $CI->db->update(db_prefix() . 'chargemanager_contract_billing_schemas', $data);
    } else {
        // Criar novo schema
        $data['contract_id'] = $contract_id;
        $data['created_at'] = date('Y-m-d H:i:s');
        $CI->db->insert(db_prefix() . 'chargemanager_contract_billing_schemas', $data);
    }

    log_activity('Contract Billing Schema: Schema atualizado para o contrato #' . $contract_id);
}

//Hooks para possibilitar edição de informações ao contrato
hooks()->add_filter('contract_merge_fields', 'merge_payment_method_fields', 10, 2);
hooks()->add_filter('available_merge_fields', 'add_payment_method_option_merge_fields');

function merge_payment_method_fields($fields, $data)
{
    $contract_id = $data['id'];
    $contract = $data['contract'];

    // Buscar dados do seu módulo
    $CI = &get_instance();
    $CI->db->where('contract_id', $contract_id);
    $billing_schema = $CI->db->get(db_prefix() . 'chargemanager_contract_billing_schemas')->row();

    $payment_method_html = '';

    if ($billing_schema && !empty($billing_schema->schema_data)) {
        $charges_data = json_decode($billing_schema->schema_data, true);

        if (!empty($charges_data) && is_array($charges_data)) {
            // Mapeamento dos tipos de cobrança
            $billing_type_map = [
                'PIX' => 'PIX',
                'BOLETO' => 'Boleto Bancário',
                'CREDIT_CARD' => 'Cartão de Crédito'
            ];

            // Obter símbolo da moeda
            $currency_symbol = get_base_currency()->symbol ?? 'R$';

            // Iniciar tabela HTML
            $payment_method_html = '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
            $payment_method_html .= '<thead>';
            $payment_method_html .= '<tr style="background-color: #f8f9fa;">';
            $payment_method_html .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Parcela</th>';
            $payment_method_html .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Valor</th>';
            $payment_method_html .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Vencimento</th>';
            $payment_method_html .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Forma de Pagamento</th>';
            $payment_method_html .= '<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Observações</th>';
            $payment_method_html .= '</tr>';
            $payment_method_html .= '</thead>';
            $payment_method_html .= '<tbody>';

            foreach ($charges_data as $index => $charge) {
                $parcela_numero = $index + 1;

                // Formatar valor monetário
                $valor_formatado = $currency_symbol . ' ' . number_format(floatval($charge['amount']), 2, ',', '.');

                // Formatar data de dd-mm-Y
                $data_vencimento = '';
                if (!empty($charge['due_date'])) {
                    $date = DateTime::createFromFormat('Y-m-d', $charge['due_date']);
                    if ($date) {
                        $data_vencimento = $date->format('d/m/Y');
                    } else {
                        $data_vencimento = $charge['due_date'];
                    }
                }

                // Mapear tipo de cobrança
                $forma_pagamento = $billing_type_map[$charge['billing_type']] ?? $charge['billing_type'];

                // Verificar se é entrada
                $observacoes = '';
                if (isset($charge['is_entry_charge']) && $charge['is_entry_charge'] == 1) {
                    $observacoes = '<strong style="color: #007bff;">Valor de Entrada</strong>';
                }

                // Estilo da linha para entrada
                $row_style = '';
                if (isset($charge['is_entry_charge']) && $charge['is_entry_charge'] == 1) {
                    $row_style = 'background-color: #e3f2fd;';
                }

                $payment_method_html .= '<tr style="' . $row_style . '">';
                $payment_method_html .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . $parcela_numero . 'ª Parcela</td>';
                $payment_method_html .= '<td style="border: 1px solid #dee2e6; padding: 8px; text-align: right;">' . $valor_formatado . '</td>';
                $payment_method_html .= '<td style="border: 1px solid #dee2e6; padding: 8px; text-align: center;">' . $data_vencimento . '</td>';
                $payment_method_html .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . $forma_pagamento . '</td>';
                $payment_method_html .= '<td style="border: 1px solid #dee2e6; padding: 8px;">' . $observacoes . '</td>';
                $payment_method_html .= '</tr>';
            }

            $payment_method_html .= '</tbody>';
            $payment_method_html .= '</table>';

            // Adicionar resumo
            $total_parcelas = count($charges_data);
            $valor_total = array_sum(array_column($charges_data, 'amount'));
            $valor_total_formatado = $currency_symbol . ' ' . number_format($valor_total, 2, ',', '.');

            $payment_method_html .= '<div style="margin-top: 10px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">';
            $payment_method_html .= '<strong>Resumo do Pagamento:</strong><br>';
            $payment_method_html .= 'Total de Parcelas: ' . $total_parcelas . '<br>';
            $payment_method_html .= 'Valor Total: ' . $valor_total_formatado;
            $payment_method_html .= '</div>';
        }
    }

    // Adicionar o novo merge field
    $fields['{forma_de_pagamento}'] = $payment_method_html;

    return $fields;
}

function add_payment_method_option_merge_fields($available)
{
    // Adicionar novos campos na categoria 'contract'
    foreach ($available as $key => $merge_fields) {
        if (isset($merge_fields['contract'])) {
            $available[$key]['contract'][] = [
                'name' => 'Forma de Pagamento',
                'key' => '{forma_de_pagamento}',
                'available' => ['contract'],
                'format' => [
                    'base_name' => 'meu_modulo_merge_fields',
                    'file' => 'meu_modulo/meu_merge_fields'
                ]
            ];
        }
    }

    return $available;
}

// Registrar hooks para processamento de dados
hooks()->add_filter('before_contract_added', 'process_contract_billing_schema_on_add');
hooks()->add_action('after_contract_added', 'save_contract_billing_schema_after_add');
hooks()->add_filter('before_contract_updated', 'process_contract_billing_schema_on_update', 10, 2);


/**
 * Criar cobranças automaticamente quando um contrato é assinado
 */
function create_charges_on_contract_signed($contract_id)
{
    // Verificar se o contrato está assinado
    $CI = &get_instance();
    $CI->db->where('id', $contract_id);
    $contract = $CI->db->get(db_prefix() . 'contracts')->row();

    if (!$contract || ($contract->signed != 1 && $contract->marked_as_signed != 1)) {
        return;
    }

    // Verificar se existe um schema de cobrança
    $CI->db->where('contract_id', $contract_id);
    $schema = $CI->db->get(db_prefix() . 'chargemanager_contract_billing_schemas')->row();

    if (!$schema || empty($schema->schema_data)) {
        return;
    }

    // Verificar se já existe um billing group para este contrato
    $CI->db->where('contract_id', $contract_id);
    $existing_billing_group = $CI->db->get(db_prefix() . 'chargemanager_billing_groups')->row();

    if ($existing_billing_group) {
        log_activity('Contract Billing Schema: Contrato #' . $contract_id . ' já possui um grupo de cobrança (#' . $existing_billing_group->id . ')');
        return;
    }

    // Carregar modelos necessários
    $CI->load->model('chargemanager/chargemanager_billing_groups_model');
    $CI->load->model('chargemanager/chargemanager_charges_model');
    $CI->load->library('chargemanager/Gateway_manager');

    // Decodificar schema de cobranças
    $charges_schema = json_decode($schema->schema_data, true);

    if (empty($charges_schema) || !is_array($charges_schema)) {
        log_activity('Contract Billing Schema: Schema de cobranças inválido para o contrato #' . $contract_id);
        return;
    }

    // Iniciar transação
    $CI->db->trans_begin();

    try {
        // Determinar sale_agent: prioridade para lead staff original, fallback para criador do contrato
        $sale_agent = $CI->chargemanager_billing_groups_model->get_client_original_lead_staff($contract->client);

        if (!$sale_agent) {
            $sale_agent = $contract->addedfrom; // Fallback para o criador do contrato
        }

        // Criar billing group
        $billing_group_data = [
            'client_id' => $contract->client,
            'contract_id' => $contract_id,
            'sale_agent' => $sale_agent,
            'status' => 'open',
            'total_amount' => $contract->contract_value
        ];

        $billing_group_id = $CI->chargemanager_billing_groups_model->create($billing_group_data);

        if (!$billing_group_id) {
            throw new Exception('Falha ao criar grupo de cobrança');
        }

        log_activity('Contract Billing Schema: Grupo de cobrança #' . $billing_group_id . ' criado para o contrato #' . $contract_id);

        // Criar cobranças baseadas no schema
        $charges_created = [];
        $charges_failed = [];

        foreach ($charges_schema as $index => $charge_data) {
            try {
                // Verificar dados obrigatórios
                if (empty($charge_data['amount']) || empty($charge_data['due_date']) || empty($charge_data['billing_type'])) {
                    $charges_failed[] = 'Cobrança #' . ($index + 1) . ': Dados incompletos';
                    continue;
                }

                // Obter customer_id do gateway
                $customer_result = $CI->gateway_manager->get_or_create_customer($contract->client);

                if (!$customer_result['success']) {
                    $charges_failed[] = 'Cobrança #' . ($index + 1) . ': Falha ao obter cliente no gateway - ' . $customer_result['message'];
                    continue;
                }

                // Preparar dados para o gateway
                $gateway_charge_data = [
                    'billing_group_id' => $billing_group_id,
                    'client_id' => $contract->client,
                    'sale_agent' => $billing_group_data['sale_agent'],
                    'value' => floatval($charge_data['amount']),
                    'due_date' => $charge_data['due_date'],
                    'billing_type' => $charge_data['billing_type'],
                    'description' => 'Cobrança automática via ChargeManager - Contrato #' . $contract_id . ' - Billing Group #' . $billing_group_id,
                    'gateway' => 'asaas' // Especificar o gateway
                ];

                // Criar cobrança no gateway
                $gateway_result = $CI->gateway_manager->create_charge($gateway_charge_data);

                if (!$gateway_result['success']) {
                    $charges_failed[] = 'Cobrança #' . ($index + 1) . ': Falha no gateway - ' . $gateway_result['message'];
                    continue;
                }

                // Salvar cobrança no banco de dados local
                $local_charge_data = [
                    'gateway_charge_id' => $gateway_result['charge_id'],
                    'gateway' => $gateway_result['gateway'] ?? 'asaas',
                    'billing_group_id' => $billing_group_id,
                    'client_id' => $contract->client,
                    'sale_agent' => $billing_group_data['sale_agent'],
                    'value' => floatval($charge_data['amount']),
                    'due_date' => $charge_data['due_date'],
                    'billing_type' => $charge_data['billing_type'],
                    'status' => 'pending',
                    'is_entry_charge' => isset($charge_data['is_entry_charge']) ? intval($charge_data['is_entry_charge']) : (($index === 0) ? 1 : 0),
                    'invoice_url' => $gateway_result['invoice_url'] ?? null,
                    'barcode' => $gateway_result['barcode'] ?? null,
                    'pix_code' => $gateway_result['pix_code'] ?? null,
                    'description' => 'Cobrança automática via ChargeManager - Contrato #' . $contract_id
                ];

                $local_charge_id = $CI->chargemanager_charges_model->create($local_charge_data);

                if ($local_charge_id) {
                    $charges_created[] = [
                        'gateway_id' => $gateway_result['charge_id'],
                        'local_id' => $local_charge_id
                    ];

                    // Gerar fatura individual para a cobrança
                    $invoice_result = $CI->chargemanager_charges_model->generate_individual_invoice($local_charge_id);

                    if ($invoice_result && $invoice_result['success']) {
                        log_activity('ChargeManager: Fatura #' . $invoice_result['invoice_id'] . ' criada para cobrança #' . $local_charge_id);
                    }
                } else {
                    $charges_failed[] = 'Cobrança #' . ($index + 1) . ': Falha ao salvar no banco de dados local';
                }
            } catch (Exception $e) {
                $charges_failed[] = 'Cobrança #' . ($index + 1) . ': ' . $e->getMessage();
            }
        }

        // Verificar se alguma cobrança foi criada com sucesso
        if (empty($charges_created)) {
            // Nenhuma cobrança criada, reverter transação
            $CI->db->trans_rollback();
            log_activity('Contract Billing Schema: Falha ao criar cobranças para o contrato #' . $contract_id . ' - ' . implode(', ', $charges_failed));
            return;
        }

        // Atualizar status do billing group
        $CI->chargemanager_billing_groups_model->refresh_status($billing_group_id);

        // Confirmar transação
        $CI->db->trans_commit();

        // Log de sucesso
        $success_message = 'Contract Billing Schema: ' . count($charges_created) . ' cobrança(s) criada(s) com sucesso para o contrato #' . $contract_id;

        if (!empty($charges_failed)) {
            $success_message .= ' (' . count($charges_failed) . ' falha(s))';
        }

        log_activity($success_message);
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        $CI->db->trans_rollback();
        log_activity('Contract Billing Schema: Erro ao criar cobranças para o contrato #' . $contract_id . ' - ' . $e->getMessage());
    }
}

// Registrar hooks para criação de cobranças quando um contrato é assinado
hooks()->add_action('after_contract_was_signed', 'create_charges_on_contract_signed');
hooks()->add_action('after_contract_was_marked_as_signed', 'create_charges_on_contract_signed');
