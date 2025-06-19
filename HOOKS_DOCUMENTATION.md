# ChargeManager - Hooks Documentation

## 📋 Hooks Disponíveis

O módulo ChargeManager fornece vários hooks para permitir customizações e integrações com outros módulos.

## 🔧 Hooks de Atualização de Charges

**Método Principal:** `$this->chargemanager_charges_model->update($charge_id, $data, $options)`

### 1. **before_chargemanager_charge_edit**

Executado **antes** da atualização de um charge.

```php
// Hook data structure
$hook_data = [
    'charge_id' => int,           // ID do charge sendo editado
    'current_data' => object,     // Dados atuais do charge
    'update_data' => array,       // Dados que serão atualizados
    'options' => array            // Opções passadas para o método
];

// Exemplo de uso
hooks()->add_action('before_chargemanager_charge_edit', function($data) {
    $charge_id = $data['charge_id'];
    $current_charge = $data['current_data'];
    $update_data = $data['update_data'];
    
    // Exemplo: Log da edição
    log_activity('Iniciando edição do charge #' . $charge_id);
    
    // Exemplo: Validação customizada
    if (isset($update_data['value']) && $update_data['value'] > 10000) {
        // Enviar notificação para supervisor
        send_supervisor_notification($charge_id, $update_data['value']);
    }
});
```

### 2. **chargemanager_charge_edit_data**

Filter que permite **modificar** os dados antes da atualização.

```php
// Exemplo: Aplicar desconto automático
hooks()->add_filter('chargemanager_charge_edit_data', function($update_data, $current_charge) {
    // Se o valor for alterado para mais de R$ 5000, aplicar desconto de 5%
    if (isset($update_data['value']) && $update_data['value'] > 5000) {
        $update_data['value'] = $update_data['value'] * 0.95;
        $update_data['description'] = ($update_data['description'] ?? '') . ' (Desconto 5% aplicado)';
    }
    
    return $update_data;
}, 10, 2);
```

### 3. **after_chargemanager_charge_edit**

Executado **após** a atualização bem-sucedida de um charge.

```php
// Hook data structure
$hook_data = [
    'charge_id' => int,              // ID do charge editado
    'previous_data' => object,       // Dados anteriores do charge
    'current_data' => object,        // Dados atuais do charge
    'updated_fields' => array,       // Campos que foram atualizados
    'invoice_updated' => bool,       // Se a invoice foi atualizada
    'invoice_update_result' => array // Resultado da atualização da invoice
];

// Exemplo de uso
hooks()->add_action('after_chargemanager_charge_edit', function($data) {
    $charge_id = $data['charge_id'];
    $previous = $data['previous_data'];
    $current = $data['current_data'];
    $updated_fields = $data['updated_fields'];
    
    // Exemplo: Notificar cliente sobre mudanças importantes
    if (in_array('due_date', $updated_fields) || in_array('value', $updated_fields)) {
        notify_client_charge_updated($charge_id, $updated_fields);
    }
    
    // Exemplo: Sincronizar com sistema externo
    if (in_array('value', $updated_fields)) {
        sync_charge_with_external_system($charge_id, $current);
    }
    
    // Exemplo: Log detalhado das mudanças
    foreach ($updated_fields as $field) {
        $old_value = $previous->$field ?? 'N/A';
        $new_value = $current->$field ?? 'N/A';
        log_activity("Charge #{$charge_id}: {$field} alterado de '{$old_value}' para '{$new_value}'");
    }
});
```

## 🎯 Exemplos Práticos de Uso

### 1. **Integração com Sistema de Notificações**

```php
// arquivo: modules/my_notifications/my_notifications.php

// Hook para notificar mudanças críticas
hooks()->add_action('after_chargemanager_charge_edit', 'notify_charge_changes');

function notify_charge_changes($data) {
    $critical_fields = ['value', 'due_date', 'status'];
    $has_critical_changes = array_intersect($critical_fields, $data['updated_fields']);
    
    if (!empty($has_critical_changes)) {
        // Enviar email para o cliente
        send_charge_update_email($data['charge_id'], $data['current_data'], $has_critical_changes);
        
        // Enviar notificação push
        send_push_notification($data['current_data']->client_id, 'Cobrança atualizada');
    }
}
```

### 2. **Auditoria Avançada**

