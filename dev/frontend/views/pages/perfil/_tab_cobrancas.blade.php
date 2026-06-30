<!-- Sub-Abas para Clientes e Representantes -->
<div class="cr-sub-tabs">
    <a href="{{ route('perfil-page') }}?aba=<?= $aba ?>&grupo=padrao" class="sub-tab-btn <?= $grupo === 'padrao' ? 'active' : '' ?>">
        <?= ucfirst($aba) ?>
    </a>
    <a href="{{ route('perfil-page') }}?aba=<?= $aba ?>&grupo=financeiro" class="sub-tab-btn <?= $grupo === 'financeiro' ? 'active' : '' ?>">
        Contatos Financeiros <span class="sub-tab-count">(<?= count($cobrancasFinanceiros ?? []) ?>)</span>
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="cr-table" id="table-minhas-cobrancas">
            <thead>
                <tr>
                    <th></th>
                    <th>Iniciada em</th>
                    <th><?= ($aba === 'clientes' && $grupo === 'padrao') ? 'Nome' : 'Tipo / Agrupamento' ?></th>
                    <?php if ($aba === 'clientes' && $grupo === 'padrao'): ?>
                    <th>CNPJ/CPF</th>
                    @endif
                    <?php if ($aba !== 'clientes' || $grupo !== 'padrao'): ?>
                    <th>Clientes Envolvidos</th>
                    @endif
                    <th class="center-col">Qtd. Pedidos Pendentes</th>
                    <th class="valor-col">Total da Dívida</th>
                    <th>Status</th>
                    <th class="cr-col-acoes cr-col-acoes-wide">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($minhasCobrancas)): ?>
                    <tr><td colspan="8" class="text-center">Você não possui nenhuma cobrança em andamento.</td></tr>
                <?php else: ?>
                    <?php foreach ($minhasCobrancas as $cob): 
                        $tipo = '';
                        $nomeAgrupamento = '';
                        if ($cob['ID_CONTATO']) {
                            $tipoStr = 'financeiros';
                            $tipo = 'Contato Financeiro';
                            $nomeAgrupamento = $cob['NOME_FINANCEIRO'];
                        } elseif ($cob['ID_REPRESENTANTE']) {
                            $tipoStr = 'representantes';
                            $tipo = 'Representante';
                            $nomeAgrupamento = $cob['NOME_REPRESENTANTE'];
                        } else {
                            $tipoStr = 'clientes';
                            $tipo = 'Cliente Direto';
                            // Pega o nome do primeiro cliente se houver
                            $nomeAgrupamento = $cob['CLIENTES'][0]['NOME_CONTATO'] ?? 'Cliente';
                        }
                    ?>
                        <tr class="clickable-row" data-id="<?= $cob['ID_COBRANCA'] ?>" data-nome="<?= htmlspecialchars(addslashes($nomeAgrupamento)) ?>" data-tipo="<?= $tipoStr ?>">
                            <td class="expand-col">
                                <button class="btn-expand" onclick="toggleDetalhesPerfil(this, <?= $cob['ID_COBRANCA'] ?>, '<?= htmlspecialchars(addslashes($nomeAgrupamento)) ?>', '<?= $tipoStr ?>')">
                                    <x-icons.icon-29 width="16" height="16" />
                                </button>
                            </td>
                            <td><?= date('d/m/Y', strtotime($cob['DATA_INICIO'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($nomeAgrupamento) ?></strong>
                                <?php if ($aba !== 'clientes' || $grupo !== 'padrao'): ?>
                                <br><small class="text-muted-sm"><?= $tipo ?></small>
                                @endif
                            </td>
                            <?php if ($aba === 'clientes' && $grupo === 'padrao'): ?>
                            <td>
                                <span class="text-doc"><?= htmlspecialchars(\App\Helpers\FormatHelper::document($cob['CLIENTES'][0]['NUMERO_DOCUMENTO'] ?? '')) ?></span>
                            </td>
                            @endif
                            <?php if ($aba !== 'clientes' || $grupo !== 'padrao'): ?>
                            <td>
                                <?php if ($tipo === 'Cliente Direto'): ?>
                                    <small>1 Cliente Selecionado</small>
                                <?php else: ?>
                                    <div class="dropdown-hover">
                                        <span class="dropdown-hover-trigger">
                                            <?= count($cob['CLIENTES']) ?> Cliente(s)
                                        </span>
                                        <div class="dropdown-content">
                                            <ul class="dropdown-list">
                                                <?php foreach ($cob['CLIENTES'] as $cli): ?>
                                                    <li>&bull; <?= htmlspecialchars($cli['NOME_CONTATO']) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            @endif
                            <td class="center-col">
                                <span class="text-bold-value"><?= $cob['QTD_PEDIDOS'] ?></span>
                            </td>
                            <td class="valor-col fw-600">
                                R$ <?= number_format($cob['TOTAL_DIVIDA'] ?? 0, 2, ',', '.') ?>
                            </td>
                            <td>
                                <span class="badge-status-atendimento">
                                    <?= htmlspecialchars($cob['STATUS_ATENDIMENTO'] ?? 'Iniciado') ?>
                                </span>
                            </td>
                            <td class="cr-col-acoes">
                                <div class="action-menu-wrapper">
                                    <button class="btn-options" onclick="toggleAcoesMenu(this, event)" title="Opções">
                                        <x-icons.icon-30 width="18" height="18" />
                                    </button>
                                    <div class="action-menu-dropdown">
                                        <button class="action-menu-item action-menu-item--default btn-atualizar-cob" data-id="<?= $cob['ID_COBRANCA'] ?>" title="Sincroniza os pedidos">
                                            <x-icons.icon-31 width="14" height="14" />
                                            Atualizar Pedidos
                                        </button>
                                        <button class="action-menu-item action-menu-item--danger btn-desistir-cob" data-id="<?= $cob['ID_COBRANCA'] ?>" title="Desistir desta cobrança">
                                            <x-icons.icon-32 width="14" height="14" />
                                            Desistir
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
