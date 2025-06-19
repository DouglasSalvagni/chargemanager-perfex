<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Module General
$lang['chargemanager'] = 'ChargeManager';
$lang['chargemanager_settings'] = 'Configurações do ChargeManager';
$lang['chargemanager_billing_groups'] = 'Grupos de Cobrança';

// Menu
$lang['chargemanager_menu_settings'] = 'ChargeManager - Configurações';

// Settings
$lang['chargemanager_asaas_settings'] = 'Configurações ASAAS';
$lang['chargemanager_general_settings'] = 'Configurações Gerais';
$lang['chargemanager_api_key'] = 'Chave da API';
$lang['chargemanager_api_key_placeholder'] = 'Insira sua chave da API ASAAS';
$lang['chargemanager_api_key_required'] = 'Chave da API é obrigatória';
$lang['chargemanager_api_key_minlength'] = 'Chave da API deve ter pelo menos 10 caracteres';
$lang['chargemanager_environment'] = 'Ambiente';
$lang['chargemanager_sandbox'] = 'Sandbox (Teste)';
$lang['chargemanager_production'] = 'Produção';
$lang['chargemanager_environment_help'] = 'Use Sandbox para testes e Produção para transações reais';
$lang['chargemanager_webhook_url'] = 'URL do Webhook';
$lang['chargemanager_webhook_help'] = 'Configure esta URL no painel ASAAS para receber notificações de pagamento';
$lang['chargemanager_show_hide'] = 'Mostrar/Ocultar';
$lang['chargemanager_save_settings'] = 'Salvar Configurações';
$lang['chargemanager_test_connection'] = 'Testar Conexão';
$lang['chargemanager_clear_logs'] = 'Limpar Logs';
$lang['chargemanager_click_test_connection'] = 'Clique em "Testar Conexão" para verificar suas configurações';
$lang['chargemanager_testing'] = 'Testando...';
$lang['chargemanager_connection_failed'] = 'Falha na conexão com ASAAS';
$lang['chargemanager_confirm_clear_logs'] = 'Tem certeza que deseja limpar todos os logs?';
$lang['chargemanager_copied_to_clipboard'] = 'Copiado para a área de transferência';

// General Settings
$lang['chargemanager_auto_sync_clients'] = 'Sincronização Automática de Clientes';
$lang['chargemanager_auto_sync_clients_help'] = 'Sincronizar automaticamente clientes com o gateway';
$lang['chargemanager_auto_create_invoices'] = 'Criação Automática de Faturas';
$lang['chargemanager_auto_create_invoices_help'] = 'Criar faturas automaticamente quando pagamentos são recebidos';
$lang['chargemanager_debug_mode'] = 'Modo Debug';
$lang['chargemanager_debug_mode_help'] = 'Registrar informações detalhadas para depuração';
$lang['chargemanager_default_billing_type'] = 'Tipo de Cobrança Padrão';

// Billing Types
$lang['chargemanager_billing_type'] = 'Tipo de Cobrança';
$lang['chargemanager_billing_type_boleto'] = 'Boleto Bancário';
$lang['chargemanager_billing_type_pix'] = 'PIX';
$lang['chargemanager_billing_type_credit_card'] = 'Cartão de Crédito';
$lang['chargemanager_select_billing_type'] = 'Selecione o tipo de cobrança';

// Billing Groups
$lang['chargemanager_create_billing_group'] = 'Criar Grupo de Cobrança';
$lang['chargemanager_billing_group_name'] = 'Nome do Grupo';
$lang['chargemanager_total_value'] = 'Valor Total';
$lang['chargemanager_due_date'] = 'Data de Vencimento';
$lang['chargemanager_description'] = 'Descrição';
$lang['chargemanager_description_placeholder'] = 'Descrição opcional do grupo de cobrança';
$lang['chargemanager_contracts'] = 'Contratos';
$lang['chargemanager_loading_contracts'] = 'Carregando contratos...';
$lang['chargemanager_select_contracts_help'] = 'Selecione os contratos que farão parte deste grupo';
$lang['chargemanager_selected_contracts'] = 'Contratos Selecionados';
$lang['chargemanager_no_contracts_available'] = 'Nenhum contrato disponível para este cliente';
$lang['chargemanager_select_at_least_one_contract'] = 'Selecione pelo menos um contrato';

// Billing Group Status
$lang['chargemanager_status'] = 'Status';
$lang['chargemanager_status_open'] = 'Aberto';
$lang['chargemanager_status_processing'] = 'Processando';
$lang['chargemanager_status_completed'] = 'Concluído';
$lang['chargemanager_status_partial'] = 'Parcial';
$lang['chargemanager_status_paid'] = 'Pago';
$lang['chargemanager_status_cancelled'] = 'Cancelado';

// Actions
$lang['chargemanager_actions'] = 'Ações';
$lang['chargemanager_view'] = 'Visualizar';
$lang['chargemanager_cancel'] = 'Cancelar';
$lang['chargemanager_confirm_cancel_billing_group'] = 'Tem certeza que deseja cancelar este grupo de cobrança?';

