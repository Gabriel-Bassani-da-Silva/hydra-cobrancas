<div class="table-responsive">
    <table class="cr-table">
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Vencimento</th>
                <th class="valor-col">Total Pedido</th>
                <th class="valor-col" style="color: #64748b;">Pago Local</th>
                <th class="valor-col" style="color: #059669;">Pago Bling</th>
                <th class="valor-col">Divergência</th>
                <th class="cr-col-acoes">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($divergencias) || count($divergencias) === 0): ?>
                <tr>
                    <td colspan="7" class="text-center">Nenhuma divergência encontrada.</td>
                </tr>
            <?php else: ?>
                <?php 
                $formatMoney = function($value) {
                    return 'R$ ' . number_format($value ?? 0, 2, ',', '.');
                };

                foreach($divergencias as $div): 
                    $local = $div['local_calc'];
                    $bling = $div['bling_calc'];
                    $diferenca = $div['diferenca_calc'];
                    $isLocalMaior = $local > $bling;
                    $diffColor = $isLocalMaior ? '#ef4444' : '#eab308';
                    $diffSignal = $isLocalMaior ? 'Local + ' : 'Bling + ';
                    $numPedStr = !empty($div['NUM_PEDIDO']) && $div['NUM_PEDIDO'] !== '—' ? htmlspecialchars($div['NUM_PEDIDO']) : 'Sem Nº';
                ?>
                    <tr>
                        <td><strong><?= $numPedStr ?></strong></td>
                        <td class="date-col"><?= date('d/m/Y', strtotime($div['DATA_VENCIMENTO'])) ?></td>
                        <td class="valor-col font-semibold"><?= $formatMoney($div['TOTAL_PEDIDO']) ?></td>
                        <td class="valor-col" style="color: #64748b; font-weight: 500;"><?= $formatMoney($local) ?></td>
                        <td class="valor-col" style="color: #059669; font-weight: 600;"><?= $formatMoney($bling) ?></td>
                        <td class="valor-col" style="color: <?= $diffColor ?>; font-weight: 600;"><?= $diffSignal . $formatMoney($diferenca) ?></td>
                        <td class="cr-col-acoes" style="white-space: nowrap;">
                            <button class="btn-acao btn-edit" onclick="abrirModalCorrigir(<?= $div['ID_PEDIDO'] ?>, <?= $local ?>)" title="Corrigir Baixa">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            </button>
                            <button class="btn-acao btn-delete" onclick="estornarBaixaPedido(<?= $div['ID_PEDIDO'] ?>)" title="Estornar Baixas Locais">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
