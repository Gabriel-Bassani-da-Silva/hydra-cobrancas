@extends('layouts.app')

@section('title', 'Meu Perfil')
@section('body_class', 'perfil-page')

@section('content')
<?php
$title = "Meu Perfil";
$body_class = "perfil-page";
$show_header = true;



if (!function_exists('formatarCpfCnpj')) {
    function formatarCpfCnpj($doc) {
        if (!$doc) return '-';
        $doc = preg_replace('/\D/', '', $doc);
        if (strlen($doc) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
        } elseif (strlen($doc) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
        }
        return $doc ?: '-';
    }
}
?>
<div class="cr-wrapper">
    <div class="cr-header-actions">
        <div class="header-title-section">
            <h2>Meu Perfil</h2>
            <p>Acompanhe suas cobranças em andamento.</p>
        </div>
    </div>

    <!-- Barra de Busca -->
    <div class="cr-search-bar">
        <div class="search-input-wrapper">
            <x-icons.search width="18" height="18" class="search-icon" />
            <input type="text" id="cr-search" placeholder="Filtrar tabela por nome, documento ou valor..." autocomplete="off">
        </div>
    </div>

    <!-- Abas Principais -->
    <div class="tabs">
        <a href="{{ route('perfil-page') }}?aba=clientes" class="tab <?= $aba === 'clientes' ? 'active' : '' ?>">
            Clientes <span class="tab-count"><?= count($cobrancasClientes ?? []) ?></span>
        </a>
        <a href="{{ route('perfil-page') }}?aba=representantes" class="tab <?= $aba === 'representantes' ? 'active' : '' ?>">
            Representantes <span class="tab-count"><?= count($cobrancasRepresentantes ?? []) ?></span>
        </a>
        <a href="{{ route('perfil-page') }}?aba=baixas" class="tab <?= $aba === 'baixas' ? 'active' : '' ?>">
            Minhas Baixas <span class="tab-count"><?= count($minhasBaixas ?? []) ?></span>
        </a>
    </div>

    <?php if ($aba === 'baixas'): ?>
    <div class="card" style="margin-top: 1.5rem;">
        <div class="table-responsive">
            <table class="cr-table" id="table-minhas-baixas">
                <thead>
                    <tr>
                        <th>Data do Registro</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th class="valor-col">Valor Baixado</th>
                        <th class="cr-col-acoes">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($minhasBaixas)): ?>
                        <tr><td colspan="5" class="text-center">Você não possui nenhuma baixa manual.</td></tr>
                    <?php else: ?>
                        <?php foreach ($minhasBaixas as $b): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($b->DATA_REGISTRO)) ?></td>
                                <td><?= htmlspecialchars($b->NUM_PEDIDO) ?></td>
                                <td><?= htmlspecialchars($b->NOME_CONTATO) ?></td>
                                <td class="valor-col">
                                    <span class="valor-baixa-display" id="valor-display-<?= $b->ID_DETALHE ?>">R$ <?= number_format($b->VALOR_PAGO_PEDIDO, 2, ',', '.') ?></span>
                                    <input type="number" step="0.01" min="0" class="input-edit-baixa" id="input-baixa-<?= $b->ID_DETALHE ?>" value="<?= number_format($b->VALOR_PAGO_PEDIDO, 2, '.', '') ?>" style="display:none; width: 100px; padding: 4px; text-align:right;">
                                </td>
                                <td class="cr-col-acoes">
                                    <button class="btn-acao btn-edit" id="btn-edit-<?= $b->ID_DETALHE ?>" onclick="editarBaixa(<?= $b->ID_DETALHE ?>)" title="Editar Baixa">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </button>
                                    <button class="btn-acao btn-save" id="btn-save-<?= $b->ID_DETALHE ?>" onclick="salvarBaixa(<?= $b->ID_DETALHE ?>)" title="Salvar" style="display:none; color: #16a34a;">
                                        <x-icons.check width="16" height="16" />
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
    </div>
    <?php else: ?>

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
                                    <span class="text-doc"><?= htmlspecialchars(formatarCpfCnpj($cob['CLIENTES'][0]['NUMERO_DOCUMENTO'] ?? '')) ?></span>
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
                                    @endif
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
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL DE DETALHES -->
<div id="modal-detalhes" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay"></div>
    <div class="cr-modal-container cr-modal-container--wide">
        <div class="cr-modal-header">
            <h3 class="cr-modal-title" id="modal-detalhes-title">Pedidos da Cobrança</h3>
            <button class="cr-modal-close" id="modal-detalhes-close">&times;</button>
        </div>
        <div class="cr-modal-body" id="modal-detalhes-body">
            <!-- Conteúdo carregado via JS -->
        </div>
    </div>
</div>

@include('components.modal_baixa_manual')

<script>const BASE_URL = "{{ url('/') }}";</script>
<script src="{{ asset('js/baixa_manual.js') }}?v=<?= time() ?>"></script>
<script src="{{ asset('js/perfil.js') }}?v=<?= time() ?>"></script>

@endsection
