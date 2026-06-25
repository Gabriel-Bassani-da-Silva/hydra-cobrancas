<?php
/**
 * @var array $data (as contas)
 * @var string $tipo ('clientes', 'representantes', 'financeiros')
 */

if (empty($data)) {
    echo '<p class="empty-msg text-center">Nenhuma conta encontrada.</p>';
    return;
}
$BASE = rtrim(url('/') ?? '', '/');
function formatCurrencyComponent($val) {
    return 'R$ ' . number_format((float)$val, 2, ',', '.');
}

function formatDateComponent($date) {
    if (!$date || $date === '0000-00-00') return '—';
    return date('d/m/Y', strtotime($date));
}

function getSyncBtnHtml($url, $title, $isSmall = false) {
    $className = $isSmall ? 'btn-action-icon-sm' : 'btn-action-icon';
    $svgSize = $isSmall ? '14' : '16';
    return '<a href="'.htmlspecialchars($url).'" title="'.htmlspecialchars($title).'" class="'.$className.'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="'.$svgSize.'" height="'.$svgSize.'"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
            </a>';
}

function getSmallBadgeHtml($situacao) {
    $s = (int)$situacao;
    if ($s === 1) return '<span class="status-badge badge-warning" style="font-size:0.65rem;">Em Aberto</span>';
    if ($s === 3) return '<span class="status-badge badge-info" style="font-size:0.65rem;">Parcial</span>';
    return '<span class="status-badge badge-success" style="font-size:0.65rem;">Pago</span>';
}

