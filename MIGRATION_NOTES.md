# ChargeManager - Migração para Invoices Individuais

## 📋 Resumo das Mudanças

Esta migração refatora o módulo ChargeManager para criar uma invoice individual para cada cobrança (charge), ao invés de uma única invoice por billing group.

## 🔄 Principais Alterações

### 1. **Estrutura do Banco de Dados**

#### **Removido:**
- Coluna `invoice_id` da tabela `tblchargemanager_billing_groups`

#### **Mantido:**
- Coluna `perfex_invoice_id` na tabela `tblchargemanager_charges` (já existia)
- Coluna `payment_record_id` na tabela `tblchargemanager_charges` (já existia)

### 2. **Fluxo de Criação de Invoices**

#### **Antes:**
```
Billing Group → 1 Invoice (com múltiplos itens)
```

#### **Depois:**
```
Billing Group → N Charges → N Invoices (1 para cada charge)
```

### 3. **Arquivos Modificados**

#### **install.php**
- Adicionada migração automática para remover coluna `invoice_id`
- Atualizada estrutura da tabela `billing_groups`

#### **models/Chargemanager_charges_model.php**
- **Adicionado:** `generate_individual_invoice()` - Cria invoice para uma cobrança específica
- **Adicionado:** `get_charge_with_relationships()` - Busca cobrança com dados relacionados

#### **models/Chargemanager_billing_groups_model.php**
- **Removido:** `generate_invoice()` - Método antigo que criava uma invoice para todo o grupo
- **Adicionado:** `generate_invoices_for_charges()` - Cria invoices individuais para todas as cobranças
- **Atualizado:** `get_with_relationships()` - Agora busca múltiplas invoices
- **Atualizado:** `calculate_billing_group_status()` - Melhorada lógica de cálculo de status
- **Atualizado:** `get_payment_summary()` - Usa modelo de charges para dados mais precisos

#### **controllers/Billing_groups.php**
- **Atualizado:** `create()` - Agora cria invoices individuais após criar as cobranças
- **Melhorado:** Tratamento de erros e mensagens de retorno

#### **controllers/Webhook.php**
- **Melhorado:** `create_payment_record()` - Usa `payments_model->add()` seguindo padrões do Perfex
- **Adicionado:** Verificação de transações duplicadas
- **Adicionado:** Logs mais detalhados

## 🎯 Benefícios da Nova Arquitetura

### **1. Granularidade de Controle**
- Cada cobrança tem sua própria invoice
- Pagamentos são registrados individualmente
- Status de pagamento mais preciso

### **2. Melhor Integração com Perfex CRM**
- Usa `payments_model->add()` que atualiza automaticamente status das invoices
- Registra atividades corretamente
- Segue padrões nativos do Perfex

### **3. Flexibilidade**
- Possibilidade de diferentes datas de vencimento por invoice
- Facilita relatórios por cobrança individual
- Melhor rastreamento de pagamentos parciais

### **4. Conformidade com Padrões**
- Segue as regras dos models `invoices_model` e `payments_model`
- Utiliza verificação de transações duplicadas
- Logs de atividade padronizados

## 🔧 Processo de Migração

### **Automático:**
1. Ao ativar o módulo, o script `install.php` verifica se a coluna `invoice_id` existe
2. Se existir, remove a coluna automaticamente
3. Registra log da migração

### **Manual (se necessário):**
```sql
-- Remover coluna invoice_id se existir
ALTER TABLE `tblchargemanager_billing_groups` DROP COLUMN `invoice_id`;
```

## 📊 Impacto nos Dados Existentes

### **Billing Groups Existentes:**
- Mantêm todos os dados (exceto `invoice_id`)
- Status continua funcionando normalmente
- Cobranças existentes não são afetadas

### **Cobranças Existentes:**
- Mantêm todas as informações
- Campo `perfex_invoice_id` continua funcional
- Pagamentos registrados não são afetados

### **Invoices Existentes:**
- Invoices criadas pelo sistema antigo continuam válidas
- Novos billing groups usarão a nova lógica automaticamente

## 🧪 Testes Recomendados

### **1. Criação de Billing Group:**
- Verificar se cada charge gera sua própria invoice
- Confirmar que todas as invoices são criadas corretamente
- Validar dados das invoices (cliente, valores, datas)

### **2. Processamento de Webhooks:**
- Testar recebimento de pagamento via ASAAS
- Verificar criação de registro de pagamento
- Confirmar atualização de status da invoice e charge

### **3. Status do Billing Group:**
- Verificar cálculo correto baseado em múltiplas invoices
- Testar cenários: todas pagas, parcialmente pagas, vencidas

### **4. Relatórios:**
- Confirmar que `get_payment_summary()` retorna dados corretos
- Verificar integridade dos dados financeiros

## 🚨 Pontos de Atenção

### **1. Views/Templates:**
- Verificar se views que referenciam `billing_group->invoice_id` foram atualizadas
- Atualizar para usar `billing_group->invoices` (array)

### **2. Permissões:**
- Usuários precisam de permissão para criar invoices
- Verificar permissões de payments

### **3. Configurações:**
- Métodos de pagamento devem estar configurados
- Templates de email de invoice devem estar ativos

## 📝 Logs Importantes

O sistema registra automaticamente:
- `ChargeManager: Migrated billing_groups table - removed invoice_id column`
- `ChargeManager: Individual invoice #X created for charge #Y`
- `ChargeManager: Payment record #X created for charge #Y (Invoice #Z)`

## 🔮 Funcionalidades Futuras

Esta nova arquitetura permite:
- Pagamentos parciais por cobrança
- Diferentes condições de pagamento por charge
- Relatórios mais detalhados
- Integração com outros gateways de pagamento
- Cobrança de juros/multas individuais

---

**Data da Migração:** Implementada em versão 1.1.0
**Compatibilidade:** Totalmente retrocompatível com dados existentes 