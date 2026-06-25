@extends('layouts.app')

@section('title', 'Cobranças')
@section('body_class', 'cobrancas-page')

@section('content')
<?php
$title = "Cobranças";
$body_class = "cobrancas-page";
$show_header = true;



$grupo = $_GET['grupo'] ?? 'padrao';
?>

<div class="cr-wrapper" data-initial-state='<?= htmlspecialchars(json_encode([
    "aba" => $aba ?? "clientes",
    "grupo" => $grupo ?? "padrao",
    "clientes" => $resumoClientes ?? [],
    "representantes" => $resumoRepresentantes ?? [],
    "financeiros" => $resumoContatosFinanceiros ?? [],
    "cobrancasAtivas" => $cobrancasAtivas ?? ["clientes"=>[], "financeiros"=>[], "representantes"=>[]]
]), ENT_QUOTES, "UTF-8") ?>'>
    <!-- Barra de Ações -->
    <div class="cr-header-actions">
        <div class="header-title-section">
            <h2>Gestão de Cobranças</h2>
            <p>Selecione um agrupamento para assumir a cobrança</p>
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
    <div class="cr-search-bar">
        <div class="search-input-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18" class="search-icon">
                <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
            <input type="text" id="cr-search" placeholder="Filtrar tabela por nome, documento ou valor..." autocomplete="off">
        </div>
    </div>

    <!-- Abas Principais -->
    <div class="tabs">
        <a href="{{ route('cobranca-page') }}?aba=clientes" class="tab <?= $aba === 'clientes' ? 'active' : '' ?>">
            Clientes <span class="tab-count"><?= count($resumoClientes ?? []) ?></span>
        </a>
        <a href="{{ route('cobranca-page') }}?aba=representantes" class="tab <?= $aba === 'representantes' ? 'active' : '' ?>">
            Representantes <span class="tab-count"><?= count($resumoRepresentantes ?? []) ?></span>
        </a>
    </div>

    <!-- Sub-Abas para Clientes e Representantes -->
    <?php if ($aba === 'clientes' || $aba === 'representantes'): ?>
    <div class="cr-sub-tabs">
        <a href="{{ route('cobranca-page') }}?aba=<?= $aba ?>&grupo=padrao" class="sub-tab-btn <?= $grupo === 'padrao' ? 'active' : '' ?>">
            <?= ucfirst($aba) ?>
        </a>
        <a href="{{ route('cobranca-page') }}?aba=<?= $aba ?>&grupo=financeiro" class="sub-tab-btn <?= $grupo === 'financeiro' ? 'active' : '' ?>">
            Contatos Financeiros
        </a>
    </div>
    @endif

    <!-- ABA: CLIENTES / REPRESENTANTES (PADRÃO) -->
    <?php if (($aba === 'clientes' || $aba === 'representantes') && $grupo === 'padrao'): ?>
    <div class="card">
        <div class="table-responsive">
            <table class="cr-table" id="table-padrao">
                <thead>
                    <tr>
                        <th class="expand-col"></th>
                        <th data-sort="az" class="sortable" style="cursor:pointer; user-select:none;"><?= $aba === 'clientes' ? 'Cliente' : 'Representante' ?> <span class="sort-icon"></span></th>
                        <?php if ($aba === 'representantes'): ?><th>Clientes</th>@endif
                        <th data-sort="qtd" class="center-col sortable" style="cursor:pointer; user-select:none;">Qtd. Pedidos <span class="sort-icon"></span></th>
                        <th data-sort="valor" class="valor-col sortable" style="cursor:pointer; user-select:none;">Total em Aberto <span class="sort-icon"></span></th>
                        <th data-sort="venc" class="date-col sortable" style="cursor:pointer; user-select:none;">Vencimento Mais Antigo <span class="sort-icon"></span></th>
                        <th>Status Cobrança</th>
                        <th data-sort="tel" class="center-col sortable" style="cursor:pointer; user-select:none;">Telefone <span class="sort-icon"></span></th>
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
    <?php if (($aba === 'clientes' || $aba === 'representantes') && $grupo === 'financeiro'): ?>
    <div class="card">
        <div class="table-responsive">
            <table class="cr-table" id="table-financeiro">
                <thead>
                    <tr>
                        <th class="expand-col"></th>
                        <th data-sort="az" class="sortable" style="cursor:pointer; user-select:none;">Contato Financeiro <span class="sort-icon"></span></th>
                        <th data-sort="qtd" class="center-col sortable" style="cursor:pointer; user-select:none;">Qtd. Pedidos <span class="sort-icon"></span></th>
                        <th data-sort="valor" class="valor-col sortable" style="cursor:pointer; user-select:none;">Total em Aberto <span class="sort-icon"></span></th>
                        <th data-sort="venc" class="date-col sortable" style="cursor:pointer; user-select:none;">Vencimento Mais Antigo <span class="sort-icon"></span></th>
                        <th>Status Cobrança</th>
                        <th data-sort="tel" class="center-col sortable" style="cursor:pointer; user-select:none;">Telefone <span class="sort-icon"></span></th>
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
            <div id="modal-cobranca-loading" class="modal-cobranca-loading">
                Carregando clientes...
            </div>
            <div class="clientes-list-container" id="clientes-list-container">
                <!-- Checkboxes injetados via JS -->
            </div>
            <div class="modal-cobranca-footer">
                <label class="check-all-label">
                    <input type="checkbox" id="check-all-clientes" checked> Selecionar Todos
                </label>
                <button id="btn-confirmar-cobranca" class="btn-cobrar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Confirmar e Assumir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE DETALHES -->
<div id="modal-detalhes" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay" id="modal-detalhes-overlay"></div>
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

<!-- DATA INJECTION -->

<script src="{{ asset('js/cobrancas.js') }}?v=<?= time() ?>"></script>

@include('components.templates_js')

@endsection
