# Implementação do Recurso "Pedras"

O objetivo desta implementação é adicionar a funcionalidade de marcar um Cliente como "Pedra". Clientes marcados como "Pedra" não devem aparecer nas abas de cobranças ativas e nem na lista de clientes regulares, mas sim em uma aba dedicada chamada "Pedras".

## Resumo das Mudanças

### 1. Banco de Dados e Models
- Verificar se a coluna `PEDRAS` já existe na tabela `CLIENTE` (já está definida no `$fillable` do model `Cliente.php`). Se não existir, criar uma migration dentro da pasta correspondente em `dev/backend`.
- O valor será um boolean/tinyint (0 ou 1).

### 2. ContatoRepository & CobrancaRepository
- Atualizar as consultas (ex: `getClientesComTelefones`, buscas de cobranças) para **excluir** onde `PEDRAS = 1` por padrão.
- Criar um método `getClientesPedras` para listar especificamente os clientes marcados.
- Criar método `togglePedra($idCliente)` para alternar o status.

### 3. Controllers
- **`ContatosController.php`**: 
  - Adicionar o tratamento para `$aba === 'pedras'`, passando os clientes marcados para a view.
  - Adicionar o endpoint `togglePedra` (POST via AJAX ou redirect) para alterar a propriedade.
- **`CobrancaController.php`**:
  - Garantir que a listagem não exiba "Pedras" nas cobranças (isto será resolvido se o repositório ocultá-los por padrão).

### 4. Frontend (Views e JS)
- **`contatos.blade.php`**:
  - Adicionar a nova tab **"Pedras"** ao lado de "Clientes", "Representantes", etc.
  - Na coluna de ações da tabela (tanto em "Clientes" quanto em "Pedras"), adicionar um botão para "Tornar Pedra" ou "Remover Pedra".
- **`cobrancas.blade.php`**:
  - Nenhum cliente marcado como pedra deve aparecer na lista (isso será tratado pelo backend, mas garantiremos a validação visual).

## Open Questions

> [!IMPORTANT]
> - Os "Pedras" devem continuar aparecendo nas tabelas de "Contatos Financeiros" ou "Representantes", ou essa regra se aplica estritamente a "Clientes"?
> - Você gostaria de um ícone específico (ex: uma pedra/rocha, um cadeado ou um escudo) para representar a ação de "Tornar Pedra"?

## User Review Required

> [!WARNING]
> Isso afetará como os clientes aparecem no sistema. Por favor, revise se as regras de negócio coincidem com a sua necessidade antes de eu prosseguir.
