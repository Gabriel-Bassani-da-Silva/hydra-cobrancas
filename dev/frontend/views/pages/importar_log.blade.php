@extends('layouts.app')

@section('title', 'Log de Importação')
@section('body_class', 'contatos-page')

@section('content')
<?php
$title = "Log de Importação";
$body_class = "contatos-page";
$show_header = true;

$totalLinhas = count($importLog['log']);
$totalSucesso = $importLog['sucesso'];
$totalErros = $importLog['erros'];
$dataImportacao = $importLog['data'];


?>
<div class="contatos-wrapper">
    <div class="contatos-header-actions">
        <div class="header-title-section">
            <h2>Log de Importação</h2>
            <p>Resultado da importação realizada em <strong><?= htmlspecialchars($dataImportacao) ?></strong></p>
        </div>
        <div class="actions-buttons">
            <a href="{{ route('contatos-page') }}" class="btn-cancel" title="Ir para Contatos">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Ir para Contatos
            </a>
            <a href="{{ route('importar-contatos-page') }}" class="btn-sync secondary" title="Nova importação">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Nova Importação
            </a>
        </div>
    </div>

    <!-- Resumo -->
    <div class="log-summary">
        <div class="log-card log-card--blue">
            <h3><?= $totalLinhas ?></h3>
            <p>Total Processadas</p>
        </div>
        <div class="log-card log-card--green">
            <h3><?= $totalSucesso ?></h3>
            <p>Sucesso</p>
        </div>
        <div class="log-card log-card--red">
            <h3><?= $totalErros ?></h3>
            <p>Erros</p>
        </div>
    </div>

    <!-- Log detalhado -->
    <div class="log-container">
        <?php foreach ($importLog['log'] as $entry): ?>
            <?php 
                $isErro = ($entry['resultado'] === 'erro');
                $icon = $isErro ? '✘' : '✔';
            ?>
            <div class="log-entry <?= $isErro ? 'log-entry--error' : 'log-entry--success' ?>">
                <div class="log-entry-header">
                    <div class="log-entry-title">
                        <span class="<?= $isErro ? 'log-entry-icon--error' : 'log-entry-icon--success' ?>"><?= $icon ?></span>
                        <strong class="log-entry-name">
                            Linha <?= $entry['linha'] ?>
                            <?php if (!empty($entry['nome'])): ?>
                                — <?= htmlspecialchars($entry['nome']) ?>
                            @endif
                        </strong>
                    </div>
                    <div class="log-entry-meta">
                        <?php if (!empty($entry['cpf_cnpj'])): ?>
                            <span>Doc: <?= htmlspecialchars($entry['cpf_cnpj']) ?></span>
                        @endif
                        <?php if (!empty($entry['telefone'])): ?>
                            <span>Tel: <?= htmlspecialchars($entry['telefone']) ?></span>
                        @endif
                    </div>
                </div>
                <div class="log-entry-details">
                    <?php foreach ($entry['detalhes'] as $detalhe): ?>
                        <?php
                            $detClass = '';
                            if (strpos($detalhe, '✔') === 0) $detClass = 'import-text-add';
                            elseif (strpos($detalhe, '✘') === 0 || strpos($detalhe, 'Ignorado') !== false) $detClass = 'import-text-error';
                            elseif (strpos($detalhe, '⚠') === 0) $detClass = 'import-text-update';
                            elseif (strpos($detalhe, '—') === 0) $detClass = 'text-muted-sm';
                        ?>
                        <div class="log-detail-line <?= $detClass ?>">
                            <?= htmlspecialchars($detalhe) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>


@endsection
