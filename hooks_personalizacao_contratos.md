# Hooks para Personalização de Contratos no Perfex CRM

Este documento explica como usar os hooks disponíveis no módulo ChargeManager para personalizar a criação e edição de contratos no Perfex CRM.

## Visão Geral

Os hooks são pontos de extensão que permitem adicionar funcionalidades personalizadas sem modificar o código core do sistema. O módulo ChargeManager implementa vários hooks para permitir a personalização do processo de contratos.

## Hooks Disponíveis

### 1. `contract_extra_fields`

**Tipo**: Action Hook  
**Função**: `add_contract_billing_schema_fields`  
**Arquivo**: `helpers/contract_billing_schema_helper.php`

#### Descrição
Este hook é executado na página de criação/edição de contratos e permite adicionar campos extras ao formulário.

#### Como Usar
```php
// Registrar o hook
hooks()->add_action('contract_extra_fields', 'minha_funcao_campos_extras');

// Implementar a função
function minha_funcao_campos_extras($contract = null) {
    $contract_id = isset($contract) ? $contract->id : null;
    
    // Adicionar seus campos personalizados aqui
    echo '<div class="form-group">';
    echo '<label for="meu_campo" class="control-label">Meu Campo Personalizado</label>';
    echo '<input type="text" name="meu_campo" id="meu_campo" class="form-control">';
    echo '</div>';
}
```

#### Exemplo Prático
O ChargeManager usa este hook para adicionar toda a interface de configuração de cobranças:
- Seletor de tipo (manual/automático)
- Campos de frequência e valor
- Campo de data da primeira parcela
- Lista dinâmica de cobranças

---

### 2. `before_contract_added`

**Tipo**: Filter Hook  
**Função**: `process_contract_billing_schema_on_add`  
**Arquivo**: `helpers/contract_billing_schema_helper.php`

#### Descrição
Este hook é executado antes de um contrato ser salvo no banco de dados. Permite processar e filtrar os dados antes da inserção.

#### Como Usar
```php
// Registrar o hook
hooks()->add_filter('before_contract_added', 'processar_dados_antes_adicionar');

// Implementar a função
function processar_dados_antes_adicionar($data) {
    // Processar campos personalizados
    if (isset($data['meu_campo'])) {
        // Validar ou transformar o dado
        $data['meu_campo'] = sanitize_text_field($data['meu_campo']);
        
        // Armazenar em variável global para uso posterior
        $GLOBALS['meus_dados_personalizados'] = $data['meu_campo'];
        
        // Remover do array para evitar erro SQL (se não for campo da tabela contracts)
        unset($data['meu_campo']);
    }
    
    return $data;
}
```

#### Exemplo Prático
O ChargeManager usa este hook para:
- Extrair campos do schema de cobrança (`schema_type`, `frequency`, etc.)
- Armazenar os dados em variável global
- Remover os campos do array principal para evitar erros SQL

---

### 3. `after_contract_added`

**Tipo**: Action Hook  
**Função**: `save_contract_billing_schema_after_add`  
**Arquivo**: `helpers/contract_billing_schema_helper.php`

#### Descrição
Este hook é executado após um contrato ser salvo com sucesso. Ideal para salvar dados relacionados que dependem do ID do contrato.

#### Como Usar
```php
// Registrar o hook
hooks()->add_action('after_contract_added', 'salvar_dados_apos_adicionar');

// Implementar a função
function salvar_dados_apos_adicionar($contract_id) {
    // Verificar se temos dados para salvar
    if (isset($GLOBALS['meus_dados_personalizados'])) {
        $CI = &get_instance();
        
        // Salvar em tabela personalizada
        $CI->db->insert('minha_tabela_personalizada', [
            'contract_id' => $contract_id,
            'meu_campo' => $GLOBALS['meus_dados_personalizados'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Limpar variável global
        unset($GLOBALS['meus_dados_personalizados']);
        
        log_activity('Dados personalizados salvos para contrato #' . $contract_id);
    }
}
```

#### Exemplo Prático
O ChargeManager usa este hook para:
- Salvar o schema de cobrança na tabela `chargemanager_contract_billing_schemas`
- Associar o schema ao ID do contrato recém-criado
- Limpar as variáveis globais temporárias

