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
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18" class="search-icon">
                <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
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
    </div>

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
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
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
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                                <line x1="3" y1="18" x2="21" y2="18"></line>
                                            </svg>
                                        </button>
                                        <div class="action-menu-dropdown">
                                            <button class="action-menu-item action-menu-item--default btn-atualizar-cob" data-id="<?= $cob['ID_COBRANCA'] ?>" title="Sincroniza os pedidos">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                                    <path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
                                                </svg>
                                                Atualizar Pedidos
                                            </button>
                                            <button class="action-menu-item action-menu-item--danger btn-desistir-cob" data-id="<?= $cob['ID_COBRANCA'] ?>" title="Desistir desta cobrança">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
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

<script>const BASE_URL = "{{ url('/') }}";</script>
<script src="{{ asset('js/perfil.js') }}?v=<?= time() ?>"></script>

@endsection
