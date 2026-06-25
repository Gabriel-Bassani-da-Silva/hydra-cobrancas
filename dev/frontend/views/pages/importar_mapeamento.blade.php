@extends('layouts.app')

@section('title', 'Mapear Colunas')
@section('body_class', 'contatos-page scrollable-page')

@section('content')
<style>
.scrollable-page main,
.scrollable-page .contatos-wrapper {
    height: auto !important;
    overflow: visible !important;
}
</style>
<?php
$title = "Mapear Colunas";
$body_class = "contatos-page scrollable-page";
$show_header = true;

$totalColunas = !empty($primeiraLinha) ? count($primeiraLinha) : 0;
$totalLinhas = count($allRows);

// Tenta auto-detectar as colunas pelo nome do cabeçalho
$autoMap = ['nome' => -1, 'cpf_cnpj' => -1, 'telefone' => -1, 'status' => -1];
foreach ($primeiraLinha as $idx => $valor) {
    $v = mb_strtolower(trim($valor));
    if (strpos($v, 'nome') !== false && $autoMap['nome'] === -1) $autoMap['nome'] = $idx;
    elseif ((strpos($v, 'cpf') !== false || strpos($v, 'cnpj') !== false || strpos($v, 'documento') !== false) && $autoMap['cpf_cnpj'] === -1) $autoMap['cpf_cnpj'] = $idx;
    elseif ((strpos($v, 'tel') !== false || strpos($v, 'fone') !== false || strpos($v, 'celular') !== false) && $autoMap['telefone'] === -1) $autoMap['telefone'] = $idx;
    elseif ((strpos($v, 'status') !== false || strpos($v, 'confirmado') !== false || strpos($v, 'tentativa') !== false) && $autoMap['status'] === -1) $autoMap['status'] = $idx;
}


?>
<div class="contatos-wrapper">
    <div class="contatos-header-actions">
        <div class="header-title-section">
            <h2>Mapear Colunas</h2>
            <p>Relacione as colunas da sua planilha com os campos do sistema. Foram detectadas <strong><?= $totalColunas ?></strong> colunas e <strong><?= $totalLinhas ?></strong> linhas.</p>
        </div>
        <div class="actions-buttons">
            <a href="{{ route('importar-contatos-page') }}" class="btn-cancel">
                <x-icons.arrow-left width="16" height="16" />
                Cancelar
            </a>
        </div>
    </div>

    <form action="{{ route('salvar-mapeamento-importacao') }}" method="POST">
        @csrf
        <!-- Opção de cabeçalho -->
        <div class="card card-padded">
            <label class="import-checkbox-label">
                <input type="checkbox" name="ignorar_cabecalho" value="1" checked class="import-checkbox" id="chk-ignorar">
                <span>Ignorar a primeira linha (cabeçalho)</span>
            </label>
        </div>

        <!-- Mapeamento de colunas -->
        <div class="card card-padded-lg">
            <h4 class="mapping-title">Relacionar Colunas</h4>
            <p class="mapping-desc">Selecione qual coluna da sua planilha corresponde a cada campo. Campos não mapeados serão deixados em branco.</p>

            <div class="mapping-grid">
                <?php
                $campos = [
                    ['campo' => 'col_nome',     'label' => 'Nome',      'desc' => 'Nome completo ou Razão Social',  'key' => 'nome'],
                    ['campo' => 'col_cpf_cnpj', 'label' => 'CPF / CNPJ','desc' => 'Documento do contato',           'key' => 'cpf_cnpj'],
                    ['campo' => 'col_telefone',  'label' => 'Telefone',  'desc' => 'Número com DDD',                'key' => 'telefone'],
                    ['campo' => 'col_status',    'label' => 'Status',    'desc' => 'Confirmado ou Tentativa',        'key' => 'status'],
                ];
                foreach ($campos as $c):
                ?>
                <div class="mapping-field">
                    <label class="mapping-field-label">
                        <?= $c['label'] ?>
                    </label>
                    <span class="mapping-field-desc"><?= $c['desc'] ?></span>
                    <select name="<?= $c['campo'] ?>" class="mapping-field-select">
                        <option value="-1">— Não mapear —</option>
                        <?php foreach ($primeiraLinha as $idx => $valor): ?>
                            <option value="<?= $idx ?>" <?= ($autoMap[$c['key']] === $idx) ? 'selected' : '' ?>>
                                Coluna <?= ($idx + 1) ?>: <?= htmlspecialchars(mb_substr(trim($valor), 0, 40)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Preview das primeiras linhas -->
        <div class="card card-overflow" style="margin-bottom:20px;">
            <div class="preview-header">
                <h4>Amostra da Planilha (primeiras <?= count($amostra) ?> linhas)</h4>
            </div>
            <div class="table-scroll">
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <?php for ($i = 0; $i < $totalColunas; $i++): ?>
                                <th>
                                    Coluna <?= ($i + 1) ?>
                                </th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($amostra as $rowIdx => $row): ?>
                            <tr class="<?= $rowIdx === 0 ? 'preview-row-header' : '' ?>">
                                <td class="preview-row-index">
                                    <?= ($rowIdx + 1) ?>
                                    <?= $rowIdx === 0 ? '<span class="preview-header-hint"> (cabeçalho?)</span>' : '' ?>
                                </td>
                                <?php for ($i = 0; $i < $totalColunas; $i++): ?>
                                    <td>
                                        <?= htmlspecialchars(mb_substr(trim($row[$i] ?? ''), 0, 50)) ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Botão de avançar -->
        <div class="text-right">
            <button type="submit" class="btn-sync btn-sync-lg">
                <x-icons.chevron-right width="16" height="16" />
                Avançar para Pré-visualização
            </button>
        </div>
    </form>
</div>

@endsection