---

### 4. `before_contract_updated`

**Tipo**: Filter Hook  
**Função**: `process_contract_billing_schema_on_update`  
**Arquivo**: `helpers/contract_billing_schema_helper.php`  
**Parâmetros**: 2 (data, id)

#### Descrição
Este hook é executado antes de um contrato ser atualizado. Similar ao `before_contract_added`, mas para atualizações.

#### Como Usar
```php
// Registrar o hook (prioridade 10, 2 parâmetros)
hooks()->add_filter('before_contract_updated', 'processar_dados_antes_atualizar', 10, 2);

// Implementar a função
function processar_dados_antes_atualizar($data, $contract_id) {
    // Processar campos personalizados
    if (isset($data['meu_campo'])) {
        // Atualizar tabela personalizada diretamente
        $CI = &get_instance();
        
        $CI->db->where('contract_id', $contract_id);
        $existing = $CI->db->get('minha_tabela_personalizada')->row();
        
        if ($existing) {
            // Atualizar registro existente
            $CI->db->where('contract_id', $contract_id);
            $CI->db->update('minha_tabela_personalizada', [
                'meu_campo' => $data['meu_campo'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            // Criar novo registro
            $CI->db->insert('minha_tabela_personalizada', [
                'contract_id' => $contract_id,
                'meu_campo' => $data['meu_campo'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Remover do array principal
        unset($data['meu_campo']);
    }
    
    return $data;
}
```

#### Exemplo Prático
O ChargeManager usa este hook para:
- Extrair campos do schema de cobrança
- Atualizar ou criar registro na tabela de schemas
- Remover campos do array principal

## Exemplo Completo: Adicionando Campo Personalizado

Aqui está um exemplo completo de como adicionar um campo personalizado aos contratos:

### 1. Criar a Tabela (opcional)
```php
function criar_tabela_personalizada() {
    $CI = &get_instance();
    
    if (!$CI->db->table_exists(db_prefix() . 'contract_custom_fields')) {
        $CI->db->query("
            CREATE TABLE `" . db_prefix() . "contract_custom_fields` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `contract_id` int(11) NOT NULL,
              `observacoes_internas` TEXT NULL,
              `prioridade` varchar(20) DEFAULT 'normal',
              `created_at` datetime NOT NULL,
              `updated_at` datetime NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `contract_id` (`contract_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}

hooks()->add_action('app_init', 'criar_tabela_personalizada');
```

### 2. Adicionar Campos ao Formulário
```php
function adicionar_campos_personalizados($contract = null) {
    $contract_id = isset($contract) ? $contract->id : null;
    
    // Carregar dados existentes
    $dados_personalizados = null;
    if ($contract_id) {
        $CI = &get_instance();
        $CI->db->where('contract_id', $contract_id);
        $dados_personalizados = $CI->db->get(db_prefix() . 'contract_custom_fields')->row();
    }
    
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">Campos Personalizados</div>';
    echo '<div class="panel-body">';
    
    // Campo de observações internas
    echo '<div class="form-group">';
    echo '<label for="observacoes_internas" class="control-label">Observações Internas</label>';
    echo '<textarea name="observacoes_internas" id="observacoes_internas" class="form-control" rows="3">';
    echo $dados_personalizados ? htmlspecialchars($dados_personalizados->observacoes_internas) : '';
    echo '</textarea>';
    echo '</div>';
    
    // Campo de prioridade
    echo '<div class="form-group">';
    echo '<label for="prioridade" class="control-label">Prioridade</label>';
    echo '<select name="prioridade" id="prioridade" class="form-control">';
    echo '<option value="baixa"' . ($dados_personalizados && $dados_personalizados->prioridade == 'baixa' ? ' selected' : '') . '>Baixa</option>';
    echo '<option value="normal"' . (!$dados_personalizados || $dados_personalizados->prioridade == 'normal' ? ' selected' : '') . '>Normal</option>';
    echo '<option value="alta"' . ($dados_personalizados && $dados_personalizados->prioridade == 'alta' ? ' selected' : '') . '>Alta</option>';
    echo '</select>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
}

