# Sistema de Status Granulares - ChargeManager

## 📋 Visão Geral

O ChargeManager agora possui um sistema de status granulares que oferece maior precisão e controle sobre o estado dos billing groups, resolvendo problemas críticos identificados na lógica anterior.

## 🎯 Problemas Resolvidos

### 1. **Deleção de Cobrança Pendente**
**Problema Anterior:** Deletar uma cobrança pendente resultava em status "completed" incorreto.
**Solução:** Validação de completude antes de permitir deleção + status granulares.

### 2. **Cobrança Cancelada Permanente**
**Problema Anterior:** Billing groups com cobranças canceladas nunca chegavam a "completed".
**Solução:** Cobranças canceladas são excluídas do cálculo de status.

### 3. **Validação de Valor Total**
**Problema Anterior:** Status não considerava diferenças entre valor das cobranças e valor do contrato.
**Solução:** Status específicos para valores acima, abaixo ou exatos do contrato.

## 🏷️ Novos Status Disponíveis

### **Status de Completude**
- **`completed_exact`** - Todas cobranças pagas, valor exato do contrato ✅
- **`completed_over`** - Todas cobranças pagas, valor acima do contrato 📈
- **`completed_under`** - Todas cobranças pagas, valor abaixo do contrato 📉

### **Status Parciais**
- **`partial_on_track`** - Algumas cobranças pagas, valor total correto ⏳
- **`partial_over`** - Algumas cobranças pagas, valor acima do contrato 📊
- **`partial_under`** - Algumas cobranças pagas, faltam cobranças ⚠️

### **Status de Problemas**
- **`overdue_on_track`** - Cobranças vencidas, valor total correto ⚡
- **`overdue_over`** - Cobranças vencidas, valor acima do contrato 🔴
- **`overdue_under`** - Cobranças vencidas, faltam cobranças 💥

### **Status Básicos**
- **`open`** - Aguardando pagamentos 📂
- **`incomplete`** - Faltam cobranças para atingir valor do contrato 📋
- **`cancelled`** - Todas cobranças canceladas ❌

## 🔧 Funcionalidades Implementadas

### **1. Cálculo Inteligente de Status**
```php
// Exclui cobranças canceladas do cálculo
$active_charges = array_filter($charges, function($charge) {
    return $charge->status !== 'cancelled';
});

// Compara valor com tolerância de R$ 0,01
$tolerance = 0.01;
$value_comparison = 'exact';
if ($active_value > $contract_value + $tolerance) {
    $value_comparison = 'over';
} elseif ($active_value < $contract_value - $tolerance) {
    $value_comparison = 'under';
}
```

### **2. Controle de Edição Granular**
```php
// Apenas 'completed_exact' e 'cancelled' não podem ser editados
public function can_edit_billing_group($status)
{
    $status_config = $this->get_status_config($status);
    return $status_config['editable'];
}
```

### **3. Validação de Deleção de Cobrança**
```php
// Impede deleção que quebraria a completude
public function can_delete_charge($charge_id)
{
    // Verifica se deleção manteria integridade do billing group
    return ['can_delete' => true/false, 'reason' => '...'];
}
```

### **4. Interface Visual Aprimorada**
- Ícones específicos para cada status
- Cores diferenciadas com gradientes
- Tooltips informativos
- Animações para status críticos
- Descrições contextuais

## 📊 Lógica de Negócio

### **Cenários de Status**

#### **Cenário 1: Valor Exato**
```
Contrato: R$ 1.000,00
Cobranças Ativas: R$ 1.000,00
- Todas pagas → completed_exact
- Algumas pagas → partial_on_track  
- Com vencidas → overdue_on_track
```

#### **Cenário 2: Valor Acima (Renegociação)**
```
Contrato: R$ 1.000,00
Cobranças Ativas: R$ 1.200,00
- Todas pagas → completed_over
- Algumas pagas → partial_over
- Com vencidas → overdue_over
```

#### **Cenário 3: Valor Abaixo (Incompleto)**
```
Contrato: R$ 1.000,00
Cobranças Ativas: R$ 800,00
- Todas pagas → completed_under
- Algumas pagas → partial_under
- Com vencidas → overdue_under
```

## 🎨 Styling e UX

### **Classes CSS Customizadas**
```css
.label-completed-exact { 
    background: #28a745; 
    animation: pulse-success 2s infinite; 
}
.label-completed-over { 
    background: #17a2b8; 
    border-left: 3px solid #ffc107; 
}
.label-overdue-under { 
    background: #dc3545; 
    animation: pulse-danger 2s infinite; 
}
```

### **Indicadores Visuais**
- 🎯 Verde sólido: Completude exata
- 🔵 Azul com borda amarela: Valor acima
- ⚠️ Amarelo: Valor abaixo
- 🔴 Vermelho pulsante: Problemas críticos
- 🟠 Laranja tracejado: Incompleto

## 🔄 Compatibilidade

### **Status Legados Mantidos**
- `completed` → `completed_exact` (compatibilidade)
- `partial` → `partial_on_track` (compatibilidade)
- `overdue` → `overdue_on_track` (compatibilidade)

### **Migração Automática**
O sistema reconhece status antigos e os mapeia automaticamente para a nova estrutura, mantendo funcionalidade existente.

## 🚀 Benefícios

### **Para Gestores**
- **Visibilidade Total:** Status específicos mostram exatamente a situação
- **Controle de Renegociação:** Identifica quando valores foram alterados
- **Prevenção de Erros:** Validações impedem ações que quebrariam integridade

### **Para Usuários**
- **Interface Intuitiva:** Cores e ícones facilitam compreensão
- **Feedback Claro:** Descrições explicam cada status
- **Ações Seguras:** Sistema previne operações problemáticas

### **Para o Sistema**
- **Integridade de Dados:** Validações garantem consistência
- **Performance:** Cálculos otimizados com cache
- **Escalabilidade:** Estrutura preparada para novos status

## 📝 Exemplos Práticos

### **Exemplo 1: Renegociação para Mais**
```
Situação: Cliente negocia valor adicional
Contrato Original: R$ 5.000,00
Nova Configuração: R$ 6.000,00
Status Resultante: completed_over ou partial_over
Ação: Editável para ajustes
```

### **Exemplo 2: Desconto Aplicado**
```
Situação: Desconto concedido ao cliente
Contrato Original: R$ 3.000,00
Valor Final: R$ 2.500,00
Status Resultante: completed_under
Ação: Editável para documentar desconto
```

### **Exemplo 3: Cobrança Cancelada**
```
Situação: Uma cobrança é cancelada
Cobranças: 3 total (2 pagas + 1 cancelada)
Status Resultante: completed_under (cancelada ignorada)
Ação: Pode criar nova cobrança se necessário
```

## 🔧 Configuração

### **Tolerância de Valor**
```php
$tolerance = 0.01; // R$ 0,01 de diferença aceita
```

### **Status Não Editáveis**
```php
'editable' => false // apenas completed_exact e cancelled
```

### **Validação de Completude**
```php
// Executa automaticamente em:
- Criação de cobrança
- Atualização de cobrança  
- Deleção de cobrança
- Mudança de status manual
```

## 📈 Métricas e Monitoramento

O novo sistema permite tracking detalhado de:
- Taxa de completude exata vs. renegociada
- Frequência de valores acima/abaixo do contrato
- Tempo médio para resolução de status overdue
- Padrões de cancelamento de cobranças

---

**Implementado em:** Dezembro 2024  
**Versão:** ChargeManager v1.0.0+  
**Compatibilidade:** Perfex CRM v3.0+ 