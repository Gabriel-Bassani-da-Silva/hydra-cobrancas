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
                        $gruposCli = [];
                        foreach ($divergencias as $div) {
                            $nomeCli = !empty($div['NOME_CLIENTE']) ? $div['NOME_CLIENTE'] : 'Não Informado';
                            $idCliKey = !empty($div['ID_CLIENTE']) ? $div['ID_CLIENTE'] : $nomeCli;
                            if (!isset($gruposCli[$idCliKey])) {
                                $gruposCli[$idCliKey] = [
                                    'nomeCli' => $nomeCli,
                                    'doc' => $div['CPF_CNPJ'] ?? '',
                                    'total' => 0,
                                    'ids' => [],
                                    'pedidos' => []
                                ];
                            }
                            
                            $keyPed = !empty($div['NUM_PEDIDO']) && $div['NUM_PEDIDO'] !== '—' ? $div['NUM_PEDIDO'] : 'SEM_NUM_' . $div['ID_PEDIDO'];
                            
                            if (!isset($gruposCli[$idCliKey]['pedidos'][$keyPed])) {
                                $gruposCli[$idCliKey]['pedidos'][$keyPed] = [
                                    'numPedido' => $div['NUM_PEDIDO'],
                                    'divergencias' => [],
                                    'total_diferenca' => 0,
                                    'ids' => []
                                ];
                            }

                            $local = (float)$div['VALOR_PAGO_LOCAL'];
                            $bling = (float)$div['VALOR_PAGO_BLING'];
                            $diferenca = abs($local - $bling);
                            
                            $div['diferenca_calc'] = $diferenca;
                            $div['local_calc'] = $local;
                            $div['bling_calc'] = $bling;

                            $totalDivergencia += $diferenca;
                            $gruposCli[$idCliKey]['total'] += $diferenca;
                            $gruposCli[$idCliKey]['pedidos'][$keyPed]['total_diferenca'] += $diferenca;

                            $gruposCli[$idCliKey]['pedidos'][$keyPed]['divergencias'][] = $div;
                            $gruposCli[$idCliKey]['pedidos'][$keyPed]['ids'][] = $div['ID_PEDIDO'];
                            $gruposCli[$idCliKey]['ids'][] = $div['ID_PEDIDO'];
                        }

                        $groupIndex = 0;
                        foreach ($gruposCli as $idCliKey => $grupo):
                            $docCli = $grupo['doc'] ? '<br><small class="text-xs">'.htmlspecialchars($grupo['doc']).'</small>' : '';
                            $subGroupIdCli = "div_cli_{$groupIndex}";
                            $expandBtnCli = '<button class="btn-expand" data-toggle-parcelas="'.$subGroupIdCli.'" title="Ver pedidos"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M7 10l5 5 5-5z"/></svg></button>';
                    ?>
                    <tr class="expandable-row" style="cursor:pointer;">
                        <td class="expand-col"><?= $expandBtnCli ?></td>
                        <td><?= htmlspecialchars($grupo['nomeCli']) . $docCli ?></td>
                        <td class="text-center"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="valor-col font-semibold" style="color: #ef4444;"><?= $formatMoney($grupo['total']) ?></td>
                        <td class="text-center"></td>
                    </tr>
                    
                    <?php
                            $pedIndex = 0;
                            foreach ($grupo['pedidos'] as $keyPed => $ped):
                                $qtdParc = count($ped['divergencias']);
                                $subGroupIdPed = "div_ped_{$groupIndex}_{$pedIndex}";
                                $expandBtnPed = '';
                                if ($qtdParc > 1) {
                                    $expandBtnPed = '<button class="btn-expand" data-toggle-parcelas="'.$subGroupIdPed.'" title="Ver parcelas"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M7 10l5 5 5-5z"/></svg></button>';
                                }
                                $numPedStr = !empty($ped['numPedido']) && $ped['numPedido'] !== '—' ? htmlspecialchars($ped['numPedido']) : 'Sem Nº';
                                $rowCls = $qtdParc > 1 ? 'expandable-row' : '';
                                $cursorStyle = $qtdParc > 1 ? 'cursor:pointer;' : '';
                    ?>
                    <tr class="<?= $rowCls ?> sub-parcelas <?= $subGroupIdCli ?>" style="background-color: #fcfcfc; <?= $cursorStyle ?> display:none;">
                        <td class="expand-col" style="padding-left: 15px;"><?= $expandBtnPed ?></td>
                        <td><strong><?= $numPedStr ?></strong></td>
                        <td class="text-center"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="valor-col font-semibold" style="color: #ef4444;"><?= $formatMoney($ped['total_diferenca']) ?></td>
                        <td class="text-center"></td>
                    </tr>
                    
                    <?php 
                                if ($qtdParc > 1):
                                    foreach ($ped['divergencias'] as $div):
                                        $local = $div['local_calc'];
                                        $bling = $div['bling_calc'];
                                        $diferenca = $div['diferenca_calc'];
                                        $isLocalMaior = $local > $bling;
                                        $diffColor = $isLocalMaior ? '#ef4444' : '#eab308';
                                        $diffSignal = $isLocalMaior ? 'Local + ' : 'Bling + ';
                    ?>
                    <tr class="sub-parcelas <?= $subGroupIdPed ?> <?= $subGroupIdCli ?>" style="background-color: #fafafa; display:none;">
                        <td style="padding-left: 45px;"><small class="text-xs" style="color: #64748b;">(Detalhe)</small></td>
                        <td class="nome-col"><?= htmlspecialchars($div['NOME_CLIENTE']) ?></td>
                        <td class="date-col"><?= date('d/m/Y', strtotime($div['DATA_VENCIMENTO'])) ?></td>
                        <td class="valor-col font-semibold"><?= $formatMoney($div['TOTAL_PEDIDO']) ?></td>
                        <td class="valor-col" style="color: #64748b; font-weight: 500;"><?= $formatMoney($local) ?></td>
                        <td class="valor-col" style="color: #059669; font-weight: 600;"><?= $formatMoney($bling) ?></td>
                        <td class="valor-col" style="color: <?= $diffColor ?>; font-weight: 600;"><?= $diffSignal . $formatMoney($diferenca) ?></td>
                        <td class="text-center" style="white-space: nowrap;">
                            <button onclick="abrirModalCorrigir(<?= $div['ID_PEDIDO'] ?>, <?= $local ?>)" title="Corrigir Baixa" class="btn-action-icon" style="border:none; background:transparent; cursor:pointer;">
                                <x-icons.check width="16" height="16" />
                            </button>
                            <button onclick="estornarBaixaPedido(<?= $div['ID_PEDIDO'] ?>)" title="Estornar Baixas Locais" class="btn-action-icon" style="border:none; background:transparent; cursor:pointer; color: #64748b; margin-left: 4px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </td>
                    </tr>
                    <?php 
                                    endforeach;
                                else:
                                    // Se tem apenas 1 parcela (divergência) no pedido, exibe diretamente os detalhes na própria linha do pedido
                                    $div = $ped['divergencias'][0];
                                    $local = $div['local_calc'];
                                    $bling = $div['bling_calc'];
                                    $diferenca = $div['diferenca_calc'];
                                    $isLocalMaior = $local > $bling;
                                    $diffColor = $isLocalMaior ? '#ef4444' : '#eab308';
                                    $diffSignal = $isLocalMaior ? 'Local + ' : 'Bling + ';
                    ?>
                    <tr class="sub-parcelas <?= $subGroupIdCli ?>" style="background-color: #fafafa; display:none;">
                        <td style="padding-left: 25px;"><small class="text-xs" style="color: #64748b;">↳ <?= $numPedStr ?></small></td>
                        <td class="nome-col"><?= htmlspecialchars($div['NOME_CLIENTE']) ?></td>
                        <td class="date-col"><?= date('d/m/Y', strtotime($div['DATA_VENCIMENTO'])) ?></td>
                        <td class="valor-col font-semibold"><?= $formatMoney($div['TOTAL_PEDIDO']) ?></td>
                        <td class="valor-col" style="color: #64748b; font-weight: 500;"><?= $formatMoney($local) ?></td>
                        <td class="valor-col" style="color: #059669; font-weight: 600;"><?= $formatMoney($bling) ?></td>
                        <td class="valor-col" style="color: <?= $diffColor ?>; font-weight: 600;"><?= $diffSignal . $formatMoney($diferenca) ?></td>
                        <td class="text-center" style="white-space: nowrap;">
                            <button onclick="abrirModalCorrigir(<?= $div['ID_PEDIDO'] ?>, <?= $local ?>)" title="Corrigir Baixa" class="btn-action-icon" style="border:none; background:transparent; cursor:pointer;">
                                <x-icons.check width="16" height="16" />
                            </button>
                            <button onclick="estornarBaixaPedido(<?= $div['ID_PEDIDO'] ?>)" title="Estornar Baixas Locais" class="btn-action-icon" style="border:none; background:transparent; cursor:pointer; color: #64748b; margin-left: 4px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </td>
                    </tr>
                    <?php
                                endif;
                                $pedIndex++;
                            endforeach;
                            $groupIndex++;
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
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

