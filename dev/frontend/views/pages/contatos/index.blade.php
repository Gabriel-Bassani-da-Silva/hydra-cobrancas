@extends('layouts.app')

@section('title', 'Contatos — Telefones')
@section('body_class', 'contatos-page')

@section('content')
<?php
$title = "Contatos — Telefones";
$body_class = "contatos-page";
$show_header = true;
?>
<div class="contatos-wrapper">

    <!-- Barra de Ações do Bling -->
    <div class="contatos-header-actions">
        <div class="header-title-section">
            <h2>Gerenciamento de Contatos</h2>
            <p>Gerencie telefones, contatos financeiros e sincronize dados com o Bling</p>
        </div>
        <div class="actions-buttons">
            <a href="{{ route('importar-contatos-page') }}" id="btn-importar-contatos" class="btn-sync" title="Importar contatos">
                <x-icons.upload width="16" height="16" />
                Importar Contatos
            </a>
            <a href="{{ route('sincronizar-contatos-bling') }}?aba=<?= $aba ?>" class="btn-sync" title="Atualizar contatos no Bling">
                <x-icons.refresh width="16" height="16" />
                Sincronizar Contatos
            </a>
            <a href="{{ route('sincronizar-vendedores-bling') }}?aba=<?= $aba ?>" class="btn-sync secondary" title="Atualizar vendedores no Bling">
                <x-icons.user width="16" height="16" />
                Sincronizar Vendedores
            </a>
            
            <div id="filtro-inadimplentes-toggle" class="filtro-cobranca-toggle" style="margin-left: 10px;">
                <button class="filtro-cob-btn <?= request('inadimplentes') != '1' ? 'filtro-cob-btn--active' : '' ?>" onclick="setInadimplentesFilter('0')">
                    Mostrar Todos
                </button>
                <button class="filtro-cob-btn <?= request('inadimplentes') == '1' ? 'filtro-cob-btn--active' : '' ?>" onclick="setInadimplentesFilter('1')">
                    @if(request('inadimplentes') == '1') <x-icons.check-1 width="13" height="13" /> @endif
                    Apenas Inadimplentes
                </button>
            </div>
        </div>
    </div>

    <!-- Mensagens Flash -->
    @if(session('flash_msg'))
        <div class="flash-message">
            <span>{{ session('flash_msg') }}</span>
            <button class="flash-close" onclick="this.parentElement.remove()">✕</button>
        </div>
    @endif

    <!-- Abas -->
    <div class="tabs">
        <?php 
            function getTabUrl($tabName) {
                $params = array_merge(request()->query(), ['aba' => $tabName]);
                return route('contatos-page') . '?' . http_build_query($params);
            }
        ?>
        <a href="<?= getTabUrl('clientes') ?>" class="tab <?= $aba === 'clientes' ? 'active' : '' ?>">
            Clientes <span class="tab-count"><?= count($clientes) ?></span>
        </a>
        <a href="<?= getTabUrl('representantes') ?>" class="tab <?= $aba === 'representantes' ? 'active' : '' ?>">
            Representantes <span class="tab-count"><?= count($representantes) ?></span>
        </a>
        <a href="<?= getTabUrl('sem-telefone') ?>" class="tab <?= $aba === 'sem-telefone' ? 'active' : '' ?>">
            Sem Telefone <span class="tab-count"><?= count($semTelefone) ?></span>
        </a>
        <a href="<?= getTabUrl('financeiros') ?>" class="tab <?= $aba === 'financeiros' ? 'active' : '' ?>">
            Contatos Financeiros <span class="tab-count"><?= count($contatosFinanceiros) ?></span>
        </a>
    </div>

    <!-- ═══ CONTEÚDO DAS ABAS ═══ -->
    
    <?php if ($aba === 'clientes'): ?>
        @include('pages.contatos._tab_clientes')
    @endif

    <?php if ($aba === 'representantes'): ?>
        @include('pages.contatos._tab_representantes')
    @endif

    <?php if ($aba === 'sem-telefone'): ?>
        @include('pages.contatos._tab_sem_telefone')
    @endif

    <?php if ($aba === 'financeiros'): ?>
        @include('pages.contatos._tab_financeiros')
    @endif

</div>

@include('pages.contatos._modais')

<script>
    window.allClientes = <?= json_encode($clientesTodos) ?>;
</script>
<script src="{{ asset('js/contatos.js') }}?v=<?= time() ?>"></script>
@endsection
