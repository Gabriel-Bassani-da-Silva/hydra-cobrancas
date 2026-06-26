@extends('layouts.app')

@section('title', 'Contas a Receber')
@section('body_class', 'contas-receber-page')

@section('content')
<?php
$title = "Contas a Receber";
$body_class = "contas-receber-page";
$show_header = true;



$isPagos = request('status') === 'pagos';
?>

<div class="cr-wrapper" data-initial-state='<?= htmlspecialchars(json_encode([
    "aba" => $aba ?? "clientes",
    "grupo" => $grupo ?? "padrao",
    "status" => request('status', 'pendentes'),
    "isPagos" => $isPagos,
    "clientes" => $resumoClientes ?? [],
    "representantes" => $resumoRepresentantes ?? [],
    "financeiros" => $resumoContatosFinanceiros ?? [],
    "pedidos" => $todosPedidos ?? [],
    "cobrancasAtivas" => $cobrancasAtivas ?? ["clientes"=>[], "financeiros"=>[], "representantes"=>[]]
]), ENT_QUOTES, "UTF-8") ?>'>
    <!-- Barra de Ações -->
    <div class="cr-header-actions">
        <div class="header-title-section">
            <h2>Contas a Receber</h2>
            <p>Gerencie e sincronize contas a receber do Bling</p>
        </div>
        <div class="actions-buttons">
            <button onclick="sincronizarPedidoPorId()" class="btn-sync btn-secondary btn-primary-override" title="Sincronizar pedido específico pelo ID do Bling">
                <x-icons.folder-open width="16" height="16" />
                Sincronizar por ID
            </button>
            <a href="{{ route('sincronizar-contas-receber') }}?aba=<?= $aba ?>"
               id="btn-sincronizar"
               class="btn-sync"
               title="Baixar alterações recentes (Rápido)">
                <x-icons.folder-open width="16" height="16" />
                Sinc. Rápida
            </a>
            <a href="{{ route('sincronizar-contas-receber') }}?full=1&aba=<?= $aba ?>"
               id="btn-sincronizar-full"
               class="btn-sync btn-warning"
               title="Verificação Completa (Varre todas as contas para encontrar apagadas e pagas)"
               onclick="return confirm('A Verificação Completa analisa todas as contas em aberto no Bling. Isso pode demorar um pouco mais. Deseja continuar?');">
                <x-icons.icon-14 width="16" height="16" />
                Verificação Completa
            </a>
            <a href="{{ route('vincular-reps-contas') }}?aba=<?= $aba ?>"
               class="btn-sync btn-secondary"
               title="Busca os vendedores no Bling para os pedidos que estão sem vendedor localmente"
               onclick="return confirm('Isso buscará detalhes de pedidos sem representante no Bling. Pode demorar alguns minutos. Deseja continuar?');">
                <x-icons.users width="16" height="16" />
                Vincular Vendedores
            </a>
        </div>
    </div>

    <!-- Mensagens Flash -->
    @if(session('flash_msg'))
        <div class="flash-message flash-{{ session('flash_type', 'info') }}">
            <span>{{ session('flash_msg') }}</span>
            <button class="flash-close" onclick="this.parentElement.remove()">✕</button>
        </div>
        
    @endif

    <!-- Barra de Busca -->
    <div class="cr-search-bar cr-search-bar-row">
        <div class="search-input-wrapper">
            <x-icons.search width="18" height="18" class="search-icon" />
            <input type="text" id="cr-search" placeholder="Filtrar tabela por nome, documento ou valor..." autocomplete="off">
        </div>
        
        <form action="{{ route('salvar-exibir-ate') }}" method="POST" id="form-exibir-ate" class="filter-date-form">
            @csrf
            <input type="hidden" name="action_type" id="input-action-type" value="">
            <div class="filter-date-group">
                <!-- Form A partir de -->
                <div class="filter-date-row">
                    <label class="filter-date-label">A partir de:</label>
                    <input type="date" name="exibir_a_partir_de" value="<?= $exibirAPartirDe ?: '' ?>" id="input-exibir-partir"
                           data-original="<?= $exibirAPartirDe ?: '' ?>" class="filter-date-input">
                    <button type="button" onclick="abrirModalExibirAte('salvar_partir', 'A partir de')" title="Salvar 'A partir de'" class="filter-date-btn-save">
                        <x-icons.check width="16" height="16" />
                    </button>
                    <?php if ($exibirAPartirDe): ?>
                        <button type="button" onclick="abrirModalExibirAte('limpar_partir', 'A partir de')" title="Limpar 'A partir de'" class="filter-date-btn-clear">✕</button>
                    @endif
                </div>

                <!-- Form Exibir até -->
                <div class="filter-date-row">
                    <label class="filter-date-label">Exibir até:</label>
                    <input type="date" name="exibir_ate" value="<?= $exibirAte ?: '' ?>" id="input-exibir-ate"
                           data-original="<?= $exibirAte ?: '' ?>" class="filter-date-input">
                    <button type="button" onclick="abrirModalExibirAte('salvar_ate', 'Exibir até')" title="Salvar 'Exibir até'" class="filter-date-btn-save">
                        <x-icons.check width="16" height="16" />
                    </button>
                    <?php if ($exibirAte): ?>
                        <button type="button" onclick="abrirModalExibirAte('limpar_ate', 'Exibir até')" title="Limpar 'Exibir até'" class="filter-date-btn-clear">✕</button>
                    @endif
                </div>
            </div>
        </form>
        <?php if ($aba !== 'pedidos'): ?>
        <div id="filtro-cobranca-toggle" class="filtro-cobranca-toggle">
            <button id="btn-filtro-sem-cobranca" class="filtro-cob-btn filtro-cob-btn--active" onclick="setFiltroCobranca('sem')">
                <x-icons.check-1 width="13" height="13" />
                Disponíveis
            </button>
            <button id="btn-filtro-todos" class="filtro-cob-btn" onclick="setFiltroCobranca('todos')">
                Todos
            </button>
        </div>
        @endif
    </div>

    <!-- Abas Principais -->
    <div class="tabs">
        <a href="{{ route('contas-receber-page') }}?aba=clientes" class="tab <?= $aba === 'clientes' ? 'active' : '' ?>">
            Clientes <span class="tab-count"><?= $contagensAbas['clientes'] ?? 0 ?></span>
        </a>
        <a href="{{ route('contas-receber-page') }}?aba=representantes" class="tab <?= $aba === 'representantes' ? 'active' : '' ?>">
            Representantes <span class="tab-count"><?= $contagensAbas['representantes'] ?? 0 ?></span>
        </a>
        <a href="{{ route('contas-receber-page') }}?aba=pedidos" class="tab <?= $aba === 'pedidos' ? 'active' : '' ?>">
            Pedidos <span class="tab-count"><?= $contagensAbas['pedidos'] ?? 0 ?></span>
        </a>
        <a href="{{ route('contas-receber-page') }}?aba=pedras" class="tab <?= $aba === 'pedras' ? 'active' : '' ?>">
            Pedras <span class="tab-count"><?= $contagensAbas['pedras'] ?? 0 ?></span>
        </a>
        <a href="{{ route('contas-receber-page') }}?aba=baixas" class="tab <?= $aba === 'baixas' ? 'active' : '' ?>">
            Baixas <span class="tab-count"><?= count($todasBaixas ?? []) ?></span>
        </a>
    </div>

    <!-- Sub-Abas para Clientes, Representantes e Pedras -->
    <?php if ($aba === 'clientes' || $aba === 'representantes' || $aba === 'pedras'): ?>
    <div class="cr-sub-tabs">
        <div class="cr-sub-tabs-group">
            <a href="{{ route('contas-receber-page') }}?aba=<?= $aba ?>&grupo=padrao" class="sub-tab-btn <?= $grupo === 'padrao' ? 'active' : '' ?>">
                <?= $aba === 'pedras' ? 'Clientes' : ucfirst($aba) ?>
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=<?= $aba ?>&grupo=financeiro" class="sub-tab-btn <?= $grupo === 'financeiro' ? 'active' : '' ?>">
                Contatos Financeiros
            </a>
            <?php if ($aba === 'clientes'): ?>
            <a href="{{ route('contas-receber-page') }}?aba=<?= $aba ?>&grupo=cheques" class="sub-tab-btn <?= $grupo === 'cheques' ? 'active' : '' ?>">
                Cheques
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=<?= $aba ?>&grupo=antecipados" class="sub-tab-btn <?= $grupo === 'antecipados' ? 'active' : '' ?>">
                Antecipados
            </a>
            @endif
        </div>
        <div class="cr-total-inadimplente">
            Total Inadimplente:&nbsp;<span>R$ <?= number_format($totalInadimplenteFiltrado, 2, ',', '.') ?></span>
        </div>
    </div>
    @endif

    <!-- Sub-Abas para Pedidos -->
    <?php if ($aba === 'pedidos'): ?>
    <div class="cr-sub-tabs">
        <div class="cr-sub-tabs-group">
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=pendentes" class="sub-tab-btn <?= (request('status', 'pendentes') === 'pendentes') ? 'active' : '' ?>">
                Inadimplentes
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=antecipado" class="sub-tab-btn <?= (request('status') === 'antecipado') ? 'active' : '' ?>">
                Antecipados
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=cheque" class="sub-tab-btn <?= (request('status') === 'cheque') ? 'active' : '' ?>">
                Cheques
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=todos_pendentes" class="sub-tab-btn <?= (request('status') === 'todos_pendentes') ? 'active' : '' ?>">
                Todos os Pendentes
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=pagos" class="sub-tab-btn <?= (request('status') === 'pagos') ? 'active' : '' ?>">
                Pagos
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=todos" class="sub-tab-btn <?= (request('status') === 'todos') ? 'active' : '' ?>">
                Todos
            </a>
        </div>
        <div class="cr-total-inadimplente">
            Total Inadimplente:&nbsp;<span>R$ <?= number_format($totalInadimplenteFiltrado, 2, ',', '.') ?></span>
        </div>
    </div>
    @endif

    <!-- ESQUELETO DAS TABELAS -->
    
    <!-- ABA: CLIENTES / REPRESENTANTES / PEDRAS (PADRÃO) -->
    <?php if (($aba === 'clientes' || $aba === 'representantes' || $aba === 'pedras') && ($grupo === 'padrao' || $grupo === 'cheques' || $grupo === 'antecipados')): ?>
    <div class="card">
        <div class="table-responsive">
            <table class="cr-table" id="table-padrao">
                <thead>
                    <tr>
                        <th></th>
                        <th data-sort="az" class="sortable sortable-header"><?= $aba === 'clientes' ? 'Cliente' : 'Representante' ?> <span class="sort-icon"></span></th>
                        <?php if ($aba === 'representantes'): ?><th>Clientes</th>@endif
                        <th data-sort="qtd" class="center-col sortable sortable-header">Qtd. Pedidos <span class="sort-icon"></span></th>
                        <th data-sort="valor" class="valor-col sortable sortable-header">Total em Aberto <span class="sort-icon"></span></th>
                        <th data-sort="venc" class="date-col sortable sortable-header">Vencimento Mais Antigo <span class="sort-icon"></span></th>
                        <th class="col-status-cobranca">Status Cobrança</th>
                        <th data-sort="tel" class="center-col sortable col-telefone sortable-header">Telefone <span class="sort-icon"></span></th>
                        <th class="cr-col-acoes">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbody-padrao">
                    <!-- JS vai preencher -->
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- ABA: CONTATOS FINANCEIROS -->
    <?php if (($aba === 'clientes' || $aba === 'representantes' || $aba === 'pedras') && ($grupo === 'financeiro')): ?>
    <div class="card">
        <div class="table-responsive">
            <table class="cr-table" id="table-financeiro">
                <thead>
                    <tr>
                        <th></th>
                        <th data-sort="az" class="sortable sortable-header">Contato Financeiro <span class="sort-icon"></span></th>
                        <th data-sort="qtd" class="center-col sortable sortable-header">Qtd. Pedidos <span class="sort-icon"></span></th>
                        <th data-sort="valor" class="valor-col sortable sortable-header">Total em Aberto <span class="sort-icon"></span></th>
                        <th data-sort="venc" class="date-col sortable sortable-header">Vencimento Mais Antigo <span class="sort-icon"></span></th>
                        <th class="col-status-cobranca">Status Cobrança</th>
                        <th class="cr-col-acoes">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbody-financeiro">
                    <!-- JS vai preencher -->
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- ABA: PEDIDOS -->
    <?php if ($aba === 'pedidos'): ?>
    <div class="card">
        <div class="table-responsive">
            <table class="cr-table" id="table-pedidos">
                <thead>
                    <tr>
                        <th></th>
                        <th>Documento</th>
                        <th data-sort="az" class="sortable sortable-header">Cliente <span class="sort-icon"></span></th>
                        <th>Representante</th>
                        <th data-sort="qtd" class="center-col sortable sortable-header">Qtd. Parcelas <span class="sort-icon"></span></th>
                        <th data-sort="valor" class="valor-col sortable sortable-header"><?= $isPagos ? 'Valor Total' : 'Valor Restante' ?> <span class="sort-icon"></span></th>
                        <th data-sort="venc" class="date-col sortable sortable-header">Vencimento <span class="sort-icon"></span></th>
                        <th data-sort="situacao" class="text-center sortable sortable-header-middle">Situação <span class="sort-icon"></span></th>
                        <th class="cr-col-acoes">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbody-pedidos">
                    <!-- JS vai preencher -->
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- ABA: BAIXAS -->
    <?php if ($aba === 'baixas'): ?>
    <?php
        // Agrupar divergências por cliente
        $gruposDivergencias = [];
        $totalDivergencia = 0;
        foreach ($divergencias ?? [] as $div) {
            $nomeCli = !empty($div['NOME_CLIENTE']) ? $div['NOME_CLIENTE'] : 'Não Informado';
            $idCliKey = !empty($div['ID_CLIENTE']) ? $div['ID_CLIENTE'] : $nomeCli;
            if (!isset($gruposDivergencias[$idCliKey])) {
                $gruposDivergencias[$idCliKey] = ['id' => $idCliKey, 'nomeCli' => $nomeCli, 'total_diferenca' => 0, 'total_local' => 0, 'total_bling' => 0, 'qtd_pedidos' => 0, 'pedidos_unicos' => []];
            }
            $keyPed = !empty($div['NUM_PEDIDO']) && $div['NUM_PEDIDO'] !== '—' ? $div['NUM_PEDIDO'] : 'SEM_NUM_' . $div['ID_PEDIDO'];
            if (!isset($gruposDivergencias[$idCliKey]['pedidos_unicos'][$keyPed])) {
                $gruposDivergencias[$idCliKey]['pedidos_unicos'][$keyPed] = true;
                $gruposDivergencias[$idCliKey]['qtd_pedidos']++;
            }
            $local = (float)$div['VALOR_PAGO_LOCAL']; $bling = (float)$div['VALOR_PAGO_BLING']; $diferenca = abs($local - $bling);
            $totalDivergencia += $diferenca;
            $gruposDivergencias[$idCliKey]['total_diferenca'] += $diferenca;
            $gruposDivergencias[$idCliKey]['total_local'] += $local;
            $gruposDivergencias[$idCliKey]['total_bling'] += $bling;
        }
        $temDivergencias = !empty($gruposDivergencias);
    ?>
    <!-- Sub-abas das Baixas: Todas / Divergentes -->
    <div class="cr-sub-tabs">
        <div class="cr-sub-tabs-group">
            <button id="btn-baixas-todas" class="sub-tab-btn active" onclick="setBaixasFiltro('todas')">
                Todas <span class="tab-count"><?= count($todasBaixas ?? []) ?></span>
            </button>
            <button id="btn-baixas-divergentes" class="sub-tab-btn <?= $temDivergencias ? 'sub-tab-warning' : '' ?>" onclick="setBaixasFiltro('divergentes')">
                ⚠ Divergentes <span class="tab-count"><?= count($gruposDivergencias) ?></span>
            </button>
        </div>
        <?php if ($temDivergencias): ?>
        <div class="cr-total-inadimplente" style="color: #ef4444;">
            Total de Divergências:&nbsp;<span>R$ <?= number_format($totalDivergencia, 2, ',', '.') ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- PAINEL: Todas as Baixas -->
    <div id="painel-baixas-todas" class="card">
        <div class="table-responsive">
            <table class="cr-table" id="table-baixas">
                <thead>
                    <tr>
                        <th>Última Baixa</th>
                        <th>Cliente</th>
                        <th>Qtd. Baixas</th>
                        <th class="valor-col">Total Baixado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($todasBaixas)): ?>
                        <tr><td colspan="4" class="text-center">Nenhuma baixa manual encontrada.</td></tr>
                    <?php else: ?>
                        <?php foreach($todasBaixas as $b): ?>
                            <tr onclick="abrirModalBaixas(<?= $b->ID_CLIENTE ?>)" style="cursor: pointer;" class="hover-row">
                                <td><?= date('d/m/Y H:i', strtotime($b->ULTIMA_BAIXA)) ?></td>
                                <td><?= htmlspecialchars($b->NOME_CONTATO) ?></td>
                                <td><?= $b->QTD_BAIXAS ?></td>
                                <td class="valor-col">R$ <?= number_format($b->TOTAL_BAIXADO, 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAINEL: Apenas Divergentes -->
    <div id="painel-baixas-divergentes" class="card" style="display: none;">
        <div class="table-responsive">
            <table class="cr-table" id="table-divergencias">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Qtd. Pedidos</th>
                        <th class="valor-col" style="color: #64748b;">Pago Local (Hydra)</th>
                        <th class="valor-col" style="color: #059669;">Pago API (Bling)</th>
                        <th class="valor-col">Divergência</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($gruposDivergencias)): ?>
                        <tr><td colspan="5" class="text-center" style="padding: 2rem; color: #64748b;">Nenhuma divergência encontrada. Tudo sincronizado! ✓</td></tr>
                    <?php else: ?>
                        <?php foreach ($gruposDivergencias as $grupo): ?>
                        <tr onclick="abrirModalDivergencias('<?= $grupo['id'] ?>')" style="cursor:pointer;" class="hover-row">
                            <td><?= htmlspecialchars($grupo['nomeCli']) ?></td>
                            <td><?= $grupo['qtd_pedidos'] ?></td>
                            <td class="valor-col">R$ <?= number_format($grupo['total_local'], 2, ',', '.') ?></td>
                            <td class="valor-col">R$ <?= number_format($grupo['total_bling'], 2, ',', '.') ?></td>
                            <td class="valor-col" style="color: #ef4444; font-weight: 600;">R$ <?= number_format($grupo['total_diferenca'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($gruposDivergencias)): ?>
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: bold; border-top: 2px solid #e2e8f0;">
                        <td colspan="4" style="text-align: right; padding-right: 1rem; color: #475569;">Somatório Total das Diferenças:</td>
                        <td class="valor-col" style="color: #ef4444; font-weight: 700;">R$ <?= number_format($totalDivergencia, 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
    @endif

</div>

<!-- MODAL DE DETALHES -->
<div id="modal-detalhes" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay"></div>
    <div class="cr-modal-container">
        <div class="cr-modal-header">
            <h3 class="cr-modal-title" id="modal-detalhes-title">Detalhes</h3>
            <button class="cr-modal-close" id="modal-detalhes-close">&times;</button>
        </div>
        <div class="cr-modal-body" id="modal-detalhes-body">
            <!-- Conteúdo carregado via JS -->
        </div>
    </div>
</div>

<!-- MODAL DE SELEÇÃO DE CLIENTES PARA COBRANÇA -->
<div id="modal-cobranca-clientes" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay" id="modal-cobranca-overlay"></div>
    <div class="cr-modal-container">
        <div class="cr-modal-header">
            <h3 class="cr-modal-title" id="modal-cobranca-title">Assumir Cobrança</h3>
            <button class="cr-modal-close" id="modal-cobranca-close">&times;</button>
        </div>
        <div class="cr-modal-body" id="modal-cobranca-body">
            <p class="modal-cobranca-body-text">Selecione os clientes do <strong><span id="modal-cobranca-tipo-texto"></span></strong> que você quer cobrar:</p>
            <div id="modal-cobranca-loading" class="modal-cobranca-loading">Carregando clientes...</div>
            <div id="clientes-list-container" class="modal-cobranca-list">
            </div>
            <div class="modal-cobranca-footer">
                <label class="modal-cobranca-check-label">
                    <input type="checkbox" id="check-all-clientes" checked> Selecionar Todos
                </label>
                <button id="btn-confirmar-cobranca" class="btn-sync btn-modal-cobranca-confirm">
                    <x-icons.check width="14" height="14" />
                    Confirmar e Assumir
                </button>
            </div>
        </div>
    </div>
</div>

@include('components.modal_baixa_manual')

<!-- MODAL DE CONFIRMAÇÃO DO EXIBIR ATÉ -->
<div id="modal-exibir-ate" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay"></div>
    <div class="cr-modal-container modal-container-sm">
        <div class="cr-modal-header">
            <h3 class="cr-modal-title">Confirmar Alteração</h3>
            <button class="cr-modal-close" onclick="fecharModalExibirAte()">&times;</button>
        </div>
        <div class="cr-modal-body modal-body-padded">
            <p id="msg-exibir-ate" class="modal-msg"></p>
            <div class="modal-actions">
                <button class="btn-modal-cancel" onclick="fecharModalExibirAte()">
                    Cancelar
                </button>
                <button class="btn-modal-confirm" onclick="confirmarExibirAte()">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Corrigir Baixa (Divergências) -->
<div id="modal-corrigir-baixa" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay"></div>
    <div class="cr-modal-container modal-container-md" style="max-width: 400px; padding: 20px;">
        <div class="cr-modal-header">
            <h3 class="cr-modal-title">Corrigir Baixa Local</h3>
            <button class="cr-modal-close" onclick="fecharModalCorrigirBaixa()">&times;</button>
        </div>
        <div class="cr-modal-body">
            <p>Digite o <strong>novo valor total</strong> que deveria constar como pago localmente para este pedido.</p>
            <input type="hidden" id="corrigir-id-pedido" value="">
            <div style="margin-top: 15px;">
                <label for="corrigir-novo-valor" style="font-weight:600; display:block; margin-bottom:5px;">Novo Valor (R$)</label>
                <input type="number" id="corrigir-novo-valor" step="0.01" min="0" class="cr-input" style="width:100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px;">
            </div>
            <div class="modal-actions-between" style="margin-top: 20px; justify-content: flex-end;">
                <div class="modal-actions">
                    <button class="btn-modal-cancel" onclick="fecharModalCorrigirBaixa()">Cancelar</button>
                    <button class="btn-modal-confirm-blue" onclick="confirmarCorrecaoBaixa()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Salvar Correção
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/baixa_manual.js') }}?v=<?= time() ?>"></script>
<script src="{{ asset('js/contas_receber.js') }}?v=<?= time() ?>"></script>

<script>
const BASE_URL = "{{ url('/') }}";

// Toggle sub-painéis das Baixas
function setBaixasFiltro(filtro) {
    const btnTodas = document.getElementById('btn-baixas-todas');
    const btnDiv   = document.getElementById('btn-baixas-divergentes');
    const painelTodas = document.getElementById('painel-baixas-todas');
    const painelDiv   = document.getElementById('painel-baixas-divergentes');
    if (!btnTodas) return;
    if (filtro === 'todas') {
        btnTodas.classList.add('active'); btnDiv.classList.remove('active');
        painelTodas.style.display = ''; painelDiv.style.display = 'none';
    } else {
        btnDiv.classList.add('active'); btnTodas.classList.remove('active');
        painelDiv.style.display = ''; painelTodas.style.display = 'none';
    }
}

// Modal divergências — abre detalhes por cliente
function abrirModalDivergencias(idCliente) {
    const modal = document.getElementById('modal-detalhes');
    if (!modal) return;
    const title = document.getElementById('modal-detalhes-title');
    const body  = document.getElementById('modal-detalhes-body');
    title.innerText = 'Divergências do Cliente';
    body.innerHTML  = '<div class="text-center" style="padding: 20px;">Carregando divergências...</div>';
    modal.style.display = 'flex';
    fetch(`${BASE_URL}/divergencias/api-divergencias-cliente?id=${idCliente}`)
        .then(res => res.json())
        .then(data => {
            if (data.html) { body.innerHTML = data.html; }
            else { body.innerHTML = '<div class="text-center" style="padding:20px;color:red;">' + (data.error || 'Erro ao carregar') + '</div>'; }
        })
        .catch(() => { body.innerHTML = '<div class="text-center" style="padding:20px;color:red;">Erro na requisição.</div>'; });
}

// Corrigir baixa local
function abrirModalCorrigir(idPedido, atualPago) {
    document.getElementById('corrigir-id-pedido').value = idPedido;
    document.getElementById('corrigir-novo-valor').value = atualPago;
    document.getElementById('modal-corrigir-baixa').style.display = 'flex';
}
function fecharModalCorrigirBaixa() {
    document.getElementById('modal-corrigir-baixa').style.display = 'none';
}
function confirmarCorrecaoBaixa() {
    const idPedido  = document.getElementById('corrigir-id-pedido').value;
    const novoValor = document.getElementById('corrigir-novo-valor').value;
    if (!idPedido || novoValor === '') { alert('Por favor, informe o novo valor.'); return; }
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch(BASE_URL + '/divergencias/corrigir-baixa', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ id_pedido: idPedido, novo_valor: novoValor })
    })
    .then(r => r.json())
    .then(res => { if (res.success) { window.location.reload(); } else { alert(res.error || 'Erro ao corrigir a baixa.'); } })
    .catch(() => alert('Erro de comunicação.'));
}

