{{-- _tab_baixas.blade.php
     Conteúdo da aba Baixas: sub-tabs Todas/Divergentes + tabelas
     Variáveis esperadas: $todasBaixas, $divergencias --}}

<?php
// Agrupar divergências por cliente
$gruposDivergencias = [];
$totalDivergencia   = 0;
foreach ($divergencias ?? [] as $div) {
    $nomeCli  = !empty($div['NOME_CLIENTE']) ? $div['NOME_CLIENTE'] : 'Não Informado';
    $idCliKey = !empty($div['ID_CLIENTE'])   ? $div['ID_CLIENTE']   : $nomeCli;
    if (!isset($gruposDivergencias[$idCliKey])) {
        $gruposDivergencias[$idCliKey] = [
            'id'            => $idCliKey,
            'nomeCli'       => $nomeCli,
            'total_diferenca' => 0,
            'total_local'   => 0,
            'total_bling'   => 0,
            'qtd_pedidos'   => 0,
            'pedidos_unicos' => [],
        ];
    }
    $keyPed = !empty($div['NUM_PEDIDO']) && $div['NUM_PEDIDO'] !== '—'
        ? $div['NUM_PEDIDO']
        : 'SEM_NUM_' . $div['ID_PEDIDO'];
    if (!isset($gruposDivergencias[$idCliKey]['pedidos_unicos'][$keyPed])) {
        $gruposDivergencias[$idCliKey]['pedidos_unicos'][$keyPed] = true;
        $gruposDivergencias[$idCliKey]['qtd_pedidos']++;
    }
    $local     = (float)$div['VALOR_PAGO_LOCAL'];
    $bling     = (float)$div['VALOR_PAGO_BLING'];
    $diferenca = abs($local - $bling);
    $totalDivergencia                              += $diferenca;
    $gruposDivergencias[$idCliKey]['total_diferenca'] += $diferenca;
    $gruposDivergencias[$idCliKey]['total_local']     += $local;
    $gruposDivergencias[$idCliKey]['total_bling']     += $bling;
}
$temDivergencias = !empty($gruposDivergencias);
?>

<!-- Sub-abas das Baixas: Todas / Divergentes -->
<div class="cr-sub-tabs">
    <div class="cr-sub-tabs-group">
        <button id="btn-baixas-todas" class="sub-tab-btn active" onclick="setBaixasFiltro('todas')">
            Todas <span class="tab-count"><?= count($todasBaixas ?? []) ?></span>
        </button>
        <button id="btn-baixas-divergentes" class="sub-tab-btn <?= $temDivergencias ? 'sub-tab-warning' : '' ?>" onclick="setBaixasFiltro('divergentes')">
            ⚠ Divergentes <span class="tab-count"><?= count($gruposDivergencias) ?></span>
        </button>
    </div>
    <?php if ($temDivergencias): ?>
    <div class="cr-total-inadimplente cr-total-danger">
        Total de Divergências:&nbsp;<span>R$ <?= number_format($totalDivergencia, 2, ',', '.') ?></span>
    </div>
    <?php endif; ?>
</div>

<!-- PAINEL: Todas as Baixas -->
<div id="painel-baixas-todas" class="card">
    <div class="table-responsive">
        <table class="cr-table" id="table-baixas">
            <thead>
                <tr>
                    <th>Última Baixa</th>
                    <th>Cliente</th>
                    <th>Qtd. Baixas</th>
                    <th class="valor-col">Total Baixado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($todasBaixas)): ?>
                    <tr><td colspan="4" class="text-center">Nenhuma baixa manual encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($todasBaixas as $b): ?>
                        <tr onclick="abrirModalBaixas(<?= $b->ID_CLIENTE ?>)" class="hover-row clickable-row">
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

<!-- PAINEL: Apenas Divergentes -->
<div id="painel-baixas-divergentes" class="card" style="display: none;">
    <div class="table-responsive">
        <table class="cr-table" id="table-divergencias">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Qtd. Pedidos</th>
                    <th class="valor-col th-local">Pago Local (Hydra)</th>
                    <th class="valor-col th-bling">Pago API (Bling)</th>
                    <th class="valor-col">Divergência</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gruposDivergencias)): ?>
                    <tr><td colspan="5" class="text-center empty-divergencias">Nenhuma divergência encontrada. Tudo sincronizado! ✓</td></tr>
                <?php else: ?>
                    <?php foreach ($gruposDivergencias as $grupo): ?>
                    <tr onclick="abrirModalDivergencias('<?= $grupo['id'] ?>')" class="hover-row clickable-row">
                        <td><?= htmlspecialchars($grupo['nomeCli']) ?></td>
                        <td><?= $grupo['qtd_pedidos'] ?></td>
                        <td class="valor-col">R$ <?= number_format($grupo['total_local'], 2, ',', '.') ?></td>
                        <td class="valor-col">R$ <?= number_format($grupo['total_bling'], 2, ',', '.') ?></td>
                        <td class="valor-col valor-divergencia">R$ <?= number_format($grupo['total_diferenca'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($gruposDivergencias)): ?>
            <tfoot>
                <tr class="tfoot-total">
                    <td colspan="4" class="tfoot-label">Somatório Total das Diferenças:</td>
                    <td class="valor-col valor-divergencia">R$ <?= number_format($totalDivergencia, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
