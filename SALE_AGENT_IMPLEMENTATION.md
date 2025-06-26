# Implementação do Campo Sale Agent (Vendedor)

## Resumo
Implementação completa do campo `sale_agent` no módulo ChargeManager para vincular vendedores aos billing groups e invoices gerados.

## Alterações Realizadas

### 1. Base de Dados
- **Arquivo**: `install.php`
- **Alteração**: Adicionada coluna `sale_agent INT(11) NULL DEFAULT NULL` na tabela `chargemanager_billing_groups`
- **Índice**: Adicionado índice para a coluna `sale_agent`
- **Migration**: Incluída lógica para adicionar a coluna em instalações existentes

### 2. Models

#### Chargemanager_billing_groups_model.php
- Adicionado método `get_active_staff()` para buscar vendedores ativos
- Modificado `get_with_relationships()` para incluir informações do vendedor
- Carrega dados do staff quando `sale_agent` está definido

#### Chargemanager_charges_model.php
- Modificado `generate_individual_invoice()` para incluir `sale_agent` do billing group no invoice
- Fallback para o usuário atual caso não haja vendedor definido
- Garante que todos os invoices gerados tenham um vendedor associado

### 3. Controllers

#### Billing_groups.php
- Adicionado método `get_staff()` para AJAX (busca vendedores ativos)
- Modificado `create()` para validar e salvar `sale_agent`
- Modificado `view()` para carregar lista de staff members
- Adicionado método `update()` para atualizar informações básicas incluindo `sale_agent`
- Validação de vendedor ativo durante criação e atualização

### 4. Views

#### views/admin/client/billing_groups_tab.php
- Adicionado campo select para `sale_agent` no formulário de criação
- Lista todos os staff members ativos
- Campo opcional (pode ficar em branco)

#### views/admin/billing_groups/view.php
- Exibe informações do vendedor na seção de detalhes
- Link para o perfil do vendedor
- Mostra "No Sale Agent" quando não há vendedor definido

#### views/admin/billing_groups/edit.php
- Adicionada seção "Basic Information" com campo editável para `sale_agent`
- Permite alterar vendedor e status do billing group
- Formulário dedicado para atualização de informações básicas

### 5. Idioma
- **Arquivo**: `language/english/chargemanager_lang.php`
- Adicionadas traduções para todos os novos campos e funcionalidades
- Mensagens de erro e validação

### 6. JavaScript
- **Arquivo**: `assets/js/billing_groups_tab.js`
- Adicionada validação opcional para o campo `sale_agent`
- Verifica se o valor é numérico quando fornecido

## Funcionalidades Implementadas

### 1. Criação de Billing Group
- Campo opcional para selecionar vendedor
- Lista todos os staff members ativos
- Validação de vendedor ativo

### 2. Visualização
- Exibe vendedor associado ao billing group
- Link para perfil do vendedor
- Informação clara quando não há vendedor

### 3. Edição
- Permite alterar vendedor do billing group
- Validação de staff ativo
- Atualização via formulário dedicado

### 4. Geração de Invoices
- Todos os invoices herdam o vendedor do billing group
- Campo `sale_agent` preenchido automaticamente
- Fallback para usuário atual se não houver vendedor

## Benefícios

1. **Rastreamento de Vendas**: Cada billing group tem vendedor responsável
2. **Relatórios**: Base para relatórios por vendedor
3. **Comissões**: Estrutura para futuro sistema de comissões
4. **Responsabilidade**: Clara atribuição de responsabilidade
5. **Integração**: Usa sistema nativo de staff do Perfex CRM

## Instalação

### Para Novas Instalações
- Execute o módulo normalmente, a coluna será criada automaticamente

### Para Instalações Existentes
1. Execute o script SQL: `add_sale_agent_column.sql`
2. Ou acesse o módulo via admin, a migração será executada automaticamente

## Uso

1. **Criar Billing Group**: Selecione vendedor no formulário (opcional)
2. **Visualizar**: Veja vendedor na página de detalhes
3. **Editar**: Altere vendedor na seção "Basic Information"
4. **Invoices**: Gerados automaticamente com vendedor do billing group

## Validações

- Apenas staff members ativos podem ser selecionados
- Campo é opcional (pode ficar NULL)
- Validação tanto no frontend quanto backend
- Logs de atividade para auditoria

## Compatibilidade

- Mantém compatibilidade com instalações existentes
- Não quebra funcionalidades existentes
- Segue padrões do Perfex CRM
- Reutiliza componentes nativos do sistema 