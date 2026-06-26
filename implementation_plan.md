# Padronização das Tabelas de Baixas

O objetivo é aplicar o mesmo layout limpo e interativo da aba de Baixas do Contas a Receber para a tela de Perfil (Minhas Baixas) e a tela de Divergências. O padrão visual consiste em uma tabela principal agrupada por cliente (onde você clica em qualquer lugar da linha) e um modal que se abre exibindo os detalhes específicos.

## User Review Required
Nenhuma alteração destrutiva. Apenas mudança na forma como as tabelas são renderizadas. Você concorda com os dados que serão agrupados nas tabelas antes do clique (conforme descrito abaixo)?

## Proposed Changes

---

### Perfil (Minhas Baixas)

A tabela atual de "Minhas Baixas" exibe as baixas soltas. Vamos modificá-la para exibir apenas os clientes que possuem baixas feitas por você.

#### [MODIFY] dev/backend/Controllers/PerfilController.php
- Alterar a query de `$minhasBaixas` para agrupar por `ID_CLIENTE` (retornando `TOTAL_BAIXADO`, `QTD_BAIXAS` e `ULTIMA_BAIXA`), seguindo a mesma estrutura que fizemos no `ContasReceberController`.
- Criar a rota/método `apiBaixasColaborador` para puxar os detalhes e renderizar o modal (filtrando pelo ID do colaborador logado e do cliente selecionado).

#### [MODIFY] dev/frontend/views/pages/perfil.blade.php
- Atualizar o layout da tabela `table-minhas-baixas` para ter as colunas: "Última Baixa", "Cliente", "Qtd. Baixas", "Total Baixado".
- Transformar as linhas em elementos clicáveis que disparam a função de abrir o modal.

---

### Divergências

A tela de divergências atualmente usa um sistema de "acordeão" (clicar na setinha para expandir a tabela e ver os pedidos). Vamos simplificar e usar a mesma interface limpa.

#### [MODIFY] dev/frontend/views/pages/divergencia_bling.blade.php
- O backend de divergências (`DivergenciaController`) já organiza os dados pela chave `$gruposCli` (agrupados por cliente).
- Iremos alterar a tabela principal para exibir as seguintes colunas de resumo do cliente: "Cliente", "Pedidos Div.", "Total Local", "Total Bling", "Diferença".
- A linha inteira será clicável e abrirá um modal.

#### [NEW] dev/frontend/views/components/modal_divergencias_cliente.blade.php
- Este componente será carregado dentro do modal.
- Listará os pedidos divergentes do cliente, exibindo as ações de **Corrigir Baixa** e **Estornar** por pedido.

#### [MODIFY] dev/backend/Controllers/DivergenciaController.php
- Criar um endpoint `apiDivergenciasCliente` para retornar a View do modal (`modal_divergencias_cliente.blade.php`) com os detalhes pré-calculados.

## Verification Plan

### Manual Verification
1. Acessar a página "Meu Perfil" e verificar se as baixas estão agrupadas por cliente.
2. Clicar em um cliente no Perfil e verificar se o modal abre exibindo os registros de baixa do colaborador.
3. Acessar a tela de "Divergências", verificar a tabela simplificada por cliente.
4. Clicar no cliente divergente e validar o modal com as ações de corrigir/estornar funcionando.
