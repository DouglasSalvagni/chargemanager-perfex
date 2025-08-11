# Hook: after_chargemanager_entry_charge_paid

Este hook é disparado quando uma cobrança de entrada (`is_entry_charge = 1`) é confirmada como paga através do webhook.

## Parâmetros

O hook recebe um array com as seguintes informações:

```php
[
    'charge_id' => int,      // ID da cobrança na tabela chargemanager_charges
    'contract_id' => int,    // ID do contrato vinculado (pode ser null)
    'charge' => object       // Objeto completo da cobrança atualizada
]
```

## Exemplo de Uso

```php
// No arquivo hooks/chargemanager_hooks.php ou similar

hooks()->add_action('after_chargemanager_entry_charge_paid', 'handle_entry_charge_payment');

function handle_entry_charge_payment($data) {
    $charge_id = $data['charge_id'];
    $contract_id = $data['contract_id'];
    $charge = $data['charge'];
    
    // Log da ação
    log_activity('Cobrança de entrada paga - Charge ID: ' . $charge_id . ', Contract ID: ' . $contract_id);
    
    // Exemplo: Atualizar status do contrato
    if ($contract_id) {
        $CI = &get_instance();
        $CI->db->where('id', $contract_id);
        $CI->db->update(db_prefix() . 'contracts', [
            'entry_payment_received' => 1,
            'entry_payment_date' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Exemplo: Enviar notificação personalizada
    // send_custom_notification($charge, $contract_id);
    
    // Exemplo: Integração com sistema externo
    // sync_payment_with_external_system($charge_id, $contract_id);
}
```

## Casos de Uso

1. **Ativação de Contratos**: Marcar contrato como ativo após pagamento da entrada
2. **Notificações Personalizadas**: Enviar emails ou SMS específicos para pagamento de entrada
3. **Integrações**: Sincronizar com sistemas externos quando entrada for paga
4. **Automações**: Disparar processos automáticos específicos para cobranças de entrada
5. **Relatórios**: Gerar relatórios específicos de entradas pagas

## Observações

- O `contract_id` pode ser `null` se a cobrança não estiver vinculada a um contrato
- O hook só é disparado para cobranças com `is_entry_charge = 1`
- O objeto `charge` contém todos os dados atualizados da cobrança
- Este hook é disparado após a atualização do status da cobrança no banco de dados