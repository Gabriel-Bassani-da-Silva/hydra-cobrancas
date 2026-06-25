@extends('layouts.app')

@section('title', 'Contas a Receber')
@section('body_class', 'contas-receber-page')

@section('content')
<?php
$title = "Contas a Receber";
$body_class = "contas-receber-page";
$show_header = true;



$isPagos = (isset($_GET['status']) && $_GET['status'] === 'pagos');
?>

<div class="cr-wrapper" data-initial-state='<?= htmlspecialchars(json_encode([
    "aba" => $aba ?? "clientes",
    "grupo" => $grupo ?? "padrao",
    "status" => $_GET["status"] ?? "pendentes",
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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                Sincronizar por ID
            </button>
            <a href="{{ route('sincronizar-contas-receber') }}?aba=<?= $aba ?>"
               id="btn-sincronizar"
               class="btn-sync"
               title="Baixar alterações recentes (Rápido)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                Sinc. Rápida
            </a>
            <a href="{{ route('sincronizar-contas-receber') }}?full=1&aba=<?= $aba ?>"
               id="btn-sincronizar-full"
               class="btn-sync btn-warning"
               title="Verificação Completa (Varre todas as contas para encontrar apagadas e pagas)"
               onclick="return confirm('A Verificação Completa analisa todas as contas em aberto no Bling. Isso pode demorar um pouco mais. Deseja continuar?');">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v6h6"/></svg>
                Verificação Completa
            </a>
            <a href="{{ route('vincular-reps-contas') }}?aba=<?= $aba ?>"
               class="btn-sync btn-secondary"
               title="Busca os vendedores no Bling para os pedidos que estão sem vendedor localmente"
               onclick="return confirm('Isso buscará detalhes de pedidos sem representante no Bling. Pode demorar alguns minutos. Deseja continuar?');">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="12" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><polyline points="16 11 18 13 22 9"/></svg>
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
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18" class="search-icon">
                <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>
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
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=pendentes" class="sub-tab-btn <?= (!isset($_GET['status']) || $_GET['status'] === 'pendentes') ? 'active' : '' ?>">
                Inadimplentes
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=antecipado" class="sub-tab-btn <?= (isset($_GET['status']) && $_GET['status'] === 'antecipado') ? 'active' : '' ?>">
                Antecipados
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=cheque" class="sub-tab-btn <?= (isset($_GET['status']) && $_GET['status'] === 'cheque') ? 'active' : '' ?>">
                Cheques
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=todos_pendentes" class="sub-tab-btn <?= (isset($_GET['status']) && $_GET['status'] === 'todos_pendentes') ? 'active' : '' ?>">
                Todos os Pendentes
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=pagos" class="sub-tab-btn <?= (isset($_GET['status']) && $_GET['status'] === 'pagos') ? 'active' : '' ?>">
                Pagos
            </a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=todos" class="sub-tab-btn <?= (isset($_GET['status']) && $_GET['status'] === 'todos') ? 'active' : '' ?>">
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Confirmar e Assumir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE BAIXA MANUAL -->
<div id="modal-baixa-manual" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay"></div>
    <div class="cr-modal-container modal-container-md">
        <div class="cr-modal-header">
            <h3 class="cr-modal-title">Registrar Baixa Manual</h3>
            <button class="cr-modal-close" onclick="fecharModalBaixa()">&times;</button>
        </div>
        <div class="cr-modal-body">
            <div id="baixa-parcelas-container" class="baixa-container">
                <!-- Preenchido via JS -->
            </div>

            <div class="modal-actions-between">
                <div class="modal-actions" style="gap:15px;">
                    <div>
                        <span class="baixa-summary-label">Total a Baixar</span>
                        <strong id="baixa-total-display" class="baixa-summary-value">R$ 0,00</strong>
                    </div>
                    <div class="baixa-divider"></div>
                    <button class="btn-modal-select-all" onclick="baixarTodasParcelas()">
                        Baixar Todos
                    </button>
                </div>
                <div class="modal-actions">
                    <button class="btn-modal-cancel" onclick="fecharModalBaixa()">
                        Cancelar
                    </button>
                    <button class="btn-modal-confirm-blue" onclick="confirmarBaixa()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path d="M5 12l5 5l10 -10"></path></svg>
                        Confirmar Baixa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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

<script src="{{ asset('js/contas_receber.js') }}?v=<?= time() ?>"></script>

@include('components.templates_js')

@endsection
