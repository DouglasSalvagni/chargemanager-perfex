# ChargeManager - Implementação de Schema de Cobrança para Contratos

## Solicitação Inicial

Foi solicitada a implementação de uma funcionalidade para o módulo ChargeManager do Perfex CRM que permitisse configurar cobranças diretamente na página de edição de contratos. Essas cobranças seriam geradas automaticamente quando o contrato fosse assinado.

A solução deveria complementar o sistema existente de billing groups, que já permitia criar cobranças manualmente após a assinatura do contrato.

## Funcionalidades Implementadas

### 1. Estrutura de Dados
- Criação da tabela `chargemanager_contract_billing_schemas` para armazenar os schemas de cobrança
- Campos para tipo de schema (manual/automático), frequência, valor da parcela e dados do schema em JSON
- Migração de dados da tabela antiga para a nova (se existir)

### 2. Interface na Página de Contrato
- Adição de campos para configuração de cobranças na página de edição de contratos
- Dois modos de configuração:
  - **Automático**: Baseado em frequência (semanal, quinzenal, mensal) e valor da parcela
  - **Manual**: Adição individual de cobranças, similar à interface existente
- Validação do valor total das cobranças contra o valor do contrato
- Visualização das cobranças em ambos os modos

### 3. Geração Automática de Cobranças
- Quando um contrato é assinado, as cobranças são geradas automaticamente
- Criação de um billing group vinculado ao contrato
- Geração de cobranças no gateway de pagamento (ASAAS)
- Criação de faturas individuais para cada cobrança

### 4. Arquivos Criados/Modificados
- **helpers/contract_billing_schema_helper.php**: Lógica principal para adicionar campos e processar dados
- **assets/js/contract_billing_schema.js**: JavaScript para manipulação da interface
- **install.php**: Adição da criação da tabela
- **chargemanager.php**: Atualização para carregar o novo helper

## Detalhes Técnicos

### Hooks Utilizados
- `contract_extra_fields`: Para adicionar campos na página de edição de contratos
- `before_contract_added` e `after_contract_added`: Para processar e salvar os dados do schema
- `before_contract_updated`: Para atualizar o schema quando o contrato é atualizado
- `contract_marked_as_signed` e `after_contract_signed`: Para criar cobranças quando o contrato é assinado

### Fluxo de Funcionamento
1. **Na página de edição de contrato**:
   - O usuário escolhe entre configuração manual ou automática
   - Configura as cobranças conforme o modo escolhido
   - O sistema valida se o total das cobranças corresponde ao valor do contrato

2. **Quando o contrato é salvo**:
   - O schema de cobranças é salvo na tabela `chargemanager_contract_billing_schemas`
   - Os dados são armazenados em formato JSON na coluna `schema_data`

3. **Quando o contrato é assinado**:
   - O sistema verifica se existe um schema de cobranças
   - Cria um billing group vinculado ao contrato
   - Gera as cobranças no gateway de pagamento (ASAAS)
   - Salva as cobranças no banco de dados local
   - Gera faturas individuais para cada cobrança

## Desafios e Soluções

### Desafios Enfrentados
1. **Erro de parâmetros**: A função `process_contract_billing_schema_on_update` esperava 2 parâmetros, mas recebia apenas 1
2. **Problemas de renderização**: A estrutura HTML estava causando quebras na renderização
3. **Visibilidade das cobranças**: As cobranças não eram exibidas corretamente no modo automático
4. **Erro de coluna desconhecida**: O nome da coluna no banco de dados não correspondia ao nome usado no código

### Soluções Implementadas
1. **Ajuste de parâmetros**: Modificação da função para aceitar apenas um parâmetro e obter o `contract_id` do array de dados
2. **Reorganização do HTML**: Estrutura HTML completamente reorganizada para evitar quebras
3. **Ajuste do JavaScript**: Garantia de que as cobranças sejam exibidas em ambos os modos
4. **Padronização de nomes**: Alteração do nome do campo para corresponder à coluna no banco de dados

## Benefícios da Implementação

1. **Automação**: Redução do trabalho manual na criação de cobranças
2. **Consistência**: Garantia de que as cobranças correspondam ao valor do contrato
3. **Flexibilidade**: Suporte a diferentes frequências e valores de parcela
4. **Integração**: Funcionamento harmonioso com o sistema existente de billing groups
5. **Experiência do usuário**: Interface intuitiva e fácil de usar

Esta implementação complementa o sistema existente, permitindo configurar as cobranças durante a criação/edição do contrato e automatizando a geração quando o contrato é assinado.