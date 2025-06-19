# Resumo de Funcionalidades do Módulo ChargeManager

Este documento descreve as principais funcionalidades do módulo `ChargeManager` para o Perfex CRM.

## 1. Gateway de Pagamento (ASAAS)

- **Integração com ASAAS**: O módulo integra-se diretamente com o gateway de pagamento ASAAS para processar cobranças.
- **Configurações**: Permite que o administrador configure as credenciais da API (API Key), o ambiente de operação (Sandbox para testes ou Produção) e um token de segurança para webhooks.
- **Armazenamento Seguro**: As configurações são armazenadas de forma segura no banco de dados, na tabela `chargemanager_asaas_settings`.

## 2. Sincronização de Clientes

- **Sincronização Automática**: Clientes do Perfex CRM são automaticamente sincronizados com a plataforma ASAAS.
- **Ciclo de Vida do Cliente**:
    - **Criação**: Um novo cliente no Perfex cria um cliente correspondente no ASAAS.
    - **Atualização**: A modificação de dados de um cliente no Perfex atualiza o registro no ASAAS.
    - **Exclusão**: A remoção de um cliente no Perfex remove o cliente do ASAAS.
- **Mapeamento de Entidades**: A tabela `chargemanager_entity_mappings` é usada para manter um vínculo claro entre os IDs de clientes no Perfex e seus respectivos IDs no ASAAS, garantindo a integridade dos dados.
- **Logs de Sincronização**: A tabela `chargemanager_sync_logs` registra todas as operações de sincronização, permitindo auditoria e depuração de possíveis falhas.

## 3. Grupos de Faturamento (Billing Groups)

- **Aba no Perfil do Cliente**: O módulo adiciona uma nova aba chamada "Grupos de Faturamento" na página de perfil do cliente na área administrativa.
- **Agrupamento de Cobranças**: Um grupo de faturamento serve para agregar múltiplas cobranças que pertencem a um mesmo cliente e estão vinculadas a um contrato específico.
- **Gestão de Grupos**: A tabela `chargemanager_billing_groups` armazena as informações desses grupos, incluindo o valor total, o status (ex: `aberto`, `pago`) e as referências ao cliente e contrato.

## 4. Gestão de Cobranças (Charges)

- **Funcionalidade Central**: Esta é a principal funcionalidade do módulo, responsável por gerenciar cada cobrança individualmente.
- **Detalhes da Cobrança**: A tabela `chargemanager_charges` armazena um conjunto rico de dados para cada cobrança:
    - **Valores e Prazos**: Valor e data de vencimento.
    - **Meios de Pagamento**: Tipo de cobrança (Boleto, Cartão de Crédito, PIX).
    - **Status**: Status atual da cobrança (`pendente`, `paga`, `vencida`, etc.).
    - **Dados do Gateway**: ID da cobrança no gateway, link para a fatura ou boleto, código de barras e código PIX.
    - **Vínculos com Perfex**: Referências ao ID da fatura, do cliente e do registro de pagamento no Perfex.
- **Conciliação de Pagamentos**: Após um pagamento ser registrado no Perfex, o sistema automaticamente tenta associá-lo a uma cobrança pendente no módulo.

## 5. Webhooks

- **Notificações em Tempo Real**: O módulo está preparado para receber e processar notificações automáticas (webhooks) enviadas pelo ASAAS.
- **Fila de Processamento**: Para garantir que nenhuma notificação seja perdida, os webhooks são enfileirados na tabela `chargemanager_webhook_queue`. Este sistema processa cada evento de forma assíncrona, com múltiplas tentativas em caso de falha, garantindo robustez.
- **Atualização de Status**: Um webhook de pagamento confirmado, por exemplo, irá disparar uma rotina para atualizar o status da cobrança correspondente em `chargemanager_charges` e, consequentemente, o status da fatura no Perfex.

## 6. Permissões de Acesso

- **Controle Granular**: O módulo introduz um novo conjunto de permissões que podem ser gerenciadas na área de `Staff` -> `Roles`.
- **Níveis de Permissão**:
    - **Ver**: Permite visualizar os dados do ChargeManager.
    - **Criar**: Permite criar novas cobranças ou grupos.
    - **Editar**: Permite modificar dados existentes.
    - **Excluir**: Permite remover registros.
- **Segurança**: Isso garante que apenas membros da equipe com a devida autorização possam acessar e gerenciar as funcionalidades de cobrança. 