<div class="table-responsive">
    <table class="cr-table">
        <thead>
            <tr>
                <th>Data do Registro</th>
                <th>Pedido</th>
                <th>Colaborador</th>
                <th class="valor-col">Valor Baixado</th>
                <th class="cr-col-acoes">Ações</th>
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
                        <td><?= htmlspecialchars($b->NUM_PEDIDO) ?></td>
                        <td><?= htmlspecialchars($b->NOME_COLABORADOR) ?></td>
                        <td class="valor-col">
                            <span class="valor-baixa-display" id="valor-display-<?= $b->ID_DETALHE ?>">R$ <?= number_format($b->VALOR_PAGO_PEDIDO, 2, ',', '.') ?></span>
                            <input type="number" step="0.01" min="0" class="input-edit-baixa" id="input-baixa-<?= $b->ID_DETALHE ?>" value="<?= number_format($b->VALOR_PAGO_PEDIDO, 2, '.', '') ?>" style="display:none; width: 100px; padding: 4px; text-align:right;">
                        </td>
                        <td class="cr-col-acoes" style="white-space: nowrap;">
                            <button class="btn-acao btn-edit" id="btn-edit-<?= $b->ID_DETALHE ?>" onclick="editarBaixa(<?= $b->ID_DETALHE ?>)" title="Editar Baixa">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            </button>
                            <button class="btn-acao btn-save" id="btn-save-<?= $b->ID_DETALHE ?>" onclick="salvarBaixa(<?= $b->ID_DETALHE ?>)" title="Salvar" style="display:none; color: #16a34a;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </button>
                            <button class="btn-acao btn-cancel" id="btn-cancel-<?= $b->ID_DETALHE ?>" onclick="cancelarEdicaoBaixa(<?= $b->ID_DETALHE ?>)" title="Cancelar" style="display:none; color: #64748b;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
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
