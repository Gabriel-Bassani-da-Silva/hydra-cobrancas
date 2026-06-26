<div class="table-responsive">
    <table class="cr-table" style="font-size: 0.9rem;">
        <thead>
            <tr>
                <th>Data</th>
                <th class="center-col">Documento</th>
                <th>Colaborador</th>
                <th class="valor-col">Valor Baixado</th>
                <th class="cr-col-acoes text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($baixas) || count($baixas) === 0): ?>
                <tr>
                    <td colspan="5" class="text-center">Nenhuma baixa encontrada para este cliente.</td>
                </tr>
            <?php else: ?>
                <?php foreach($baixas as $b): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($b->DATA_REGISTRO)) ?></td>
                        <td class="center-col"><?= htmlspecialchars($b->NUM_PEDIDO) ?></td>
                        <td><?= htmlspecialchars($b->NOME_COLABORADOR) ?></td>
                        <td class="valor-col" style="font-weight: 600; color: #059669;">R$ <?= number_format($b->VALOR_PAGO_PEDIDO, 2, ',', '.') ?></td>
                        <td class="cr-col-acoes text-center">
                            <button class="btn-acao btn-delete" onclick="estornarBaixa(<?= $b->ID_DETALHE ?>)" title="Estornar Baixa">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
