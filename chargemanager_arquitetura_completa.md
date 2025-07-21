# ChargeManager - Arquitetura Completa do Módulo

## Visão Geral

O ChargeManager é um módulo completo para gestão de cobranças no Perfex CRM que integra com gateways de pagamento (principalmente ASAAS) para automatizar a criação e gestão de cobranças baseadas em contratos.

## Arquitetura do Sistema

### 1. **Estrutura de Dados (Database)**

#### Tabelas Principais

**`chargemanager_billing_groups`**
- **Propósito**: Agrupa cobranças relacionadas a um contrato
- **Campos principais**:
  - `client_id`: Cliente do grupo
  - `contract_id`: Contrato vinculado
  - `sale_agent`: Vendedor responsável
  - `status`: Status do grupo (open, completed, partial, overdue, etc.)
  - `total_amount`: Valor total do grupo

**`chargemanager_charges`**
- **Propósito**: Armazena cobranças individuais
- **Campos principais**:
  - `gateway_charge_id`: ID da cobrança no gateway
  - `billing_group_id`: Grupo ao qual pertence
  - `perfex_invoice_id`: Fatura Perfex vinculada
  - `client_id`: Cliente da cobrança
  - `value`: Valor da cobrança
  - `due_date`: Data de vencimento
  - `billing_type`: Tipo (BOLETO, PIX, CREDIT_CARD)
  - `status`: Status (pending, paid, overdue, cancelled)
  - `is_entry_charge`: Se é cobrança de entrada

**`chargemanager_entity_mappings`**
- **Propósito**: Mapeia entidades Perfex com entidades do gateway
- **Uso**: Vincula clientes Perfex com customers ASAAS

**`chargemanager_contract_billing_schemas`**
- **Propósito**: Armazena configurações de cobrança por contrato
- **Campos**: `schema_type`, `frequency`, `schema_data` (JSON)

#### Tabelas de Suporte

- `chargemanager_webhook_queue`: Fila de webhooks
- `chargemanager_sync_logs`: Logs de sincronização
- `chargemanager_asaas_settings`: Configurações do ASAAS

### 2. **Camada de Modelos (Models)**

#### `Chargemanager_billing_groups_model`
**Responsabilidades**:
- CRUD de billing groups
- Validação de contratos
- Cálculo de status baseado em cobranças
- Geração de faturas para cobranças
- Validação de completude

**Métodos principais**:
```php
create($data)                           // Criar grupo
get_with_charges($billing_group_id)     // Buscar com cobranças
validate_contract_status($contract_id)  // Validar contrato
calculate_billing_group_status($id)     // Calcular status
refresh_status($billing_group_id)       // Atualizar status
```

#### `Chargemanager_charges_model`
**Responsabilidades**:
- CRUD de cobranças
- Integração com gateway via Gateway_manager
- Geração de faturas individuais
- Atualização de status via webhook

**Métodos principais**:
```php
create($data)                          // Criar cobrança
create_batch_charges($charges_data)    // Criar múltiplas
update_payment_status($gateway_id, $status) // Atualizar via webhook
generate_individual_invoice($charge_id) // Gerar fatura
cancel_charge($charge_id, $reason)     // Cancelar cobrança
```

#### `Chargemanager_model`
**Responsabilidades**:
- Configurações ASAAS
- Entity mappings
- Webhook queue
- Sync logs
- Utilitários gerais

### 3. **Camada de Bibliotecas (Libraries)**

#### `Gateway_manager`
**Propósito**: Interface unificada para gateways de pagamento

**Métodos principais**:
```php
get_or_create_customer($client_id)     // Obter/criar customer
create_charge($charge_data)            // Criar cobrança
cancel_charge($charge_id)              // Cancelar cobrança
test_connection($api_key, $env)        // Testar conexão
```

**Fluxo de funcionamento**:
1. Recebe dados do Perfex
2. Verifica/cria mapping de cliente
3. Chama gateway específico
4. Retorna resultado padronizado

#### `Gateway_factory`
**Propósito**: Factory pattern para criar instâncias de gateways

**Gateways suportados**:
- **ASAAS**: Implementado completamente
- **Mercado Pago**: Planejado
- **PagSeguro**: Planejado

#### `Asaas_gateway` (implementa `Gateway_interface`)
**Responsabilidades**:
- Comunicação direta com API ASAAS
- Criação/atualização de customers
- Criação/cancelamento de cobranças
- Processamento de webhooks