// Billing Group Details
$lang['chargemanager_billing_group_details'] = 'Detalhes do Grupo de Cobrança';
$lang['chargemanager_billing_group_info'] = 'Informações do Grupo';
$lang['chargemanager_client'] = 'Cliente';
$lang['chargemanager_created_at'] = 'Criado em';
$lang['chargemanager_payment_summary'] = 'Resumo de Pagamentos';
$lang['chargemanager_total_charges'] = 'Total de Cobranças';
$lang['chargemanager_total_paid'] = 'Total Pago';
$lang['chargemanager_remaining'] = 'Restante';
$lang['chargemanager_progress'] = 'Progresso';
$lang['chargemanager_associated_contracts'] = 'Contratos Associados';
$lang['chargemanager_no_contracts_associated'] = 'Nenhum contrato associado';

// Charges
$lang['chargemanager_charges'] = 'Cobranças';
$lang['chargemanager_charge_id'] = 'ID da Cobrança';
$lang['chargemanager_value'] = 'Valor';
$lang['chargemanager_payment_date'] = 'Data de Pagamento';
$lang['chargemanager_invoice'] = 'Fatura';
$lang['chargemanager_no_charges_found'] = 'Nenhuma cobrança encontrada';
$lang['chargemanager_view_invoice'] = 'Ver Fatura';
$lang['chargemanager_view_barcode'] = 'Ver Código de Barras';
$lang['chargemanager_view_pix_code'] = 'Ver Código PIX';

// Charge Status
$lang['chargemanager_charge_status_pending'] = 'Pendente';
$lang['chargemanager_charge_status_received'] = 'Recebido';
$lang['chargemanager_charge_status_overdue'] = 'Vencido';
$lang['chargemanager_charge_status_cancelled'] = 'Cancelado';

// Payment Details
$lang['chargemanager_barcode'] = 'Código de Barras';
$lang['chargemanager_barcode_number'] = 'Número do Código de Barras';
$lang['chargemanager_pix_code'] = 'Código PIX';
$lang['chargemanager_pix_copy_paste'] = 'Código PIX (Copiar e Colar)';

// Logs
$lang['chargemanager_recent_logs'] = 'Logs Recentes';
$lang['chargemanager_activity_log'] = 'Log de Atividades';
$lang['chargemanager_log_date'] = 'Data';
$lang['chargemanager_log_type'] = 'Tipo';
$lang['chargemanager_log_message'] = 'Mensagem';
$lang['chargemanager_log_status'] = 'Status';
$lang['chargemanager_no_logs'] = 'Nenhum log encontrado';
$lang['chargemanager_success'] = 'Sucesso';
$lang['chargemanager_error'] = 'Erro';

// Messages
$lang['chargemanager_settings_saved'] = 'Configurações salvas com sucesso';
$lang['chargemanager_billing_group_created'] = 'Grupo de cobrança criado com sucesso';
$lang['chargemanager_billing_group_cancelled'] = 'Grupo de cobrança cancelado com sucesso';
$lang['chargemanager_connection_successful'] = 'Conexão estabelecida com sucesso';
$lang['chargemanager_logs_cleared'] = 'Logs limpos com sucesso';

// Errors
$lang['chargemanager_error_creating_billing_group'] = 'Erro ao criar grupo de cobrança';
$lang['chargemanager_error_cancelling_billing_group'] = 'Erro ao cancelar grupo de cobrança';
$lang['chargemanager_error_loading_data'] = 'Erro ao carregar dados';
$lang['chargemanager_error_saving_settings'] = 'Erro ao salvar configurações';
$lang['chargemanager_error_testing_connection'] = 'Erro ao testar conexão';

// Validation
$lang['chargemanager_validation_required'] = 'Este campo é obrigatório';
$lang['chargemanager_validation_numeric'] = 'Este campo deve ser numérico';
$lang['chargemanager_validation_email'] = 'Formato de email inválido';
$lang['chargemanager_validation_date'] = 'Data inválida';
$lang['chargemanager_validation_min_value'] = 'Valor deve ser maior que zero';

// Permissions
$lang['chargemanager_permission_view'] = 'Ver ChargeManager';
$lang['chargemanager_permission_create'] = 'Criar Grupos de Cobrança';
$lang['chargemanager_permission_edit'] = 'Editar Grupos de Cobrança';
$lang['chargemanager_permission_delete'] = 'Excluir Grupos de Cobrança';
$lang['chargemanager_permission_settings'] = 'Configurar ChargeManager';

// Webhooks
$lang['chargemanager_webhook_received'] = 'Webhook recebido';
$lang['chargemanager_webhook_processed'] = 'Webhook processado com sucesso';
$lang['chargemanager_webhook_failed'] = 'Falha ao processar webhook';
$lang['chargemanager_webhook_invalid'] = 'Webhook inválido';

// Client Tab
$lang['chargemanager_client_tab'] = 'Grupos de Cobrança';
$lang['chargemanager_client_tab_help'] = 'Gerencie grupos de cobrança para este cliente';

