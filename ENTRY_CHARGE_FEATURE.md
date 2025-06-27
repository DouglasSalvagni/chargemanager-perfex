# Entry Charge Feature - ChargeManager

## Visão Geral

A funcionalidade de **Cobrança de Entrada** permite identificar e gerenciar qual cobrança em um billing group é considerada como valor de entrada para o negócio. Esta é uma necessidade comum na regra de negócio do cliente.

## Funcionalidades Implementadas

### 1. Identificação Automática
- **Primeira cobrança criada**: Automaticamente marcada como cobrança de entrada
- **Campo de banco**: `is_entry_charge` (TINYINT(1), padrão 0)
- **Compatibilidade**: Totalmente compatível com dados existentes

### 2. Interface Visual Diferenciada
- **Coloração especial**: Linha destacada com borda azul e fundo diferenciado
- **Badge identificador**: Label "Entrada" com ícone de estrela
- **Animação sutil**: Efeito de pulsação para destaque
- **CSS responsivo**: Funciona em todas as telas

### 3. Lógica de Reassignação Automática
- **Quando entrada é deletada**: Sistema automaticamente define a próxima cobrança (por vencimento) como entrada
- **Critério de seleção**: Cobrança com vencimento mais próximo
- **Fallback**: Se não houver outras cobranças, nenhuma é marcada como entrada

### 4. Gerenciamento Manual
- **Interface de edição**: Botão para definir qualquer cobrança como entrada
- **Validação**: Apenas uma cobrança pode ser entrada por billing group
- **Confirmação**: Modal de confirmação antes de alterar
- **Feedback**: Mensagens de sucesso/erro apropriadas

### 5. Interface de Criação Aprimorada
- **Identificação visual**: Primeira cobrança criada destacada como entrada
- **Numeração inteligente**: Renumeração automática quando cobranças são removidas
- **Proteção de entrada**: Cobrança de entrada não pode ser removida se existem outras
- **CSS responsivo**: Animações e destaque visual consistente

## Implementação Técnica

### Banco de Dados

```sql
-- Campo adicionado à tabela chargemanager_charges
ALTER TABLE `chargemanager_charges` 
ADD COLUMN `is_entry_charge` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
ADD KEY `is_entry_charge` (`is_entry_charge`);
```

### Métodos Principais

#### Chargemanager_charges_model.php

```php
// Definir cobrança como entrada
public function set_as_entry_charge($charge_id)

// Obter cobrança de entrada de um billing group
public function get_entry_charge($billing_group_id)

// Auto-assignar entrada quando atual é deletada
public function auto_assign_entry_charge($billing_group_id)

// Override do delete para reassignação automática
public function delete($id)
```

#### Billing_groups.php (Controller)

```php
// Endpoint AJAX para definir entrada
public function set_entry_charge()

// Endpoint AJAX para obter entrada atual
public function get_entry_charge()
```

### Frontend

#### CSS Classes
```css
.entry-charge-row {
    background-color: #f8f9fa !important;
    border-left: 4px solid #007bff !important;
}

.entry-charge-row .label-primary {
    animation: pulse-entry 2s infinite;
}
```

#### JavaScript Functions
```javascript
// Definir cobrança como entrada
function setAsEntryCharge(chargeId)

// Atualizar status de entrada na criação
function updateEntryChargeStatus()

// Renumerar cobranças sequencialmente
function renumberCharges()
```

## Fluxo de Funcionamento

### 1. Criação de Billing Group
1. Usuário cria billing group com múltiplas cobranças
2. Sistema automaticamente marca a primeira cobrança como entrada
3. Interface visual destaca a cobrança de entrada durante a criação
4. Numeração sequencial mantida mesmo com remoções
5. Log de atividade registra a ação

### 2. Visualização
1. **View de visualização**: Mostra cobrança de entrada com destaque visual
2. **View de edição**: Permite alterar qual cobrança é entrada
3. **Tabelas**: Coluna específica para identificar status de entrada

### 3. Edição Manual
1. Usuário clica em "Set Entry" na cobrança desejada
2. Sistema confirma a ação via modal
3. Remove flag de entrada de outras cobranças
4. Define nova cobrança como entrada
5. Atualiza interface visual

### 4. Deleção com Reassignação
1. Usuário deleta cobrança que é entrada
2. Sistema identifica que entrada foi removida
3. Busca próxima cobrança (por vencimento)
4. Define automaticamente como nova entrada
5. Log de atividade registra a reassignação

## Traduções

### Inglês (chargemanager_lang.php)
```php
$lang['chargemanager_entry_charge'] = 'Entry Charge';
$lang['chargemanager_entry'] = 'Entry';
$lang['chargemanager_set_entry'] = 'Set Entry';
$lang['chargemanager_set_as_entry'] = 'Set as Entry Charge';
$lang['chargemanager_confirm_set_entry_charge'] = 'Are you sure you want to set this charge as the entry charge?';
```

## Compatibilidade

### Dados Existentes
- **Campo padrão 0**: Cobranças existentes não são afetadas
- **Migração automática**: Campo adicionado via install.php
- **Sem quebras**: Funcionalidade totalmente opcional

### Versões do Perfex
- **Compatível**: Todas as versões suportadas pelo módulo
- **Padrões**: Segue convenções do Perfex CRM
- **Hooks**: Preparado para extensões futuras

## Benefícios

### Para o Usuário
1. **Clareza visual**: Identifica facilmente a cobrança de entrada
2. **Gestão simples**: Interface intuitiva para alterações
3. **Automação**: Não precisa gerenciar manualmente quando deleta
4. **Flexibilidade**: Pode alterar qual cobrança é entrada a qualquer momento

### Para o Negócio
1. **Regra de negócio**: Atende necessidade específica do cliente
2. **Controle financeiro**: Facilita identificação de valores de entrada
3. **Relatórios**: Base para futuras funcionalidades de relatório
4. **Auditoria**: Logs completos de todas as alterações

## Próximos Passos Sugeridos

### Melhorias Futuras
1. **Relatórios**: Relatório específico de cobranças de entrada
2. **Dashboard**: Widget mostrando estatísticas de entrada
3. **Notificações**: Alertas quando cobrança de entrada vence
4. **API**: Endpoints para integração externa
5. **Bulk actions**: Ações em massa para múltiplos billing groups

### Integrações
1. **Perfex Dashboard**: Widgets personalizados
2. **Reports module**: Relatórios avançados
3. **Email templates**: Templates específicos para entrada
4. **Webhooks**: Notificações externas de mudanças

## Conclusão

A funcionalidade de Cobrança de Entrada foi implementada seguindo as melhores práticas do Perfex CRM, mantendo compatibilidade total com dados existentes e fornecendo uma interface intuitiva para o usuário. A solução é robusta, escalável e está preparada para futuras extensões. 