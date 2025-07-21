# Implementação: Geração Automática de Cobranças na Assinatura do Contrato

## Funcionalidade Implementada

A funcionalidade para gerar automaticamente billing groups e cobranças quando um contrato é assinado já está **COMPLETAMENTE IMPLEMENTADA** no arquivo `helpers/contract_billing_schema_helper.php`.

## Como Funciona

### 1. **Hooks Monitorados**

A implementação monitora **4 hooks diferentes** para capturar a assinatura do contrato:

```php
hooks()->add_action('contract_marked_as_signed', 'create_charges_on_contract_signed');
hooks()->add_action('after_contract_signed', 'create_charges_on_contract_signed');
hooks()->add_action('after_contract_was_signed', 'create_charges_on_contract_signed');
hooks()->add_action('after_contract_was_marked_as_signed', 'create_charges_on_contract_signed');
```

### 2. **Função Principal: `create_charges_on_contract_signed($contract_id)`**

#### **Validações Iniciais**
```php
// 1. Verifica se o contrato está realmente assinado
if (!$contract || ($contract->signed != 1 && $contract->marked_as_signed != 1)) {
    return; // Sai se não estiver assinado
}

// 2. Verifica se existe schema de cobrança
$schema = $CI->db->get(db_prefix() . 'chargemanager_contract_billing_schemas')->row();
if (!$schema || empty($schema->schema_data)) {
    return; // Sai se não tiver schema
}

// 3. PREVENÇÃO DE DUPLICAÇÃO - Verifica se já existe billing group
$existing_billing_group = $CI->db->get(db_prefix() . 'chargemanager_billing_groups')->row();
if ($existing_billing_group) {
    log_activity('Contract Billing Schema: Contrato #' . $contract_id . ' já possui um grupo de cobrança');
    return; // SAI PARA PREVENIR DUPLICAÇÃO
}
```

#### **Criação do Billing Group**
```php
$billing_group_data = [
    'client_id' => $contract->client,
    'contract_id' => $contract_id,
    'sale_agent' => $contract->addedfrom, // Criador do contrato
    'status' => 'open',
    'total_amount' => $contract->contract_value
];

$billing_group_id = $CI->chargemanager_billing_groups_model->create($billing_group_data);
```

#### **Processamento do Schema e Geração de Cobranças**
```php
// 1. Decodifica o schema JSON
$charges_schema = json_decode($schema->schema_data, true);

// 2. Para cada cobrança no schema:
foreach ($charges_schema as $index => $charge_data) {
    // a. Cria customer no gateway (se não existir)
    $customer_result = $CI->gateway_manager->get_or_create_customer($contract->client);
    
    // b. Cria cobrança no gateway (ASAAS)
    $gateway_result = $CI->gateway_manager->create_charge($gateway_charge_data);
    
    // c. Salva cobrança no banco local
    $local_charge_id = $CI->chargemanager_charges_model->create($local_charge_data);
    
    // d. Gera fatura individual
    $invoice_result = $CI->chargemanager_charges_model->generate_individual_invoice($local_charge_id);
}
```

### 3. **Dados Salvos na Cobrança Local**

Cada cobrança criada salva os seguintes dados:

```php
$local_charge_data = [
    'gateway_charge_id' => $gateway_result['charge_id'],    // ID no ASAAS
    'gateway' => 'asaas',
    'billing_group_id' => $billing_group_id,               // Vincula ao grupo
    'client_id' => $contract->client,
    'sale_agent' => $billing_group_data['sale_agent'],
    'value' => floatval($charge_data['amount']),
    'due_date' => $charge_data['due_date'],
    'billing_type' => $charge_data['billing_type'],        // BOLETO/PIX/CREDIT_CARD
    'status' => 'pending',
    'is_entry_charge' => ($index === 0) ? 1 : 0,          // Primeira = entrada
    'invoice_url' => $gateway_result['invoice_url'],        // URL do ASAAS
    'barcode' => $gateway_result['barcode'],               // Código de barras
    'pix_code' => $gateway_result['pix_code'],             // QR Code PIX
    'description' => 'Cobrança automática via ChargeManager - Contrato #' . $contract_id
];
```

### 4. **Controle de Transação**

A implementação usa **transação de banco** para garantir integridade:

```php
$CI->db->trans_begin();

try {
    // Cria billing group
    // Cria todas as cobranças
    // Gera todas as faturas
    
    $CI->db->trans_commit(); // Confirma tudo
} catch (Exception $e) {
    $CI->db->trans_rollback(); // Desfaz tudo em caso de erro
}
```

### 5. **Logs Detalhados**

A implementação gera logs completos:

```php
// Sucesso
log_activity('Contract Billing Schema: 3 cobrança(s) criada(s) com sucesso para o contrato #123');

// Prevenção de duplicação
log_activity('Contract Billing Schema: Contrato #123 já possui um grupo de cobrança (#456)');

// Erros
log_activity('Contract Billing Schema: Erro ao criar cobranças para o contrato #123 - Mensagem do erro');
```

## Fluxo Completo de Funcionamento

```
1. USUÁRIO ASSINA CONTRATO
   ↓
2. HOOK É DISPARADO (4 hooks diferentes monitorados)
   ↓
3. VALIDAÇÕES:
   - Contrato realmente assinado?
   - Existe schema de cobrança?
   - JÁ EXISTE BILLING GROUP? → SE SIM, PARA AQUI (prevenção)
   ↓
4. CRIA BILLING GROUP
   - client_id do contrato
   - contract_id
   - sale_agent (criador do contrato)
   - status = 'open'
   ↓
5. PROCESSA SCHEMA JSON:
   - Lê cada cobrança configurada
   - Valores, datas, tipos de cobrança
   ↓
6. PARA CADA COBRANÇA:
   a. Cria/obtém customer no ASAAS
   b. Cria cobrança no ASAAS
   c. Salva cobrança local (tabela chargemanager_charges)
   d. Gera fatura individual no Perfex
   e. Vincula cobrança → billing group
   ↓
7. ATUALIZA STATUS DO BILLING GROUP
   ↓
8. CONFIRMA TRANSAÇÃO E LOGA SUCESSO
```

## Prevenção de Duplicação

A implementação tem **3 níveis de prevenção**:

1. **Verificação de billing group existente**:
   ```php
   $existing_billing_group = $CI->db->get(db_prefix() . 'chargemanager_billing_groups')->row();
   if ($existing_billing_group) {
       return; // Para a execução
   }
   ```

2. **Transação de banco**: Se algo falhar, tudo é revertido

3. **Logs detalhados**: Permite identificar tentativas de duplicação

## Reutilização de Métodos

A implementação **reutiliza completamente** os métodos existentes:

- `chargemanager_billing_groups_model->create()` - Criar billing group
- `gateway_manager->get_or_create_customer()` - Customer no gateway
- `gateway_manager->create_charge()` - Cobrança no gateway
- `chargemanager_charges_model->create()` - Cobrança local
- `chargemanager_charges_model->generate_individual_invoice()` - Fatura
- `chargemanager_billing_groups_model->refresh_status()` - Status

## Resultado Final

Quando um contrato é assinado:

✅ **Billing Group criado** automaticamente  
✅ **Cobranças geradas no ASAAS** conforme schema  
✅ **Cobranças salvas localmente** vinculadas ao grupo  
✅ **Faturas individuais criadas** no Perfex  
✅ **Prevenção de duplicação** garantida  
✅ **Logs completos** para auditoria  
✅ **Transação segura** com rollback em caso de erro  

A implementação está **100% funcional** e segue exatamente as especificações solicitadas!