if ($tipo === 'financeiros' || $tipo === 'representantes') {
    $gruposCli = [];
    foreach ($data as $c) {
        $nomeCli = !empty($c['NOME_CLIENTE']) ? $c['NOME_CLIENTE'] : (!empty($c['NOME_CONTATO']) ? $c['NOME_CONTATO'] : 'Não Informado');
        $idCliKey = !empty($c['ID_CLIENTE']) ? $c['ID_CLIENTE'] : $nomeCli;
        if (!isset($gruposCli[$idCliKey])) {
            $gruposCli[$idCliKey] = [
                'nomeCli' => $nomeCli,
                'doc' => $c['CPF_CNPJ'] ?? '',
                'total' => 0,
                'ultimoVencimento' => $c['VENCIMENTO'],
                'ids' => [],
                'pedidos' => []
            ];
        }
        
        $keyPed = !empty($c['NUMERO_DOCUMENTO']) && $c['NUMERO_DOCUMENTO'] !== '—' ? $c['NUMERO_DOCUMENTO'] : 'SEM_NUM_' . $c['ID_CONTA_RECEBER'];
        
        if (!isset($gruposCli[$idCliKey]['pedidos'][$keyPed])) {
            $gruposCli[$idCliKey]['pedidos'][$keyPed] = [
                'numPedido' => $c['NUMERO_DOCUMENTO'],
                'parcelas' => [],
                'total' => 0,
                'ultimoVencimento' => $c['VENCIMENTO'],
                'ids' => []
            ];
        }
        
        $gruposCli[$idCliKey]['pedidos'][$keyPed]['parcelas'][] = $c;
        $valLiq = ((float)($c['VALOR'] ?? 0)) - ((float)($c['VALOR_PAGO'] ?? 0));
        $gruposCli[$idCliKey]['pedidos'][$keyPed]['total'] += $valLiq;
        $gruposCli[$idCliKey]['pedidos'][$keyPed]['ids'][] = $c['ID_CONTA_RECEBER'];
        if ($c['VENCIMENTO'] > $gruposCli[$idCliKey]['pedidos'][$keyPed]['ultimoVencimento']) {
            $gruposCli[$idCliKey]['pedidos'][$keyPed]['ultimoVencimento'] = $c['VENCIMENTO'];
        }
        
        $gruposCli[$idCliKey]['total'] += $valLiq;
        $gruposCli[$idCliKey]['ids'][] = $c['ID_CONTA_RECEBER'];
        if ($c['VENCIMENTO'] > $gruposCli[$idCliKey]['ultimoVencimento']) {
            $gruposCli[$idCliKey]['ultimoVencimento'] = $c['VENCIMENTO'];
        }
    }
    
    $syncAba = $tipo === 'representantes' ? 'representantes' : 'financeiros';
    $totalClientes = count($gruposCli);
    $isAutoExpandCli = ($totalClientes === 1);
    $groupIndex = 0;
    $tbodyHTML = '';
    
    foreach ($gruposCli as $nomeCli => $grupo) {
        $docCli = $grupo['doc'] ? '<br><small class="text-xs">'.htmlspecialchars($grupo['doc']).'</small>' : '';
        $totalPedidos = count($grupo['pedidos']);
        $subGroupIdCli = "cf_cli_{$groupIndex}";
        
        $expandBtnCli = '';
        if ($totalPedidos > 0) {
            $svgPathCli = $isAutoExpandCli ? 'M7 14l5-5 5 5z' : 'M7 10l5 5 5-5z';
            $expandedCls = $isAutoExpandCli ? 'expanded' : '';
            $expandBtnCli = '<button class="btn-expand '.$expandedCls.'" data-toggle-parcelas="'.$subGroupIdCli.'" title="Ver pedidos"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="'.$svgPathCli.'"/></svg></button>';
        }
        
        $nomeDisplay = htmlspecialchars($grupo['nomeCli']) . $docCli;
        $syncBtn = getSyncBtnHtml("{$BASE}/contas-receber/sincronizar-unico?aba={$syncAba}&id=" . implode(',', $grupo['ids']), 'Sincronizar cliente', true);
        
        $tbodyHTML .= '<tr class="expandable-row" style="cursor:pointer;">
            <td class="expand-col">'.$expandBtnCli.'</td>
            <td>'.$nomeDisplay.'</td>
            <td class="text-center">'.$totalPedidos.'</td>
            <td>'.formatDateComponent($grupo['ultimoVencimento']).'</td>
            <td class="valor-col font-semibold pr-40">'.formatCurrencyComponent($grupo['total']).'</td>
            <td class="text-center">'.$syncBtn.'</td>
        </tr>';
        
        $pedidosHtml = '';
        $pedIndex = 0;
        foreach ($grupo['pedidos'] as $keyPed => $ped) {
            $qtdParc = count($ped['parcelas']);
            $isAutoExpandPed = ($totalPedidos === 1 && $qtdParc > 1);
            $subGroupIdPed = "cf_ped_{$groupIndex}_{$pedIndex}";
            
            $expandBtnPed = '';
            if ($qtdParc > 1) {
                $svgPathPed = $isAutoExpandPed ? 'M7 14l5-5 5 5z' : 'M7 10l5 5 5-5z';
                $expandedClsPed = $isAutoExpandPed ? 'expanded' : '';
                $expandBtnPed = '<button class="btn-expand '.$expandedClsPed.'" data-toggle-parcelas="'.$subGroupIdPed.'" title="Ver parcelas"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="'.$svgPathPed.'"/></svg></button>';
            }
            
            $numPedStr = !empty($ped['numPedido']) && $ped['numPedido'] !== '—' ? htmlspecialchars($ped['numPedido']) : 'Sem Nº';
            
            $syncTxt = $qtdParc > 1 ? 'Sincronizar todas as parcelas' : 'Sincronizar';
            $syncBtnPed = getSyncBtnHtml("{$BASE}/contas-receber/sincronizar-unico?aba={$syncAba}&id=" . implode(',', $ped['ids']), $syncTxt, true);
            
            $rowCls = $qtdParc > 1 ? 'expandable-row' : '';
            $cursorStyle = $qtdParc > 1 ? 'cursor:pointer;' : '';
            $pedidosHtml .= '<tr class="'.$rowCls.'" style="background-color: #fcfcfc; '.$cursorStyle.'">
                <td class="expand-col">'.$expandBtnPed.'</td>
                <td><strong>'.$numPedStr.'</strong></td>
                <td class="text-center">'.$qtdParc.'</td>
                <td>'.formatDateComponent($ped['ultimoVencimento']).'</td>
                <td class="valor-col font-semibold pr-40">'.formatCurrencyComponent($ped['total']).'</td>
                <td class="text-center">'.$syncBtnPed.'</td>
            </tr>';
            
            if ($qtdParc > 1) {
                $parcelasHtml = '';
                foreach ($ped['parcelas'] as $p) {
                    $valorP = ((float)($p['VALOR'] ?? 0)) - ((float)($p['VALOR_PAGO'] ?? 0));
                    $syncBtnP = getSyncBtnHtml("{$BASE}/contas-receber/sincronizar-unico?aba={$syncAba}&id=".$p['ID_CONTA_RECEBER'], 'Sincronizar esta parcela', true);
                    $badgeP = getSmallBadgeHtml($p['SITUACAO'] ?? 1);
                    
                    $parcelasHtml .= '<tr style="background-color: #fff;">
                        <td>'.formatDateComponent($p['VENCIMENTO']).'</td>
                        <td class="valor-col font-semibold pr-40">'.formatCurrencyComponent($valorP).'</td>
                        <td class="text-center">'.$badgeP.'</td>
                        <td class="text-center">'.$syncBtnP.'</td>
                    </tr>';
                }
                
                $dispPed = $isAutoExpandPed ? '' : 'd-none';
                $pedidosHtml .= '<tr class="detail-row '.$dispPed.'" id="parcelas_'.$subGroupIdPed.'">
                    <td colspan="6">
                        <div class="detail-content" style="padding-left: 40px;">
                            <table class="detail-table inner-detail">
                                <thead>
                                    <tr>
                                        <th>Vencimento</th>
                                        <th class="valor-col">Valor</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>'.$parcelasHtml.'</tbody>
                            </table>
                        </div>
                    </td>
                </tr>';
            }
            $pedIndex++;
        }
        
        $dispCli = $isAutoExpandCli ? '' : 'd-none';
        $tbodyHTML .= '<tr class="detail-row '.$dispCli.'" id="parcelas_'.$subGroupIdCli.'">
            <td colspan="6">
                <div class="detail-content" style="padding-left: 20px;">
                    <table class="detail-table inner-detail">
                        <thead>
                            <tr>
                                <th class="expand-col"></th>
                                <th>Nº Pedido</th>
                                <th class="text-center">Qtd. Parcelas</th>
                                <th>Último Vencimento</th>
                                <th class="valor-col pr-40">Total Restante</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>'.$pedidosHtml.'</tbody>
                    </table>
                </div>
            </td>
        </tr>';
        
        $groupIndex++;
    }
    
    echo '<table class="detail-table">
        <thead>
            <tr>
                <th class="expand-col"></th>
                <th>Cliente</th>
                <th class="text-center">Qtd. Pedidos</th>
                <th>Último Vencimento</th>
                <th class="valor-col pr-40">Total Restante</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>'.$tbodyHTML.'</tbody>
    </table>';

} else {
    // Modo Clientes diretos
    $gruposPed = [];
    foreach ($data as $c) {
        $key = !empty($c['NUMERO_DOCUMENTO']) && $c['NUMERO_DOCUMENTO'] !== '—' ? $c['NUMERO_DOCUMENTO'] : 'SEM_NUM_' . $c['ID_CONTA_RECEBER'];
        if (!isset($gruposPed[$key])) {
            $gruposPed[$key] = [
                'numPedido' => $c['NUMERO_DOCUMENTO'],
                'nomeCli' => !empty($c['NOME_CLIENTE']) ? $c['NOME_CLIENTE'] : (!empty($c['NOME_CONTATO']) ? $c['NOME_CONTATO'] : 'Não Informado'),
                'docCli' => $c['CPF_CNPJ'] ?? '',
                'parcelas' => [],
                'total' => 0,
                'ultimoVencimento' => $c['VENCIMENTO'],
                'ids' => []
            ];
        }
        $gruposPed[$key]['parcelas'][] = $c;
        $valLiq = ((float)($c['VALOR'] ?? 0)) - ((float)($c['VALOR_PAGO'] ?? 0));
        $gruposPed[$key]['total'] += $valLiq;
        $gruposPed[$key]['ids'][] = $c['ID_CONTA_RECEBER'];
        if ($c['VENCIMENTO'] > $gruposPed[$key]['ultimoVencimento']) {
            $gruposPed[$key]['ultimoVencimento'] = $c['VENCIMENTO'];
        }
    }

    $groupIndex = 0;
    $totalGruposPed = count($gruposPed);
    $tbodyHTML = '';
    
    foreach ($gruposPed as $key => $grupo) {
        $qtd = count($grupo['parcelas']);
        $isAutoExpand = ($totalGruposPed === 1 && $qtd > 1);
        
        $expandBtn = '';
        $subGroupId = "{$tipo}_ped_{$groupIndex}";
        if ($qtd > 1) {
            $svgPath = $isAutoExpand ? 'M7 14l5-5 5 5z' : 'M7 10l5 5 5-5z';
            $expandedCls = $isAutoExpand ? 'expanded' : '';
            $expandBtn = '<button class="btn-expand '.$expandedCls.'" data-toggle-parcelas="'.$subGroupId.'" title="Ver parcelas"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="'.$svgPath.'"/></svg></button>';
        }
        
        $numPedStr = !empty($grupo['numPedido']) && $grupo['numPedido'] !== '—' ? htmlspecialchars($grupo['numPedido']) : 'Sem Nº';
        
        $syncTxt = $qtd > 1 ? 'Sincronizar todas as parcelas' : 'Sincronizar';
        $syncBtn = getSyncBtnHtml("{$BASE}/contas-receber/sincronizar-unico?aba={$tipo}&id=" . implode(',', $grupo['ids']), $syncTxt, true);

        $rowCls = $qtd > 1 ? 'expandable-row' : '';
        $cursorStyle = $qtd > 1 ? 'cursor:pointer;' : '';
        $tbodyHTML .= '<tr class="'.$rowCls.'" style="'.$cursorStyle.'">
            <td class="expand-col">'.$expandBtn.'</td>
            <td><strong>'.$numPedStr.'</strong></td>
            <td class="text-center">'.$qtd.'</td>
            <td>'.formatDateComponent($grupo['ultimoVencimento']).'</td>
            <td class="valor-col font-semibold pr-40">'.formatCurrencyComponent($grupo['total']).'</td>
            <td class="text-center">'.$syncBtn.'</td>
        </tr>';
        
        if ($qtd > 1) {
            $parcelasHtml = '';
            foreach ($grupo['parcelas'] as $p) {
                $valorP = ((float)($p['VALOR'] ?? 0)) - ((float)($p['VALOR_PAGO'] ?? 0));
                $syncBtnP = getSyncBtnHtml("{$BASE}/contas-receber/sincronizar-unico?aba={$tipo}&id=".$p['ID_CONTA_RECEBER'], 'Sincronizar esta parcela', true);
                $badgeP = getSmallBadgeHtml($p['SITUACAO'] ?? 1);
                
                $parcelasHtml .= '<tr>
                    <td>'.formatDateComponent($p['VENCIMENTO']).'</td>
                    <td class="valor-col font-semibold pr-40">'.formatCurrencyComponent($valorP).'</td>
                    <td class="text-center">'.$badgeP.'</td>
                    <td class="text-center">'.$syncBtnP.'</td>
                </tr>';
            }
            
            $dispPed = $isAutoExpand ? '' : 'd-none';
            $tbodyHTML .= '<tr class="detail-row '.$dispPed.'" id="parcelas_'.$subGroupId.'">
                <td colspan="6">
                    <div class="detail-content">
                        <table class="detail-table inner-detail">
                            <thead>
                                <tr>
                                    <th>Vencimento</th>
                                    <th class="valor-col">Valor</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Ação</th>
                                </tr>
                            </thead>
                            <tbody>'.$parcelasHtml.'</tbody>
                        </table>
                    </div>
                </td>
            </tr>';
        }
        $groupIndex++;
    }

    echo '<table class="detail-table">
        <thead>
            <tr>
                <th class="expand-col"></th>
                <th>Nº Pedido</th>
                <th class="text-center">Qtd. Parcelas</th>
                <th>Último Vencimento</th>
                <th class="valor-col pr-40">Valor Restante</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>'.$tbodyHTML.'</tbody>
    </table>';
}
?>
