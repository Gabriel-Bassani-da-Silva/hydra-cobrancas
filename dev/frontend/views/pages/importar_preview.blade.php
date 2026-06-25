@extends('layouts.app')

@section('title', 'Pré-visualização da Importação')
@section('body_class', 'contatos-page')

@section('content')
<?php
$title = "Pré-visualização da Importação";
$body_class = "contatos-page";
$show_header = true;

// Conta estatísticas
$total = count($linhasValidadas);
$prontos = 0;
$erros = 0;

foreach ($linhasValidadas as $linha) {
    if ($linha['status'] === 'ok') $prontos++;
    else $erros++;
}


?>
<div class="contatos-wrapper">
    <div class="contatos-header-actions">
        <div class="header-title-section">
            <h2>Relatório de Importação</h2>
            <p>Verifique os dados abaixo antes de confirmar a importação para o banco de dados.</p>
        </div>
        <div class="actions-buttons">
            <a href="{{ route('importar-contatos-page') }}" class="btn-cancel" title="Cancelar e voltar">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Cancelar
            </a>
            <?php if ($prontos > 0): ?>
            <form action="{{ route('confirmar-importacao') }}" method="POST" class="form-no-margin">
                <button type="submit" class="btn-sync btn-sync-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Confirmar Importação (<?= $prontos ?>)
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="import-summary-cards">
        <div class="card import-summary-card import-summary-card--blue">
            <h3><?= $total ?></h3>
            <p>Total de Linhas</p>
        </div>
        <div class="card import-summary-card import-summary-card--green">
            <h3><?= $prontos ?></h3>
            <p>Prontos para Processar</p>
        </div>
        <div class="card import-summary-card import-summary-card--red">
            <h3><?= $erros ?></h3>
            <p>Com Erro (Ignorados)</p>
        </div>
    </div>

    <div class="card card-overflow">
        <div class="table-scroll">
            <table class="import-preview-table">
                <thead>
                    <tr>
                        <th>Nome da Planilha</th>
                        <th>Documento</th>
                        <th>Ação no Contato</th>
                        <th>Ação no Telefone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($linhasValidadas)): ?>
                        <tr>
                            <td colspan="4" class="text-empty">Nenhuma linha válida encontrada na planilha.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($linhasValidadas as $linha): ?>
                            <?php $isErro = ($linha['status'] === 'erro'); ?>
                            <tr class="<?= $isErro ? 'import-preview-row-error' : '' ?>">
                                <td>
                                    <?php if ($isErro): ?>
                                        <span class="import-icon-error" title="Erro">⚠️</span>
                                    <?php else: ?>
                                        <span class="import-icon-ok" title="Pronto">✅</span>
                                    @endif
                                    <strong><?= htmlspecialchars($linha['nome'] ?? '---') ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($linha['cpf_cnpj'] ?? '---') ?>
                                </td>
                                <td>
                                    <?php if ($isErro): ?>
                                        <span class="import-text-error"><?= htmlspecialchars($linha['mensagem'] ?? 'Erro') ?></span>
                                    <?php else: ?>
                                        <span class="import-text-action"><?= htmlspecialchars($linha['acao_contato']) ?></span>
                                    @endif
                                </td>
                                <td>
                                    <?php if (!$isErro): ?>
                                        <?php if (strpos($linha['acao_telefone'], 'Atualizar') !== false): ?>
                                            <span class="import-text-update"><?= htmlspecialchars($linha['acao_telefone']) ?></span>
                                        <?php else: ?>
                                            <span class="import-text-add"><?= htmlspecialchars($linha['acao_telefone']) ?></span>
                                        @endif
                                        <div class="import-text-phone-detail"><?= htmlspecialchars($linha['telefone'] ?? '') ?></div>
                                    @endif
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>


@endsection