```php
// arquivo: modules/audit_trail/audit_trail.php

hooks()->add_action('before_chargemanager_charge_edit', 'audit_charge_edit_start');
hooks()->add_action('after_chargemanager_charge_edit', 'audit_charge_edit_complete');

function audit_charge_edit_start($data) {
    // Salvar snapshot antes da edição
    $CI = &get_instance();
    $CI->load->model('audit_model');
    
    $CI->audit_model->create_snapshot([
        'entity_type' => 'chargemanager_charge',
        'entity_id' => $data['charge_id'],
        'action' => 'edit_start',
        'data_before' => json_encode($data['current_data']),
        'user_id' => get_staff_user_id(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function audit_charge_edit_complete($data) {
    $CI = &get_instance();
    $CI->load->model('audit_model');
    
    $CI->audit_model->create_snapshot([
        'entity_type' => 'chargemanager_charge',
        'entity_id' => $data['charge_id'],
        'action' => 'edit_complete',
        'data_after' => json_encode($data['current_data']),
        'updated_fields' => json_encode($data['updated_fields']),
        'invoice_updated' => $data['invoice_updated'],
        'user_id' => get_staff_user_id(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
```

### 3. **Validação de Negócio Customizada**

```php
// arquivo: modules/business_rules/business_rules.php

hooks()->add_filter('chargemanager_charge_edit_data', 'apply_business_rules', 5, 2);

function apply_business_rules($update_data, $current_charge) {
    // Regra 1: Não permitir aumento de valor > 20% sem aprovação
    if (isset($update_data['value'])) {
        $increase_percent = (($update_data['value'] - $current_charge->value) / $current_charge->value) * 100;
        
        if ($increase_percent > 20) {
            // Marcar para aprovação
            $update_data['requires_approval'] = 1;
            $update_data['approval_reason'] = "Aumento de valor superior a 20% ({$increase_percent}%)";
        }
    }
    
    // Regra 2: Ajustar data de vencimento para dia útil
    if (isset($update_data['due_date'])) {
        $update_data['due_date'] = adjust_to_business_day($update_data['due_date']);
    }
    
    return $update_data;
}
```

### 4. **Sincronização com Gateway**

```php
// arquivo: modules/gateway_sync/gateway_sync.php

hooks()->add_action('after_chargemanager_charge_edit', 'sync_charge_with_gateway');

function sync_charge_with_gateway($data) {
    // Apenas sincronizar se campos relevantes foram alterados
    $gateway_fields = ['value', 'due_date', 'billing_type'];
    $needs_sync = array_intersect($gateway_fields, $data['updated_fields']);
    
    if (!empty($needs_sync) && !empty($data['current_data']->gateway_charge_id)) {
        $CI = &get_instance();
        $CI->load->library('chargemanager/Gateway_manager');
        
        $sync_result = $CI->gateway_manager->update_charge(
            $data['current_data']->gateway_charge_id,
            [
                'value' => $data['current_data']->value,
                'due_date' => $data['current_data']->due_date,
                'billing_type' => $data['current_data']->billing_type
            ]
        );
        
        if (!$sync_result['success']) {
            log_activity('Falha na sincronização do charge #' . $data['charge_id'] . ' com gateway: ' . $sync_result['message']);
        }
    }
}
```

## 📝 Como Usar os Hooks

### 1. **Em um Módulo**

```php
// No arquivo principal do seu módulo (ex: my_module.php)
hooks()->add_action('after_chargemanager_charge_edit', 'my_module_handle_charge_edit');

function my_module_handle_charge_edit($data) {
    // Sua lógica aqui
}
```

### 2. **Em um Plugin/Theme**

```php
// No arquivo functions.php ou equivalente
add_action('after_chargemanager_charge_edit', function($data) {
    // Sua lógica aqui
});
```

### 3. **Prioridade dos Hooks**

```php
// Hook com prioridade alta (executa primeiro)
hooks()->add_action('after_chargemanager_charge_edit', 'critical_function', 5);

// Hook com prioridade normal
hooks()->add_action('after_chargemanager_charge_edit', 'normal_function', 10);

// Hook com prioridade baixa (executa por último)
hooks()->add_action('after_chargemanager_charge_edit', 'cleanup_function', 15);
```

## ⚠️ Boas Práticas

1. **Sempre verificar se os dados existem** antes de usá-los
2. **Tratar exceções** adequadamente nos hooks
3. **Não fazer operações pesadas** em hooks síncronos
4. **Documentar** hooks customizados
5. **Testar** thoroughly em ambiente de desenvolvimento
6. **Log de atividades** para debug e auditoria

## 🔍 Debug de Hooks

```php
// Ativar debug de hooks (apenas desenvolvimento)
hooks()->add_action('before_chargemanager_charge_edit', function($data) {
    error_log('ChargeManager Debug: Editando charge #' . $data['charge_id']);
    error_log('Update data: ' . json_encode($data['update_data']));
});

hooks()->add_action('after_chargemanager_charge_edit', function($data) {
    error_log('ChargeManager Debug: Charge #' . $data['charge_id'] . ' editado com sucesso');
    error_log('Campos atualizados: ' . implode(', ', $data['updated_fields']));
});
``` 