// Estornar baixas de um pedido inteiro
function estornarBaixaPedido(idPedido) {
    if (!confirm('Deseja realmente estornar todas as baixas locais deste pedido?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch(BASE_URL + '/divergencias/estornar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ id_pedido: idPedido })
    })
    .then(r => r.json())
    .then(res => { if (res.success) { window.location.reload(); } else { alert(res.error || 'Erro ao estornar.'); } })
    .catch(() => alert('Erro de comunicação.'));
}

// Estornar detalhe individual
function estornarBaixa(idDetalhe) {
    if (!confirm('Deseja realmente estornar esta baixa local?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch(BASE_URL + '/baixas/estornar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ id_detalhe: idDetalhe })
    })
    .then(r => r.json())
    .then(data => { if (data.success) { alert('Baixa estornada!'); location.reload(); } else { alert('Erro: ' + (data.error || 'Falha ao estornar.')); } })
    .catch(() => alert('Erro de comunicação.'));
}

// Editar baixa inline
function editarBaixa(idDetalhe) {
    document.getElementById('valor-display-' + idDetalhe).style.display = 'none';
    document.getElementById('input-baixa-' + idDetalhe).style.display = 'inline-block';
    document.getElementById('input-baixa-' + idDetalhe).focus();
    document.getElementById('btn-edit-' + idDetalhe).style.display = 'none';
    document.getElementById('btn-save-' + idDetalhe).style.display = 'inline-block';
    document.getElementById('btn-cancel-' + idDetalhe).style.display = 'inline-block';
}
function cancelarEdicaoBaixa(idDetalhe) {
    document.getElementById('valor-display-' + idDetalhe).style.display = 'inline-block';
    document.getElementById('input-baixa-' + idDetalhe).style.display = 'none';
    document.getElementById('btn-edit-' + idDetalhe).style.display = 'inline-block';
    document.getElementById('btn-save-' + idDetalhe).style.display = 'none';
    document.getElementById('btn-cancel-' + idDetalhe).style.display = 'none';
}
function salvarBaixa(idDetalhe) {
    const novoValor = parseFloat(document.getElementById('input-baixa-' + idDetalhe).value);
    if (isNaN(novoValor) || novoValor < 0) { alert('Valor inválido.'); return; }
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch(BASE_URL + '/baixas/editar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ id_detalhe: idDetalhe, valor: novoValor })
    })
    .then(r => r.json())
    .then(data => { if (data.success) { location.reload(); } else { alert('Erro: ' + (data.error || 'Falha ao editar.')); } })
    .catch(() => alert('Erro de comunicação.'));
}
</script>

@include('components.templates_js')

@endsection