**Métodos da interface**:
```php
test_connection()                      // Testar API
create_customer($customer_data)        // Criar customer
create_charge($charge_data)            // Criar cobrança
cancel_charge($charge_id)              // Cancelar
process_webhook($payload)              // Processar webhook
```

### 4. **Camada de Controle (Controllers)**

#### `Billing_groups`
- Gerenciamento de grupos de cobrança
- AJAX endpoints para contratos
- Validações de negócio

#### `Charges`
- Edição de cobranças individuais
- Visualização de detalhes
- Quick edit via AJAX

#### `Settings`
- Configurações do módulo
- Teste de conexão com gateway
- Gerenciamento de webhooks

#### `Webhook`
- Recebimento de webhooks
- Processamento assíncrono
- Atualização de status

### 5. **Sistema de Hooks para Contratos**

#### Hooks Implementados

**`contract_extra_fields`**
- Adiciona interface de configuração de cobranças
- Campos para modo manual/automático
- Configuração de frequência e valores

**`before_contract_added/updated`**
- Processa dados do schema de cobrança
- Remove campos do array principal
- Armazena em variáveis globais

**`after_contract_added`**
- Salva schema na tabela específica
- Vincula ao ID do contrato criado

**`contract_marked_as_signed/after_contract_signed`**
- Cria billing group automaticamente
- Gera cobranças no gateway
- Cria faturas individuais

## Fluxo de Funcionamento

### 1. **Configuração de Cobrança em Contrato**

```
1. Usuário edita contrato
2. Hook 'contract_extra_fields' adiciona interface
3. Usuário configura:
   - Tipo: Manual ou Automático
   - Se automático: frequência, valor, data 1ª parcela
   - Se manual: adiciona cobranças individuais
4. Validação: total = valor do contrato
5. Dados salvos como JSON no schema
```

### 2. **Assinatura do Contrato → Geração Automática**

```
1. Contrato é assinado
2. Hook 'contract_marked_as_signed' é disparado
3. Verifica se existe schema de cobrança
4. Cria billing_group:
   - client_id do contrato
   - contract_id
   - sale_agent (do contrato)
   - status = 'open'
5. Para cada cobrança no schema:
   a. Gateway_manager.get_or_create_customer()
   b. Gateway_manager.create_charge()
   c. Salva charge no banco local
   d. Gera fatura individual
6. Atualiza status do billing_group
```

### 3. **Criação de Customer no Gateway**

```
1. Gateway_manager.get_or_create_customer(client_id)
2. Verifica entity_mapping existente
3. Se não existe:
   a. Busca dados do cliente no Perfex
   b. Chama Asaas_gateway.create_customer()
   c. Cria entity_mapping
   d. Log de sucesso
4. Retorna customer_id do gateway
```

### 4. **Criação de Cobrança no Gateway**

```
1. Gateway_manager.create_charge(charge_data)
2. Garante que customer existe
3. Chama Asaas_gateway.create_charge():
   a. Monta payload para ASAAS API
   b. POST /payments
   c. Retorna charge_id, invoice_url, barcode, pix_code
4. Salva no banco local:
   - gateway_charge_id
   - status = 'pending'
   - URLs de pagamento
5. Gera fatura individual no Perfex
```

### 5. **Processamento de Webhooks**

```
1. ASAAS envia webhook para /webhook
2. Webhook controller valida payload
3. Adiciona à webhook_queue
4. Processamento assíncrono:
   a. Identifica cobrança por gateway_charge_id
   b. Atualiza status da charge
   c. Se pago: atualiza paid_at, paid_amount
   d. Atualiza status do billing_group
   e. Marca webhook como processado
```

### 6. **Geração de Faturas Individuais**

```
1. Para cada charge criada
2. Chargemanager_charges_model.generate_individual_invoice()
3. Cria fatura Perfex:
   - clientid = charge.client_id
   - duedate = charge.due_date
   - total = charge.value
   - sale_agent = billing_group.sale_agent
4. Vincula: charge.perfex_invoice_id = invoice.id
5. Configura payment_modes apropriados
```

## Integração com ASAAS

### Endpoints Utilizados

**Customers**:
- `POST /customers` - Criar customer
- `POST /customers/{id}` - Atualizar customer
- `DELETE /customers/{id}` - Deletar customer

