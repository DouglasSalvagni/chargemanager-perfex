<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Hooks para adicionar coluna de status de cobrança na tabela de contratos
hooks()->add_filter('contracts_table_sql_columns', 'chargemanager_add_billing_status_column');
hooks()->add_filter('contracts_table_columns', 'chargemanager_add_billing_status_header');
hooks()->add_filter('contracts_table_row_data', 'chargemanager_add_billing_status_data', 10, 2);

// Hook para adicionar filtro de billing_status na tabela de contratos
hooks()->add_filter('table_contracts_rules', 'chargemanager_add_billing_status_filter');

// Adiciona a coluna SQL para buscar o status de cobrança
function chargemanager_add_billing_status_column($aColumns)
{
    // Debug: Log da subconsulta
    $subquery = "(SELECT status FROM " . db_prefix() . "chargemanager_billing_groups WHERE contract_id = " . db_prefix() . "contracts.id ORDER BY created_at DESC LIMIT 1) as billing_status";

    $aColumns[] = $subquery;

    return $aColumns;
}


// Adiciona o cabeçalho da coluna na interface
function chargemanager_add_billing_status_header($table_data)
{
    $table_data[] = 'Status de Cobrança';
    return $table_data;
}

// Adiciona os dados da linha para a coluna de status de cobrança
function chargemanager_add_billing_status_data($row, $aRow = [])
{

    $billing_status = null;

    // Tentar acessar billing_status por diferentes métodos
    if (isset($aRow['billing_status'])) {
        $billing_status = $aRow['billing_status'];
    } else {
        // Se não encontrou no $aRow, buscar diretamente no banco de dados
        if (isset($aRow['id']) && !empty($aRow['id'])) {
            $CI = &get_instance();
            $CI->load->database();

            $query = $CI->db->query(
                "SELECT status FROM " . db_prefix() . "chargemanager_billing_groups " .
                    "WHERE contract_id = ? ORDER BY created_at DESC LIMIT 1",
                [$aRow['id']]
            );
        }
    }

    if (empty($billing_status) || is_null($billing_status)) {
        $status_label = '<span class="label label-default">Não assinado</span>';
    } else {
        // Define as cores baseadas no status
        $status_class = 'label-default';
        $status_text = ucfirst($billing_status);

        switch ($billing_status) {
            case 'open':
                $status_class = 'label-info';
                $status_text = 'Aberto';
                break;
            case 'incomplete':
                $status_class = 'label-warning';
                $status_text = 'Incompleto';
                break;
            case 'cancelled':
                $status_class = 'label-danger';
                $status_text = 'Cancelado';
                break;
            case 'partial_on_track':
                $status_class = 'label-primary';
                $status_text = 'Parcial - No Prazo';
                break;
            case 'partial_over':
                $status_class = 'label-warning';
                $status_text = 'Parcial - Acima';
                break;
            case 'partial_under':
                $status_class = 'label-info';
                $status_text = 'Parcial - Abaixo';
                break;
            case 'overdue_on_track':
                $status_class = 'label-danger';
                $status_text = 'Vencido - No Prazo';
                break;
            case 'overdue_over':
                $status_class = 'label-danger';
                $status_text = 'Vencido - Acima';
                break;
            case 'overdue_under':
                $status_class = 'label-danger';
                $status_text = 'Vencido - Abaixo';
                break;
            case 'completed_exact':
                $status_class = 'label-success';
                $status_text = 'Completo - Exato';
                break;
            case 'completed_over':
                $status_class = 'label-success';
                $status_text = 'Completo - Acima';
                break;
            case 'completed_under':
                $status_class = 'label-success';
                $status_text = 'Completo - Abaixo';
                break;
            // Legacy statuses for backwards compatibility
            case 'partial':
                $status_class = 'label-primary';
                $status_text = 'Parcial';
                break;
            case 'completed':
                $status_class = 'label-success';
                $status_text = 'Completo';
                break;
            case 'overdue':
                $status_class = 'label-danger';
                $status_text = 'Vencido';
                break;
        }

        $status_label = '<span class="label ' . $status_class . '">' . $status_text . '</span>';
    }

    $row[] = $status_label;
    return $row;
}

// Função para adicionar filtro de billing_status na tabela de contratos
function chargemanager_add_billing_status_filter($rules)
{
    // Adicionar filtro MultiSelectRule para billing_status
    $rules[] = App_table_filter::new('billing_status', 'MultiSelectRule')
        ->label('Status de Cobrança')
        ->options(function ($ci) {
            return [
                ['value' => 'open', 'label' => 'Aberto'],
                ['value' => 'incomplete', 'label' => 'Incompleto'],
                ['value' => 'cancelled', 'label' => 'Cancelado'],
                ['value' => 'partial_on_track', 'label' => 'Parcial no Prazo'],
                ['value' => 'partial_over', 'label' => 'Parcial Acima'],
                ['value' => 'partial_under', 'label' => 'Parcial Abaixo'],
                ['value' => 'overdue_on_track', 'label' => 'Vencido no Prazo'],
                ['value' => 'overdue_over', 'label' => 'Vencido Acima'],
                ['value' => 'overdue_under', 'label' => 'Vencido Abaixo'],
                ['value' => 'completed_exact', 'label' => 'Completo Exato'],
                ['value' => 'completed_over', 'label' => 'Completo Acima'],
                ['value' => 'completed_under', 'label' => 'Completo Abaixo'],
                ['value' => 'partial', 'label' => 'Parcial'],
                ['value' => 'completed', 'label' => 'Completo'],
                ['value' => 'overdue', 'label' => 'Vencido']
            ];
        })
        ->raw(function ($value, $operator) {
            if ($operator == 'in') {
                $values = implode("','", $value);
                return "(SELECT status FROM " . db_prefix() . "chargemanager_billing_groups WHERE contract_id = " . db_prefix() . "contracts.id ORDER BY created_at DESC LIMIT 1) IN ('$values')";
            } else {
                $values = implode("','", $value);
                return "(SELECT status FROM " . db_prefix() . "chargemanager_billing_groups WHERE contract_id = " . db_prefix() . "contracts.id ORDER BY created_at DESC LIMIT 1) NOT IN ('$values')";
            }
        });

    return $rules;
}
