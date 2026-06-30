@if(empty($data))
    <p class="empty-msg text-center">Nenhuma conta encontrada.</p>
@else
    @if($tipo === 'financeiros' || $tipo === 'representantes')
        @php
            $syncAba = $tipo === 'representantes' ? 'representantes' : 'financeiros';
            $totalClientes = count($grupos);
            $isAutoExpandCli = ($totalClientes === 1);
            $groupIndex = 0;
        @endphp
        
        <table class="detail-table">
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
            <tbody>
                @foreach($grupos as $nomeCli => $grupo)
                    @php
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
                        $syncUrlCli = rtrim(url('/') ?? '', '/') . "/contas-receber/sincronizar-unico?aba={$syncAba}&id=" . implode(',', $grupo['ids']);
                    @endphp
                    <tr class="expandable-row" style="cursor:pointer;">
                        <td class="expand-col">{!! $expandBtnCli !!}</td>
                        <td>{!! $nomeDisplay !!}</td>
                        <td class="text-center">{{ $totalPedidos }}</td>
                        <td>{{ \App\Helpers\FormatHelper::date($grupo['ultimoVencimento']) }}</td>
                        <td class="valor-col font-semibold pr-40">{{ \App\Helpers\FormatHelper::currency($grupo['total']) }}</td>
                        <td class="text-center">
                            <a href="{{ $syncUrlCli }}" title="Sincronizar cliente" class="btn-action-icon-sm">
                                <x-icons.refresh width="14" height="14" />
                            </a>
                            <button onclick="abrirModalBaixa({{ json_encode($grupo['ids']) }})" title="Baixar cliente" class="btn-action-icon-sm" style="margin-left:4px; border:none; background:transparent; cursor:pointer;">
                                <x-icons.check width="14" height="14" />
                            </button>
                        </td>
                    </tr>

                    {{-- Pedidos --}}
                    @php $dispCli = $isAutoExpandCli ? '' : 'd-none'; @endphp
                    <tr class="detail-row {{ $dispCli }}" id="parcelas_{{ $subGroupIdCli }}">
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
                                    <tbody>
                                        @php $pedIndex = 0; @endphp
                                        @foreach($grupo['pedidos'] as $keyPed => $ped)
                                            @php
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
                                                $syncUrlPed = rtrim(url('/') ?? '', '/') . "/contas-receber/sincronizar-unico?aba={$syncAba}&id=" . implode(',', $ped['ids']);
                                                $rowCls = $qtdParc > 1 ? 'expandable-row' : '';
                                                $cursorStyle = $qtdParc > 1 ? 'cursor:pointer;' : '';
                                            @endphp
                                            <tr class="{{ $rowCls }}" style="background-color: #fcfcfc; {{ $cursorStyle }}">
                                                <td class="expand-col">{!! $expandBtnPed !!}</td>
                                                <td><strong>{{ $numPedStr }}</strong></td>
                                                <td class="text-center">{{ $qtdParc }}</td>
                                                <td>{{ \App\Helpers\FormatHelper::date($ped['ultimoVencimento']) }}</td>
                                                <td class="valor-col font-semibold pr-40">{{ \App\Helpers\FormatHelper::currency($ped['total']) }}</td>
                                                <td class="text-center">
                                                    <a href="{{ $syncUrlPed }}" title="{{ $syncTxt }}" class="btn-action-icon-sm">
                                                        <x-icons.refresh width="14" height="14" />
                                                    </a>
                                                    <button onclick="abrirModalBaixa({{ json_encode($ped['ids']) }})" title="{{ $qtdParc > 1 ? 'Baixar todas as parcelas' : 'Baixar' }}" class="btn-action-icon-sm" style="margin-left:4px; border:none; background:transparent; cursor:pointer;">
                                                        <x-icons.check width="14" height="14" />
                                                    </button>
                                                    <button onclick="converterParaCheque('{{ implode(',', $ped['ids']) }}')" title="Mudar para Cheque" class="btn-action-icon-sm" style="margin-left:4px; border:none; background:transparent; cursor:pointer; color: #ea580c;">
                                                        <span style="font-size:12px;">💸</span>
                                                    </button>
                                                </td>
                                            </tr>

                                            {{-- Parcelas --}}
                                            @if($qtdParc > 1)
                                                @php $dispPed = $isAutoExpandPed ? '' : 'd-none'; @endphp
                                                <tr class="detail-row {{ $dispPed }}" id="parcelas_{{ $subGroupIdPed }}">
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
                                                                <tbody>
                                                                    @foreach($ped['parcelas'] as $p)
                                                                        @php
                                                                            $valorP = ((float)($p['VALOR'] ?? 0)) - ((float)($p['VALOR_PAGO'] ?? 0));
                                                                            $syncUrlP = rtrim(url('/') ?? '', '/') . "/contas-receber/sincronizar-unico?aba={$syncAba}&id=".$p['ID_CONTA_RECEBER'];
                                                                            $s = (int)($p['SITUACAO'] ?? 1);
                                                                        @endphp
                                                                        <tr style="background-color: #fff;">
                                                                            <td>{{ \App\Helpers\FormatHelper::date($p['VENCIMENTO']) }}</td>
                                                                            <td class="valor-col font-semibold pr-40">{{ \App\Helpers\FormatHelper::currency($valorP) }}</td>
                                                                            <td class="text-center">
                                                                                @if($s === 1) <span class="status-badge badge-warning" style="font-size:0.65rem;">Em Aberto</span>
                                                                                @elseif($s === 3) <span class="status-badge badge-info" style="font-size:0.65rem;">Parcial</span>
                                                                                @else <span class="status-badge badge-success" style="font-size:0.65rem;">Pago</span>
                                                                                @endif
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <a href="{{ $syncUrlP }}" title="Sincronizar esta parcela" class="btn-action-icon-sm">
                                                                                    <x-icons.refresh width="14" height="14" />
                                                                                </a>
                                                                                <button onclick="abrirModalBaixa([{{ $p['ID_CONTA_RECEBER'] }}])" title="Baixar esta parcela" class="btn-action-icon-sm" style="margin-left:4px; border:none; background:transparent; cursor:pointer;">
                                                                                    <x-icons.check width="14" height="14" />
                                                                               </button>
                                                                               <button onclick="converterParaCheque('{{ $p['ID_CONTA_RECEBER'] }}')" title="Mudar para Cheque" class="btn-action-icon-sm" style="margin-left:4px; border:none; background:transparent; cursor:pointer; color: #ea580c;">
                                                                                    <span style="font-size:12px;">💸</span>
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                            @php $pedIndex++; @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @php $groupIndex++; @endphp
                @endforeach
            </tbody>
        </table>
    @else
        {{-- Modo Clientes diretos --}}
        @php
            $groupIndex = 0;
            $totalGruposPed = count($grupos);
        @endphp
        <table class="detail-table">
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
            <tbody>
                @foreach($grupos as $key => $grupo)
                    @php
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
                        $syncUrl = rtrim(url('/') ?? '', '/') . "/contas-receber/sincronizar-unico?aba={$tipo}&id=" . implode(',', $grupo['ids']);
                        $rowCls = $qtd > 1 ? 'expandable-row' : '';
                        $cursorStyle = $qtd > 1 ? 'cursor:pointer;' : '';
                    @endphp
                    <tr class="{{ $rowCls }}" style="{{ $cursorStyle }}">
                        <td class="expand-col">{!! $expandBtn !!}</td>
                        <td><strong>{{ $numPedStr }}</strong></td>
                        <td class="text-center">{{ $qtd }}</td>
                        <td>{{ \App\Helpers\FormatHelper::date($grupo['ultimoVencimento']) }}</td>
                        <td class="valor-col font-semibold pr-40">{{ \App\Helpers\FormatHelper::currency($grupo['total']) }}</td>
                        <td class="text-center">
                            <a href="{{ $syncUrl }}" title="{{ $syncTxt }}" class="btn-action-icon-sm">
                                <x-icons.refresh width="14" height="14" />
                            </a>
                            <button onclick="abrirModalBaixa({{ json_encode($grupo['ids']) }})" title="{{ $qtd > 1 ? 'Baixar todas as parcelas' : 'Baixar' }}" class="btn-action-icon-sm" style="margin-left:4px; border:none; background:transparent; cursor:pointer;">
                                <x-icons.check width="14" height="14" />
                            </button>
                            <button onclick="converterParaCheque('{{ implode(',', $grupo['ids']) }}')" title="Mudar para Cheque" class="btn-action-icon-sm" style="margin-left:4px; border:none; background:transparent; cursor:pointer; color: #ea580c;">
                                <span style="font-size:12px;">💸</span>
                            </button>
                        </td>
                    </tr>

                    @if($qtd > 1)
                        @php $dispPed = $isAutoExpand ? '' : 'd-none'; @endphp
                        <tr class="detail-row {{ $dispPed }}" id="parcelas_{{ $subGroupId }}">
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
                                        <tbody>
                                            @foreach($grupo['parcelas'] as $p)
                                                @php
                                                    $valorP = ((float)($p['VALOR'] ?? 0)) - ((float)($p['VALOR_PAGO'] ?? 0));
                                                    $syncUrlP = rtrim(url('/') ?? '', '/') . "/contas-receber/sincronizar-unico?aba={$tipo}&id=".$p['ID_CONTA_RECEBER'];
                                                    $s = (int)($p['SITUACAO'] ?? 1);
                                                @endphp
                                                <tr>
                                                    <td>{{ \App\Helpers\FormatHelper::date($p['VENCIMENTO']) }}</td>
                                                    <td class="valor-col font-semibold pr-40">{{ \App\Helpers\FormatHelper::currency($valorP) }}</td>
                                                    <td class="text-center">
                                                        @if($s === 1) <span class="status-badge badge-warning" style="font-size:0.65rem;">Em Aberto</span>
                                                        @elseif($s === 3) <span class="status-badge badge-info" style="font-size:0.65rem;">Parcial</span>
                                                        @else <span class="status-badge badge-success" style="font-size:0.65rem;">Pago</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="{{ $syncUrlP }}" title="Sincronizar esta parcela" class="btn-action-icon-sm">
                                                            <x-icons.refresh width="14" height="14" />
                                                        </a>
                                                        <button onclick="abrirModalBaixa([{{ $p['ID_CONTA_RECEBER'] }}])" title="Baixar esta parcela" class="btn-action-icon-sm" style="margin-left:4px; border:none; background:transparent; cursor:pointer;">
                                                            <x-icons.check width="14" height="14" />
                                                        </button>
                                                        <button onclick="converterParaCheque('{{ $p['ID_CONTA_RECEBER'] }}')" title="Mudar para Cheque" class="btn-action-icon-sm" style="margin-left:4px; border:none; background:transparent; cursor:pointer; color: #ea580c;">
                                                            <span style="font-size:12px;">💸</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @endif
                    @php $groupIndex++; @endphp
                @endforeach
            </tbody>
        </table>
    @endif
@endif