**Payments (Cobranças)**:
- `POST /payments` - Criar cobrança
- `GET /payments/{id}` - Buscar cobrança
- `DELETE /payments/{id}` - Cancelar cobrança

**Webhooks**:
- Eventos: `PAYMENT_CREATED`, `PAYMENT_UPDATED`, `PAYMENT_CONFIRMED`, etc.

### Tipos de Cobrança Suportados

1. **BOLETO**: Boleto bancário com código de barras
2. **PIX**: Pagamento instantâneo com QR Code
3. **CREDIT_CARD**: Cartão de crédito (parcelado ou à vista)

## Status e Estados

### Status de Billing Groups

**Estados Básicos**:
- `open`: Aguardando pagamentos
- `incomplete`: Faltam cobranças
- `cancelled`: Todas cobranças canceladas

**Estados com Pagamentos**:
- `partial_on_track`: Algumas pagas, valor correto
- `partial_over`: Algumas pagas, valor acima
- `partial_under`: Algumas pagas, faltam cobranças

**Estados Vencidos**:
- `overdue_on_track`: Vencidas, valor correto
- `overdue_over`: Vencidas, valor acima
- `overdue_under`: Vencidas, incompleto

**Estados Concluídos**:
- `completed_exact`: Todas pagas, valor exato
- `completed_over`: Todas pagas, valor acima
- `completed_under`: Todas pagas, valor abaixo

### Status de Charges

- `pending`: Aguardando pagamento
- `paid`: Pago
- `overdue`: Vencido
- `cancelled`: Cancelado
- `partial`: Parcialmente pago

## Validações e Regras de Negócio

### Validações de Contrato
1. Deve estar assinado
2. Não pode estar expirado
3. Deve ter valor > 0
4. Não pode já ter billing group

### Validações de Cobrança
1. Total das cobranças = valor do contrato (tolerância 0.01)
2. Datas de vencimento não podem ser no passado
3. Valores devem ser > 0
4. Tipos de cobrança devem ser válidos

### Regras de Edição
1. Cobranças pagas não podem ser editadas
2. Cobranças canceladas não podem ser editadas
3. Deletar cobrança não pode quebrar completude do grupo

## Logs e Monitoramento

### Tipos de Log

**Activity Logs**:
- Criação/atualização de billing groups
- Criação/cancelamento de cobranças
- Processamento de webhooks
- Erros de sincronização

**Sync Logs**:
- Sucesso/erro na criação de customers
- Sucesso/erro na criação de cobranças
- Detalhes de sincronização com gateway

**Webhook Queue**:
- Status de processamento
- Tentativas e erros
- Payload completo para debug

## Extensibilidade

### Adicionando Novos Gateways

1. Implementar `Gateway_interface`
2. Adicionar classe em `payment_gateways/`
3. Registrar em `Gateway_factory`
4. Configurar endpoints específicos

### Hooks Disponíveis

```php
// Antes de editar cobrança
hooks()->do_action('before_chargemanager_charge_edit', $data);

// Após editar cobrança  
hooks()->do_action('after_chargemanager_charge_edit', $data);

// Filtrar dados de edição
hooks()->apply_filters('chargemanager_charge_edit_data', $data, $charge);
```

## Considerações de Performance

### Otimizações Implementadas

1. **Entity Mappings**: Evita recriação de customers
2. **Batch Processing**: Criação múltipla de cobranças
3. **Webhook Queue**: Processamento assíncrono
4. **Status Caching**: Cálculo otimizado de status
5. **Índices de Banco**: Consultas otimizadas

### Monitoramento Recomendado

1. **Webhook Queue**: Monitorar itens pendentes
2. **Sync Logs**: Verificar erros de sincronização  
3. **Status Inconsistencies**: Billing groups com status incorreto
4. **Failed Charges**: Cobranças que falharam na criação

## Conclusão

O ChargeManager é um sistema robusto e extensível que automatiza completamente o processo de cobrança baseado em contratos no Perfex CRM. Sua arquitetura modular permite fácil manutenção e extensão para novos gateways, enquanto as validações e logs garantem integridade e rastreabilidade das operações.

A integração com hooks do Perfex permite personalização sem modificar o core, e o sistema de webhooks garante sincronização em tempo real com os gateways de pagamento.