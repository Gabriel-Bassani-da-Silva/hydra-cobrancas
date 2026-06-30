@extends('layouts.app')

@section('title', 'Contas a Receber')
@section('body_class', 'contas-receber-page')

@section('content')
<?php
$isPagos = request('status') === 'pagos';
?>

<div class="cr-wrapper" data-initial-state='<?= htmlspecialchars(json_encode([
    "aba"           => $aba ?? "clientes",
    "grupo"         => $grupo ?? "padrao",
    "status"        => request('status', 'pendentes'),
    "isPagos"       => $isPagos,
    "clientes"      => $resumoClientes ?? [],
    "representantes" => $resumoRepresentantes ?? [],
    "financeiros"   => $resumoContatosFinanceiros ?? [],
    "pedidos"       => $todosPedidos ?? [],
    "cobrancasAtivas" => $cobrancasAtivas ?? ["clientes" => [], "financeiros" => [], "representantes" => []]
]), ENT_QUOTES, "UTF-8") ?>'>

    <!-- Barra de Ações -->
    <div class="cr-header-actions">
        <div class="header-title-section">
            <h2>Contas a Receber</h2>
            <p>Gerencie e sincronize contas a receber do Bling</p>
        </div>
        <div class="actions-buttons">
            <a href="{{ route('importar-baixas-page') }}" class="btn-sync btn-secondary" style="background-color: #107c41; color: #fff; border:none;" title="Importar Baixas via Excel">
                <x-icons.upload width="16" height="16" />
                Importar Excel
            </a>
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
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=pendentes" class="sub-tab-btn <?= (request('status', 'pendentes') === 'pendentes') ? 'active' : '' ?>">Inadimplentes</a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=antecipado" class="sub-tab-btn <?= (request('status') === 'antecipado') ? 'active' : '' ?>">Antecipados</a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=cheque" class="sub-tab-btn <?= (request('status') === 'cheque') ? 'active' : '' ?>">Cheques</a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=todos_pendentes" class="sub-tab-btn <?= (request('status') === 'todos_pendentes') ? 'active' : '' ?>">Todos os Pendentes</a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=pagos" class="sub-tab-btn <?= (request('status') === 'pagos') ? 'active' : '' ?>">Pagos</a>
            <a href="{{ route('contas-receber-page') }}?aba=pedidos&status=todos" class="sub-tab-btn <?= (request('status') === 'todos') ? 'active' : '' ?>">Todos</a>
        </div>
        <div class="cr-total-inadimplente">
            Total Inadimplente:&nbsp;<span>R$ <?= number_format($totalInadimplenteFiltrado, 2, ',', '.') ?></span>
        </div>
    </div>
    @endif

    <!-- CONTEÚDO DAS ABAS -->

    <!-- Aba padrao: Clientes / Representantes / Pedras -->
    <?php if (($aba === 'clientes' || $aba === 'representantes' || $aba === 'pedras') && ($grupo === 'padrao' || $grupo === 'cheques' || $grupo === 'antecipados')): ?>
        @include('pages.contas_receber._tab_padrao')
    @endif

    <!-- Aba financeiro -->
    <?php if (($aba === 'clientes' || $aba === 'representantes' || $aba === 'pedras') && ($grupo === 'financeiro')): ?>
        @include('pages.contas_receber._tab_financeiro')
    @endif

    <!-- Aba pedidos -->
    <?php if ($aba === 'pedidos'): ?>
        @include('pages.contas_receber._tab_pedidos')
    @endif

    <!-- Aba baixas -->
    <?php if ($aba === 'baixas'): ?>
        @include('pages.contas_receber._tab_baixas')
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
            <div id="clientes-list-container" class="modal-cobranca-list"></div>
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
                <button class="btn-modal-cancel" onclick="fecharModalExibirAte()">Cancelar</button>
                <button class="btn-modal-confirm" onclick="confirmarExibirAte()">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Corrigir Baixa (Divergências) -->
<div id="modal-corrigir-baixa" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay"></div>
    <div class="cr-modal-container modal-container-md modal-container-corrigir">
        <div class="cr-modal-header">
            <h3 class="cr-modal-title">Corrigir Baixa Local</h3>
            <button class="cr-modal-close" onclick="fecharModalCorrigirBaixa()">&times;</button>
        </div>
        <div class="cr-modal-body">
            <p>Digite o <strong>novo valor total</strong> que deveria constar como pago localmente para este pedido.</p>
            <input type="hidden" id="corrigir-id-pedido" value="">
            <div class="field-group">
                <label for="corrigir-novo-valor" class="field-label">Novo Valor (R$)</label>
                <input type="number" id="corrigir-novo-valor" step="0.01" min="0" class="cr-input cr-input-full">
            </div>
            <div class="modal-actions-between modal-actions-end">
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
<script src="{{ asset('js/contas_receber_baixas.js') }}?v=<?= time() ?>"></script>

@include('components.templates_js')

@endsection