hooks()->add_action('contract_extra_fields', 'adicionar_campos_personalizados');
```

### 3. Processar Dados na Criação
```php
function processar_campos_personalizados_adicionar($data) {
    $campos_personalizados = [];
    
    if (isset($data['observacoes_internas'])) {
        $campos_personalizados['observacoes_internas'] = $data['observacoes_internas'];
        unset($data['observacoes_internas']);
    }
    
    if (isset($data['prioridade'])) {
        $campos_personalizados['prioridade'] = $data['prioridade'];
        unset($data['prioridade']);
    }
    
    if (!empty($campos_personalizados)) {
        $GLOBALS['contract_custom_data'] = $campos_personalizados;
    }
    
    return $data;
}

hooks()->add_filter('before_contract_added', 'processar_campos_personalizados_adicionar');
```

### 4. Salvar Dados Após Criação
```php
function salvar_campos_personalizados_apos_adicionar($contract_id) {
    if (isset($GLOBALS['contract_custom_data'])) {
        $CI = &get_instance();
        
        $dados = $GLOBALS['contract_custom_data'];
        $dados['contract_id'] = $contract_id;
        $dados['created_at'] = date('Y-m-d H:i:s');
        $dados['updated_at'] = date('Y-m-d H:i:s');
        
        $CI->db->insert(db_prefix() . 'contract_custom_fields', $dados);
        
        unset($GLOBALS['contract_custom_data']);
        
        log_activity('Campos personalizados salvos para contrato #' . $contract_id);
    }
}

hooks()->add_action('after_contract_added', 'salvar_campos_personalizados_apos_adicionar');
```

### 5. Processar Atualizações
```php
function processar_campos_personalizados_atualizar($data, $contract_id) {
    $campos_personalizados = [];
    
    if (isset($data['observacoes_internas'])) {
        $campos_personalizados['observacoes_internas'] = $data['observacoes_internas'];
        unset($data['observacoes_internas']);
    }
    
    if (isset($data['prioridade'])) {
        $campos_personalizados['prioridade'] = $data['prioridade'];
        unset($data['prioridade']);
    }
    
    if (!empty($campos_personalizados)) {
        $CI = &get_instance();
        
        // Verificar se já existe
        $CI->db->where('contract_id', $contract_id);
        $existing = $CI->db->get(db_prefix() . 'contract_custom_fields')->row();
        
        $campos_personalizados['updated_at'] = date('Y-m-d H:i:s');
        
        if ($existing) {
            $CI->db->where('contract_id', $contract_id);
            $CI->db->update(db_prefix() . 'contract_custom_fields', $campos_personalizados);
        } else {
            $campos_personalizados['contract_id'] = $contract_id;
            $campos_personalizados['created_at'] = date('Y-m-d H:i:s');
            $CI->db->insert(db_prefix() . 'contract_custom_fields', $campos_personalizados);
        }
        
        log_activity('Campos personalizados atualizados para contrato #' . $contract_id);
    }
    
    return $data;
}

hooks()->add_filter('before_contract_updated', 'processar_campos_personalizados_atualizar', 10, 2);
```

## Boas Práticas

### 1. Nomenclatura
- Use prefixos únicos para evitar conflitos (`meumodulo_`, `empresa_`, etc.)
- Nomes descritivos para funções e variáveis

### 2. Validação
- Sempre valide e sanitize os dados recebidos
- Use `htmlspecialchars()` para output HTML
- Implemente validações de negócio apropriadas

### 3. Tratamento de Erros
- Use try/catch quando apropriado
- Log erros com `log_activity()`
- Forneça feedback adequado ao usuário

### 4. Performance
- Evite consultas desnecessárias ao banco
- Use cache quando apropriado
- Otimize consultas SQL

### 5. Compatibilidade
- Teste com diferentes versões do Perfex CRM
- Mantenha compatibilidade com outros módulos
- Documente dependências

## Conclusão

Os hooks do Perfex CRM oferecem uma maneira poderosa e flexível de estender a funcionalidade dos contratos sem modificar o código core. O módulo ChargeManager demonstra como usar esses hooks efetivamente para adicionar funcionalidades complexas de forma organizada e maintível.

Lembre-se sempre de testar suas implementações em ambiente de desenvolvimento antes de aplicar em produção, e mantenha backups regulares do seu sistema.