@include('components.modal_baixa_manual')

<!-- Modal Corrigir Baixa -->
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
                    <button class="btn-modal-cancel" onclick="fecharModalCorrigirBaixa()">
                        Cancelar
                    </button>
                    <button class="btn-modal-confirm-blue" onclick="confirmarCorrecaoBaixa()">
                        <x-icons.check-heavy width="16" height="16" />
                        Salvar Correção
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = "{{ url('/') }}";
    
    function abrirModalCorrigir(idPedido, atualPago) {
        document.getElementById('corrigir-id-pedido').value = idPedido;
        document.getElementById('corrigir-novo-valor').value = atualPago;
        document.getElementById('modal-corrigir-baixa').style.display = 'flex';
    }

    function fecharModalCorrigirBaixa() {
        document.getElementById('modal-corrigir-baixa').style.display = 'none';
    }

    function confirmarCorrecaoBaixa() {
        const idPedido = document.getElementById('corrigir-id-pedido').value;
        const novoValor = document.getElementById('corrigir-novo-valor').value;
        
        if (!idPedido || novoValor === '') {
            alert('Por favor, informe o novo valor.');
            return;
        }

        fetch(BASE_URL + '/divergencias/corrigir-baixa', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_pedido: idPedido, novo_valor: novoValor })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                window.location.reload();
            } else {
                alert(res.error || 'Erro ao corrigir a baixa.');
            }
        })
        .catch(err => {
            alert('Erro de comunicação.');
            console.error(err);
        });
    }

    function estornarBaixaPedido(idPedido) {
        if (!confirm('Deseja realmente estornar todas as baixas locais deste pedido? O valor voltará a ser o do Bling.')) {
            return;
        }

        fetch('{{ url("/divergencias/estornar") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_pedido: idPedido })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                window.location.reload();
            } else {
                alert(res.error || 'Erro ao estornar a baixa.');
            }
        })
        .catch(err => {
            alert('Erro de comunicação.');
            console.error(err);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-expand');
            if (btn) {
                const targetClass = btn.getAttribute('data-toggle-parcelas');
                const rows = document.querySelectorAll('.' + targetClass);
                
                btn.classList.toggle('expanded');
                const isExpanded = btn.classList.contains('expanded');
                
                rows.forEach(row => {
                    if (isExpanded) {
                        row.style.display = 'table-row';
                    } else {
                        row.style.display = 'none';
                        // if collapsing, also collapse children
                        const childBtns = row.querySelectorAll('.btn-expand');
                        childBtns.forEach(cBtn => {
                            cBtn.classList.remove('expanded');
                            const cTarget = cBtn.getAttribute('data-toggle-parcelas');
                            document.querySelectorAll('.' + cTarget).forEach(cRow => cRow.style.display = 'none');
                        });
                    }
                });
            }
        });
    });
</script>
<script src="{{ asset('js/baixa_manual.js') }}?v=<?= time() ?>"></script>

@endsection
