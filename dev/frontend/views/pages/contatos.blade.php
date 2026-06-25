@extends('layouts.app')

@section('title', 'Contatos — Telefones')
@section('body_class', 'contatos-page')

@section('content')
<?php
$title = "Contatos — Telefones";
$body_class = "contatos-page";
$show_header = true;

// Função helper para formatar telefone
function formatPhone($num) {
    $num = preg_replace('/\D/', '', $num);
    if (strlen($num) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $num);
    } elseif (strlen($num) === 10) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $num);
    }
    return $num;
}



// Helper para formatar CPF/CNPJ
$formatDoc = function($doc) {
    if (!$doc) return '';
    $doc = preg_replace('/\D/', '', $doc);
    if (strlen($doc) === 11) return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
    if (strlen($doc) === 14) return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
    return $doc;
};
?>
<div class="contatos-wrapper">

    <!-- Barra de Ações do Bling -->
    <div class="contatos-header-actions">
        <div class="header-title-section">
            <h2>Gerenciamento de Contatos</h2>
            <p>Gerencie telefones, contatos financeiros e sincronize dados com o Bling</p>
        </div>
        <div class="actions-buttons">
            <a href="{{ route('importar-contatos-page') }}" id="btn-importar-contatos" class="btn-sync" title="Importar contatos">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Importar Contatos
            </a>
            <a href="{{ route('sincronizar-contatos-bling') }}?aba=<?= $aba ?>" class="btn-sync" title="Atualizar contatos no Bling">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                Sincronizar Contatos
            </a>
            <a href="{{ route('sincronizar-vendedores-bling') }}?aba=<?= $aba ?>" class="btn-sync secondary" title="Atualizar vendedores no Bling">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                Sincronizar Vendedores
            </a>
            
            <div id="filtro-inadimplentes-toggle" class="filtro-cobranca-toggle" style="margin-left: 10px;">
                <button class="filtro-cob-btn <?= request('inadimplentes') != '1' ? 'filtro-cob-btn--active' : '' ?>" onclick="setInadimplentesFilter('0')">
                    Mostrar Todos
                </button>
                <button class="filtro-cob-btn <?= request('inadimplentes') == '1' ? 'filtro-cob-btn--active' : '' ?>" onclick="setInadimplentesFilter('1')">
                    <?= request('inadimplentes') == '1' ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>' : '' ?>
                    Apenas Inadimplentes
                </button>
            </div>
        </div>
    </div>

    <!-- Mensagens Flash -->
    @if(session('flash_msg'))
        <div class="flash-message">
            <span>{{ session('flash_msg') }}</span>
            <button class="flash-close" onclick="this.parentElement.remove()">✕</button>
        </div>
        
    @endif

    <!-- Abas -->
    <div class="tabs">
        <?php 
            function getTabUrl($tabName) {
                $params = array_merge(request()->query(), ['aba' => $tabName]);
                return route('contatos-page') . '?' . http_build_query($params);
            }
        ?>
        <a href="<?= getTabUrl('clientes') ?>" class="tab <?= $aba === 'clientes' ? 'active' : '' ?>">
            Clientes <span class="tab-count"><?= count($clientes) ?></span>
        </a>
        <a href="<?= getTabUrl('representantes') ?>" class="tab <?= $aba === 'representantes' ? 'active' : '' ?>">
            Representantes <span class="tab-count"><?= count($representantes) ?></span>
        </a>
        <a href="<?= getTabUrl('sem-telefone') ?>" class="tab <?= $aba === 'sem-telefone' ? 'active' : '' ?>">
            Sem Telefone <span class="tab-count"><?= count($semTelefone) ?></span>
        </a>
        <a href="<?= getTabUrl('financeiros') ?>" class="tab <?= $aba === 'financeiros' ? 'active' : '' ?>">
            Contatos Financeiros <span class="tab-count"><?= count($contatosFinanceiros) ?></span>
        </a>
    </div>

    <!-- ═══ ABA: CLIENTES ═══ -->
    <?php if ($aba === 'clientes'): ?>
    <div class="card">
        <div class="table-filters">
            <div class="search-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="search-table" placeholder="Buscar por nome, documento ou telefone...">
            </div>
            
            <div class="filters-group">
                <button type="button" class="toggle-checkbox-wrapper" style="border: 1px solid #94a3b8; background: <?= request('com_telefone') == '1' ? '#e2e8f0' : 'transparent' ?>; cursor: pointer; transition: all 0.2s;" onclick="toggleTableFilter('com_telefone')">
                    <span style="width: 14px; height: 14px; border-radius: 3px; border: 1px solid #64748b; background-color: <?= request('com_telefone') == '1' ? '#3b82f6' : 'transparent' ?>; display: inline-flex; align-items: center; justify-content: center; margin-right: 4px;">
                        <?= request('com_telefone') == '1' ? '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' : '' ?>
                    </span>
                    Apenas com telefone
                </button>
                
                <div style="display: flex; align-items: center; gap: 8px; margin-left: 10px; padding-left: 15px; border-left: 2px solid #e2e8f0;">
                    <button type="button" class="toggle-checkbox-wrapper" style="border: 1px solid #16a34a; background: <?= request('com_confirmado') == '1' ? '#16a34a' : 'transparent' ?>; color: <?= request('com_confirmado') == '1' ? '#fff' : '#16a34a' ?>; cursor: pointer; transition: all 0.2s;" onclick="toggleTableFilter('com_confirmado', 'com_tentativa')">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?= request('com_confirmado') == '1' ? '#fff' : '#16a34a' ?>; display: inline-block;"></span>
                        Confirmados
                    </button>
                    
                    <button type="button" class="toggle-checkbox-wrapper" style="border: 1px solid #ca8a04; background: <?= request('com_tentativa') == '1' ? '#ca8a04' : 'transparent' ?>; color: <?= request('com_tentativa') == '1' ? '#fff' : '#ca8a04' ?>; cursor: pointer; transition: all 0.2s;" onclick="toggleTableFilter('com_tentativa', 'com_confirmado')">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?= request('com_tentativa') == '1' ? '#fff' : '#ca8a04' ?>; display: inline-block;"></span>
                        Tentativas
                    </button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="phone-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF/CNPJ</th>
                        <th>Telefones (Bling)</th>
                        <th>Telefone Manual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr><td colspan="5" class="empty-msg">Nenhum cliente encontrado.</td></tr>
                    @endif
                    <?php foreach ($clientes as $c): ?>
                    <tr class="c-table-row clickable-row" onclick="openManagePhonesModal('<?= $c['ID_CONTATO_BLING'] ?>', '<?= htmlspecialchars($c['NOME_CONTATO'], ENT_QUOTES) ?>', 'clientes')" title="Gerenciar Telefones">
                        <td class="nome-col">
                            <div class="nome-container">
                                <?= htmlspecialchars($c['NOME_CONTATO']) ?>
                            </div>
                            <div style="display: none;" id="phones-data-<?= $c['ID_CONTATO_BLING'] ?>"><?= htmlspecialchars(json_encode($c['telefones_arr']), ENT_QUOTES) ?></div>
                        </td>
                        <td class="doc-col"><?= $formatDoc($c['NUMERO_DOCUMENTO'] ?? '') ?></td>
                        <td>
                            <?php 
                            $blingTels = array_filter($c['telefones_arr'], fn($t) => $t['origem'] === 'bling');
                            if (empty($blingTels)): ?>
                                <span class="no-phone">Sem telefone</span>
                            <?php else: ?>
                                <div class="tel-list-container">
                                <?php foreach ($blingTels as $t): ?>
                                    <div class="tel-item simplified">
                                        <span class="tel-num <?= $t['confirmado'] ? 'confirmed-text' : 'attempt-text' ?>"><?= htmlspecialchars(formatPhone($t['num'])) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            @endif
                        </td>
                        <td>
                            <?php 
                            $manualTels = array_filter($c['telefones_arr'], fn($t) => $t['origem'] === 'manual');
                            if (empty($manualTels)): ?>
                                <span class="no-phone">Sem telefone</span>
                            <?php else: ?>
                                <div class="tel-list-container">
                                <?php foreach ($manualTels as $t): ?>
                                    <div class="tel-item simplified">
                                        <span class="tel-num <?= $t['confirmado'] ? 'confirmed-text' : 'attempt-text' ?>"><?= htmlspecialchars(formatPhone($t['num'])) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            @endif
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- ═══ ABA: REPRESENTANTES ═══ -->
    <?php if ($aba === 'representantes'): ?>
    <div class="card">
        <div class="table-filters">
            <div class="search-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="search-table" placeholder="Buscar por nome, documento ou telefone...">
            </div>
            
            <div class="filters-group">
                <button type="button" class="toggle-checkbox-wrapper" style="border: 1px solid #94a3b8; background: <?= request('com_telefone') == '1' ? '#e2e8f0' : 'transparent' ?>; cursor: pointer; transition: all 0.2s;" onclick="toggleTableFilter('com_telefone')">
                    <span style="width: 14px; height: 14px; border-radius: 3px; border: 1px solid #64748b; background-color: <?= request('com_telefone') == '1' ? '#3b82f6' : 'transparent' ?>; display: inline-flex; align-items: center; justify-content: center; margin-right: 4px;">
                        <?= request('com_telefone') == '1' ? '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' : '' ?>
                    </span>
                    Apenas com telefone
                </button>
                
                <div style="display: flex; align-items: center; gap: 8px; margin-left: 10px; padding-left: 15px; border-left: 2px solid #e2e8f0;">
                    <button type="button" class="toggle-checkbox-wrapper" style="border: 1px solid #16a34a; background: <?= request('com_confirmado') == '1' ? '#16a34a' : 'transparent' ?>; color: <?= request('com_confirmado') == '1' ? '#fff' : '#16a34a' ?>; cursor: pointer; transition: all 0.2s;" onclick="toggleTableFilter('com_confirmado', 'com_tentativa')">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?= request('com_confirmado') == '1' ? '#fff' : '#16a34a' ?>; display: inline-block;"></span>
                        Confirmados
                    </button>
                    
                    <button type="button" class="toggle-checkbox-wrapper" style="border: 1px solid #ca8a04; background: <?= request('com_tentativa') == '1' ? '#ca8a04' : 'transparent' ?>; color: <?= request('com_tentativa') == '1' ? '#fff' : '#ca8a04' ?>; cursor: pointer; transition: all 0.2s;" onclick="toggleTableFilter('com_tentativa', 'com_confirmado')">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?= request('com_tentativa') == '1' ? '#fff' : '#ca8a04' ?>; display: inline-block;"></span>
                        Tentativas
                    </button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="phone-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF/CNPJ</th>
                        <th>Telefones (Bling)</th>
                        <th>Telefone Manual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($representantes)): ?>
                        <tr><td colspan="5" class="empty-msg">Nenhum representante encontrado.</td></tr>
                    @endif
                    <?php foreach ($representantes as $r): ?>
                    <tr class="c-table-row clickable-row" onclick="openManagePhonesModal('<?= $r['ID_CONTATO_BLING'] ?>', '<?= htmlspecialchars($r['NOME_CONTATO'], ENT_QUOTES) ?>', 'representantes')" title="Gerenciar Telefones">
                        <td class="nome-col">
                            <div class="nome-container">
                                <?= htmlspecialchars($r['NOME_CONTATO']) ?>
                            </div>
                            <div style="display: none;" id="phones-data-<?= $r['ID_CONTATO_BLING'] ?>"><?= htmlspecialchars(json_encode($r['telefones_arr']), ENT_QUOTES) ?></div>
                        </td>
                        <td class="doc-col"><?= $formatDoc($r['NUMERO_DOCUMENTO'] ?? '') ?></td>
                        <td>
                            <?php 
                            $blingTels = array_filter($r['telefones_arr'], fn($t) => $t['origem'] === 'bling');
                            if (empty($blingTels)): ?>
                                <span class="no-phone">Sem telefone</span>
                            <?php else: ?>
                                <div class="tel-list-container">
                                <?php foreach ($blingTels as $t): ?>
                                    <div class="tel-item simplified">
                                        <span class="tel-num <?= $t['confirmado'] ? 'confirmed-text' : 'attempt-text' ?>"><?= htmlspecialchars(formatPhone($t['num'])) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            @endif
                        </td>
                        <td>
                            <?php 
                            $manualTels = array_filter($r['telefones_arr'], fn($t) => $t['origem'] === 'manual');
                            if (empty($manualTels)): ?>
                                <span class="no-phone">Sem telefone</span>
                            <?php else: ?>
                                <div class="tel-list-container">
                                <?php foreach ($manualTels as $t): ?>
                                    <div class="tel-item simplified">
                                        <span class="tel-num <?= $t['confirmado'] ? 'confirmed-text' : 'attempt-text' ?>"><?= htmlspecialchars(formatPhone($t['num'])) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            @endif
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- ═══ ABA: SEM TELEFONE ═══ -->
    <?php if ($aba === 'sem-telefone'): ?>
    <div class="card">
        <div class="table-filters">
            <div class="search-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="search-table" placeholder="Buscar por nome ou documento...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="phone-table">
                <thead><tr><th>Nome</th><th>CPF/CNPJ</th></tr></thead>
                <tbody>
                    <?php if (empty($semTelefone)): ?>
                        <tr><td colspan="2" class="empty-msg">Todos os clientes possuem telefone.</td></tr>
                    @endif
                    <?php foreach ($semTelefone as $s): ?>
                    <tr class="c-table-row clickable-row" onclick="openManagePhonesModal('<?= $s['ID_CONTATO_BLING'] ?>', '<?= htmlspecialchars($s['NOME_CONTATO'], ENT_QUOTES) ?>', 'sem-telefone')" title="Gerenciar Telefones">
                        <td class="nome-col">
                            <div class="nome-container">
                                <?= htmlspecialchars($s['NOME_CONTATO']) ?>
                            </div>
                            <div style="display: none;" id="phones-data-<?= $s['ID_CONTATO_BLING'] ?>">[]</div>
                        </td>
                        <td class="doc-col"><?= $formatDoc($s['NUMERO_DOCUMENTO'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- ═══ ABA: CONTATOS FINANCEIROS ═══ -->
    <?php if ($aba === 'financeiros'): ?>
    <div class="cf-section">
    <div class="cf-section">
        <!-- Tabela de contatos financeiros -->
        <div class="card">
            <div class="table-filters">
                <div class="search-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="search-table" placeholder="Buscar por nome, documento ou telefone...">
                </div>
                
                <div class="filters-group">
                    <button class="btn btn-primary" onclick="openNewCFModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Novo Contato
                    </button>
                    <button type="button" class="toggle-checkbox-wrapper" style="border: 1px solid #94a3b8; background: <?= request('com_telefone') == '1' ? '#e2e8f0' : 'transparent' ?>; cursor: pointer; transition: all 0.2s;" onclick="toggleTableFilter('com_telefone')">
                        <span style="width: 14px; height: 14px; border-radius: 3px; border: 1px solid #64748b; background-color: <?= request('com_telefone') == '1' ? '#3b82f6' : 'transparent' ?>; display: inline-flex; align-items: center; justify-content: center; margin-right: 4px;">
                            <?= request('com_telefone') == '1' ? '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' : '' ?>
                        </span>
                        Apenas com telefone
                    </button>
                    
                    <div style="display: flex; align-items: center; gap: 8px; margin-left: 10px; padding-left: 15px; border-left: 2px solid #e2e8f0;">
                        <button type="button" class="toggle-checkbox-wrapper" style="border: 1px solid #16a34a; background: <?= request('com_confirmado') == '1' ? '#16a34a' : 'transparent' ?>; color: <?= request('com_confirmado') == '1' ? '#fff' : '#16a34a' ?>; cursor: pointer; transition: all 0.2s;" onclick="toggleTableFilter('com_confirmado', 'com_tentativa')">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?= request('com_confirmado') == '1' ? '#fff' : '#16a34a' ?>; display: inline-block;"></span>
                            Confirmados
                        </button>
                        
                        <button type="button" class="toggle-checkbox-wrapper" style="border: 1px solid #ca8a04; background: <?= request('com_tentativa') == '1' ? '#ca8a04' : 'transparent' ?>; color: <?= request('com_tentativa') == '1' ? '#fff' : '#ca8a04' ?>; cursor: pointer; transition: all 0.2s;" onclick="toggleTableFilter('com_tentativa', 'com_confirmado')">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?= request('com_tentativa') == '1' ? '#fff' : '#ca8a04' ?>; display: inline-block;"></span>
                            Tentativas
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="phone-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Vinculado a</th>
                            <th class="actions-col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contatosFinanceiros)): ?>
                            <tr><td colspan="4" class="empty-msg">Nenhum contato financeiro cadastrado.</td></tr>
                        @endif
                        <?php foreach ($contatosFinanceiros as $cf): ?>
                        <tr>
                            <td class="nome-col"><div class="nome-container"><?= htmlspecialchars($cf['NOME_CF']) ?></div></td>
                            <td class="tel-num"><?= htmlspecialchars(formatPhone($cf['NUM_TEL'])) ?></td>
                            <td><span class="vinculos-text"><?= htmlspecialchars($cf['VINCULOS'] ?? 'Sem vínculo') ?></span></td>
                            <td class="actions-col">
                                <div class="dropdown">
                                    <button type="button" class="btn-icon dropdown-toggle" onclick="toggleDropdown(event, this)" title="Opções">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                            <line x1="3" y1="12" x2="21" y2="12"></line>
                                            <line x1="3" y1="6" x2="21" y2="6"></line>
                                            <line x1="3" y1="18" x2="21" y2="18"></line>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu">
                                        <button class="dropdown-item btn-edit-cf" data-id="<?= $cf['ID_CONTATO'] ?>" data-nome="<?= htmlspecialchars($cf['NOME_CF'], ENT_QUOTES, 'UTF-8') ?>" data-tel="<?= htmlspecialchars(formatPhone($cf['NUM_TEL']), ENT_QUOTES, 'UTF-8') ?>" data-vinculos="<?= htmlspecialchars($cf['VINCULOS_RAW'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                            ✎ Editar Contato
                                        </button>
                                        <form method="POST" action="{{ route('excluir-contato-financeiro') }}" class="dropdown-form" onsubmit="return confirm('Excluir este contato financeiro?')">
                                            <input type="hidden" name="id_contato_fin" value="<?= $cf['ID_CONTATO'] ?>">
                                            <input type="hidden" name="aba" value="financeiros">
                                            <button type="submit" class="dropdown-item">✕ Excluir</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>

<!-- ═══ MODAL: Adicionar/Editar Telefone ═══ -->
<div id="modal-tel" class="modal-overlay">
    <div class="modal-box">
        <h3 id="modal-tel-title">Adicionar Telefone</h3>
        <form method="POST" action="{{ route('salvar-telefone-contato') }}">
            <input type="hidden" name="action" id="tel-action" value="add">
            <input type="hidden" name="id_contato" id="tel-id-contato">
            <input type="hidden" name="id_tel" id="tel-id-tel">
            <input type="hidden" name="aba" id="tel-aba">
            <div class="form-group">
                <label for="tel-num">Número</label>
                <input type="text" name="num_tel" id="tel-num" required placeholder="(00) 00000-0000">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" onclick="closeModal('modal-tel')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ MODAL: Adicionar/Editar Contato Financeiro ═══ -->
<div id="modal-cf" class="modal-overlay">
    <div class="modal-box modal-large">
        <h3 id="cf-form-title">Novo Contato Financeiro</h3>
        <form method="POST" action="{{ route('salvar-contato-financeiro') }}" id="form-cf">
            <input type="hidden" name="id_contato_fin" id="cf-id-contato">
            <div class="cf-form-grid">
                <div class="form-group">
                    <label>Nome do Contato</label>
                    <input type="text" name="nome" required placeholder="Nome completo">
                </div>
                <div class="form-group autocomplete-wrapper">
                    <label>Telefone</label>
                    <input type="text" name="num_tel" id="cf-tel-input" required placeholder="Digite ou selecione" autocomplete="off">
                    <div class="autocomplete-dropdown" id="tel-dropdown"></div>
                </div>
                <div class="form-group autocomplete-wrapper multiselect-wrapper full-width">
                    <label>Vincular a</label>
                    <div class="vinculo-tipo-selector">
                        <label><input type="radio" name="tipo_vinculo" value="cliente" checked> Cliente</label>
                        <label><input type="radio" name="tipo_vinculo" value="representante"> Representante</label>
                    </div>
                    <div class="multiselect-tags" id="vinculos-tags"></div>
                    <input type="text" id="vinculos-input" placeholder="Buscar por nome ou CPF/CNPJ" autocomplete="off">
                    <div class="autocomplete-dropdown" id="vinculos-dropdown"></div>
                </div>
            </div>
            <div class="modal-actions mt-15">
                <button type="button" class="btn btn-cancel" onclick="closeModal('modal-cf'); resetCFForm();">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="cf-submit-btn">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ MODAL: Gerenciar Telefones ═══ -->
<div id="modal-manage-phones" class="modal-overlay">
    <div class="modal-box modal-large" style="max-width: 900px;">
        <h3 id="manage-phones-title" style="margin-bottom: 20px;">Telefones de [Nome]</h3>
        
        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div style="flex: 1; background: #fafbfc; border-radius: 10px; padding: 15px; border: 1px solid #e8eaf0;">
                <h4 style="font-size: 0.85rem; color: #6b7589; text-transform: uppercase; margin-bottom: 12px; border-bottom: 2px solid #e8eaf0; padding-bottom: 8px;">Bling</h4>
                <div id="manage-phones-bling" class="manage-list"></div>
            </div>
            <div style="flex: 1; background: #fafbfc; border-radius: 10px; padding: 15px; border: 1px solid #e8eaf0;">
                <h4 style="font-size: 0.85rem; color: #6b7589; text-transform: uppercase; margin-bottom: 12px; border-bottom: 2px solid #e8eaf0; padding-bottom: 8px;">Manual</h4>
                <div id="manage-phones-manual" class="manage-list"></div>
            </div>
        </div>

        <div class="modal-actions mt-15" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-add" id="manage-btn-add" style="background: #e6f4ea; color: #137333;">📞 Adicionar Telefone</button>
                <form id="manage-form-sync" method="POST" action="{{ route('sincronizar-contato-unico') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="id_contato" id="manage-sync-id">
                    <input type="hidden" name="aba" id="manage-sync-aba">
                    <button type="submit" class="btn btn-toggle" style="background: #e3f2fd; color: #1565c0;">🔄 Sincronizar Bling</button>
                </form>
            </div>
            <button type="button" class="btn btn-cancel" onclick="closeModalAndReload('modal-manage-phones')">Fechar</button>
        </div>
    </div>
</div>

<script>
    function toggleTableFilter(param, paramToRemove = null) {
        var u = new URL(window.location.href);
        if (u.searchParams.get(param) === '1') {
            u.searchParams.delete(param);
        } else {
            u.searchParams.set(param, '1');
            if (paramToRemove) {
                u.searchParams.delete(paramToRemove);
            }
        }
        window.location.href = u.toString();
    }
    function setInadimplentesFilter(val) {
        var u = new URL(window.location.href);
        if (val === '1') {
            u.searchParams.set('inadimplentes', '1');
        } else {
            u.searchParams.delete('inadimplentes');
        }
        window.location.href = u.toString();
    }
</script>
<script src="{{ asset('js/contatos.js') }}?v=<?= time() ?>"></script>
@endsection
