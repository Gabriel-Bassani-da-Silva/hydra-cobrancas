@extends('layouts.app')

@section('title', 'Divergência Bling')
@section('body_class', 'divergencia-page')

@section('content')
<?php
$title = "Divergência Bling";
$body_class = "divergencia-page";
$show_header = true;

// Helper para formatar valores monetários
$formatMoney = function($value) {
    return 'R$ ' . number_format($value ?? 0, 2, ',', '.');
};


?>
<div class="cr-wrapper">
    <div class="cr-header-actions">
        <div class="header-title-section">
            <h2>Divergência Bling</h2>
            <p>Lista de pedidos com baixa automática que requerem conferência manual.</p>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="cr-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Vencimento</th>
                        <th class="valor-col">Total Pedido</th>
                        <th class="valor-col" style="color: #64748b;">Pago Local (Hydra)</th>
                        <th class="valor-col" style="color: #059669;">Pago API (Bling)</th>
                        <th class="valor-col">Divergência</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalDivergencia = 0;
                    if (empty($divergencias)): 
                    ?>
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 2rem; color: #64748b;">Nenhuma divergência encontrada. Tudo sincronizado!</td>
                        </tr>
                    <?php 
                    else: 
                        foreach ($divergencias as $div): 
                            $local = (float)$div['VALOR_PAGO_LOCAL'];
                            $bling = (float)$div['VALOR_PAGO_BLING'];
                            $diferenca = abs($local - $bling);
                            $totalDivergencia += $diferenca;
                            
                            $isLocalMaior = $local > $bling;
                            $diffColor = $isLocalMaior ? '#ef4444' : '#eab308'; // Red se local > bling, Amarelo se bling > local
                            $diffSignal = $isLocalMaior ? 'Local + ' : 'Bling + ';
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($div['NUM_PEDIDO'] && $div['NUM_PEDIDO'] !== '—' ? $div['NUM_PEDIDO'] : $div['ID_PEDIDO']) ?></strong></td>
                        <td class="nome-col"><?= htmlspecialchars($div['NOME_CLIENTE']) ?></td>
                        <td class="date-col"><?= date('d/m/Y', strtotime($div['DATA_VENCIMENTO'])) ?></td>
                        <td class="valor-col font-semibold"><?= $formatMoney($div['TOTAL_PEDIDO']) ?></td>
                        <td class="valor-col" style="color: #64748b; font-weight: 500;"><?= $formatMoney($local) ?></td>
                        <td class="valor-col" style="color: #059669; font-weight: 600;"><?= $formatMoney($bling) ?></td>
                        <td class="valor-col" style="color: <?= $diffColor ?>; font-weight: 600;"><?= $diffSignal . $formatMoney($diferenca) ?></td>
                        <td class="text-center">
                            <button onclick="abrirModalBaixa(<?= $div['ID_PEDIDO'] ?>)" title="Corrigir Baixa" class="btn-action-icon" style="border:none; background:transparent; cursor:pointer;">
                                <x-icons.check width="16" height="16" />
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endforeach; 
                    endif; 
                    ?>
                </tbody>
                <?php if (!empty($divergencias)): ?>
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: bold; border-top: 2px solid #e2e8f0;">
                        <td colspan="6" style="text-align: right; padding-right: 1rem; color: #475569;">Somatório Total das Diferenças:</td>
                        <td class="valor-col" style="color: #ef4444; font-weight: 700; font-size: 1.1em;"><?= $formatMoney($totalDivergencia) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@include('components.modal_baixa_manual')

<script>const BASE_URL = "{{ url('/') }}";</script>
<script src="{{ asset('js/baixa_manual.js') }}?v=<?= time() ?>"></script>

@endsection
