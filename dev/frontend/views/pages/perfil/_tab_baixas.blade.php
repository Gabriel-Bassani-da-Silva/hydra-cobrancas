{{-- _tab_baixas.blade.php --}}
<div class="card" style="margin-top: 1.5rem;">
    <div class="table-responsive">
        <table class="cr-table" id="table-minhas-baixas">
            <thead>
                <tr>
                    <th>Última Baixa</th>
                    <th>Cliente</th>
                    <th>Qtd. Baixas</th>
                    <th class="valor-col">Total Baixado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($minhasBaixas)): ?>
                    <tr><td colspan="4" class="text-center">Você não possui nenhuma baixa manual.</td></tr>
                <?php else: ?>
                    <?php foreach ($minhasBaixas as $b): ?>
                        <tr onclick="abrirModalBaixas(<?= $b->ID_CLIENTE ?>)" style="cursor: pointer;" class="hover-row">
                            <td><?= date('d/m/Y H:i', strtotime($b->ULTIMA_BAIXA)) ?></td>
                            <td><?= htmlspecialchars($b->NOME_CONTATO) ?></td>
                            <td><?= $b->QTD_BAIXAS ?></td>
                            <td class="valor-col">R$ <?= number_format($b->TOTAL_BAIXADO, 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
