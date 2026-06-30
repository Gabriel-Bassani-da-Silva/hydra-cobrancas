@extends('layouts.app')

@section('title', 'Meu Perfil')
@section('body_class', 'perfil-page')

@section('content')
<?php
$title = "Meu Perfil";
$body_class = "perfil-page";
$show_header = true;

use App\Helpers\FormatHelper;
?>
<div class="cr-wrapper">
    <div class="cr-header-actions">
        <div class="header-title-section">
            <h2>Meu Perfil</h2>
            <p>Acompanhe suas cobranças em andamento.</p>
        </div>
    </div>

    <!-- Barra de Busca -->
    <div class="cr-search-bar">
        <div class="search-input-wrapper">
            <x-icons.search width="18" height="18" class="search-icon" />
            <input type="text" id="cr-search" placeholder="Filtrar tabela por nome, documento ou valor..." autocomplete="off">
        </div>
    </div>

    <!-- Abas Principais -->
    <div class="tabs">
        <a href="{{ route('perfil-page') }}?aba=clientes" class="tab <?= $aba === 'clientes' ? 'active' : '' ?>">
            Clientes <span class="tab-count"><?= count($cobrancasClientes ?? []) ?></span>
        </a>
        <a href="{{ route('perfil-page') }}?aba=representantes" class="tab <?= $aba === 'representantes' ? 'active' : '' ?>">
            Representantes <span class="tab-count"><?= count($cobrancasRepresentantes ?? []) ?></span>
        </a>
        <a href="{{ route('perfil-page') }}?aba=baixas" class="tab <?= $aba === 'baixas' ? 'active' : '' ?>">
            Minhas Baixas <span class="tab-count"><?= $countBaixas ?? count($minhasBaixas ?? []) ?></span>
        </a>
    </div>

    <?php if ($aba === 'baixas'): ?>
        @include('pages.perfil._tab_baixas')
    <?php else: ?>
        @include('pages.perfil._tab_cobrancas')
    <?php endif; ?>
</div>

<!-- MODAL DE DETALHES -->
<div id="modal-detalhes" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay"></div>
    <div class="cr-modal-container cr-modal-container--wide">
        <div class="cr-modal-header">
            <h3 class="cr-modal-title" id="modal-detalhes-title">Pedidos da Cobrança</h3>
            <button class="cr-modal-close" id="modal-detalhes-close">&times;</button>
        </div>
        <div class="cr-modal-body" id="modal-detalhes-body">
            <!-- Conteúdo carregado via JS -->
        </div>
    </div>
</div>

@include('components.modal_baixa_manual')

<script>const BASE_URL = "{{ url('/') }}";</script>
<script src="{{ asset('js/baixa_manual.js') }}?v=<?= time() ?>"></script>
<script src="{{ asset('js/perfil.js') }}?v=<?= time() ?>"></script>

@endsection