// New Billing Group Form
$lang['chargemanager_existing_billing_groups'] = 'Grupos de Cobrança Existentes';
$lang['chargemanager_new_billing_group'] = 'Novo Grupo de Cobrança';
$lang['chargemanager_contract'] = 'Contrato';
$lang['chargemanager_contract_value'] = 'Valor do Contrato';
$lang['chargemanager_add_charge'] = 'Adicionar Cobrança';
$lang['chargemanager_charge'] = 'Cobrança';
$lang['chargemanager_amount'] = 'Valor';
$lang['chargemanager_difference'] = 'Diferença';
$lang['chargemanager_validation_status'] = 'Status de Validação';
$lang['chargemanager_pending_validation'] = 'Validação Pendente';

// Table Headers
$lang['chargemanager_id'] = 'ID';
$lang['chargemanager_options'] = 'Opções';

// Validation Messages
$lang['chargemanager_client_id_required'] = 'ID do cliente é obrigatório';
$lang['chargemanager_contract_required'] = 'Contrato é obrigatório';
$lang['chargemanager_contract_not_belongs_client'] = 'Contrato não pertence ao cliente selecionado';
$lang['chargemanager_contract_already_in_billing_group'] = 'Contrato já está em outro grupo de cobrança ativo';
$lang['chargemanager_charges_required'] = 'Pelo menos uma cobrança é obrigatória';
$lang['chargemanager_charge_amount_invalid'] = 'Cobrança %d: Valor é obrigatório e deve ser maior que 0';
$lang['chargemanager_charge_due_date_invalid'] = 'Cobrança %d: Data de vencimento é obrigatória';
$lang['chargemanager_charge_billing_type_invalid'] = 'Cobrança %d: Tipo de cobrança é obrigatório';
$lang['chargemanager_total_amount_mismatch'] = 'Total das cobranças deve ser igual ao valor do contrato';

// New validation messages for improved billing group creation
$lang['chargemanager_error_no_charges_provided'] = 'Nenhuma cobrança foi fornecida';
$lang['chargemanager_error_due_date_required'] = 'Cobrança %d: Data de vencimento é obrigatória';
$lang['chargemanager_error_due_date_past'] = 'Cobrança %d: Data de vencimento (%s) não pode ser anterior ao dia atual';
$lang['chargemanager_error_invalid_amount'] = 'Cobrança %d: Valor deve ser um número válido e maior que zero';
$lang['chargemanager_error_billing_type_required'] = 'Cobrança %d: Tipo de cobrança é obrigatório';
$lang['chargemanager_error_invalid_billing_type'] = 'Cobrança %d: Tipo de cobrança deve ser BOLETO, PIX ou CREDIT_CARD';
$lang['chargemanager_error_saving_charge_to_db_number'] = 'Erro ao salvar cobrança %d no banco de dados';
$lang['chargemanager_error_creating_charge_number'] = 'Erro ao criar cobrança %d no gateway: %s';
$lang['chargemanager_error_charge_exception'] = 'Erro inesperado na cobrança %d: %s';
$lang['chargemanager_error_no_charges_created'] = 'Nenhuma cobrança foi criada com sucesso. Grupo de cobrança foi removido.';
$lang['chargemanager_error_unexpected'] = 'Erro inesperado';
$lang['chargemanager_billing_group_created_successfully'] = 'Grupo de cobrança criado com sucesso';
$lang['chargemanager_billing_group_created_successfully_with_id'] = 'Grupo de cobrança #%d criado com sucesso';
$lang['chargemanager_charges_created_count'] = '%d cobrança(s) criada(s) com sucesso';
$lang['chargemanager_charges_failed_count'] = '%d cobrança(s) falharam';

// Invoice Generation Messages
$lang['chargemanager_invoice_created_with_id'] = 'Fatura #%d criada com sucesso';
$lang['chargemanager_invoice_generation_failed_but_charges_created'] = 'Cobranças criadas, mas houve problema na geração da fatura (será tentado novamente)';
$lang['chargemanager_billing_group_incomplete_data'] = 'Dados incompletos do grupo de cobrança (cliente ou contrato ausente)';
$lang['chargemanager_invoice_already_exists'] = 'Fatura já existe para este grupo de cobrança';
$lang['chargemanager_error_creating_invoice'] = 'Erro ao criar fatura';
$lang['chargemanager_invoice_created_successfully'] = 'Fatura criada com sucesso';
$lang['chargemanager_charge_description'] = 'Cobrança %s - Vencimento: %s';
$lang['chargemanager_view_billing_group'] = 'Visualizar Grupo de Cobrança';

// Contract Loading Messages
$lang['chargemanager_contracts_loaded_successfully'] = 'Contratos carregados com sucesso';
$lang['chargemanager_no_available_contracts'] = 'Nenhum contrato disponível para este cliente';
$lang['chargemanager_error_loading_contracts'] = 'Erro ao carregar contratos';
$lang['chargemanager_contract_id_required'] = 'ID do contrato é obrigatório';
$lang['chargemanager_contract_not_found'] = 'Contrato não encontrado';
$lang['chargemanager_contract_invalid'] = 'Contrato inválido (não assinado, expirado ou sem valor)';
$lang['chargemanager_error_loading_contract'] = 'Erro ao carregar detalhes do contrato'; 