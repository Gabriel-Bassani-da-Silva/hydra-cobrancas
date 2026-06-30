{{-- _tab_clientes.blade.php --}}
<div class="card">
    <div class="table-filters">
        <div class="search-box">
            <x-icons.search-circle width="18" height="18" />
            <input type="text" id="search-table" placeholder="Buscar por nome, documento ou telefone...">
        </div>
        
        <div class="filters-group">
            <button type="button" class="toggle-checkbox-wrapper" style="border: 1px solid #94a3b8; background: <?= request('com_telefone') == '1' ? '#e2e8f0' : 'transparent' ?>; cursor: pointer; transition: all 0.2s;" onclick="toggleTableFilter('com_telefone')">
                <span style="width: 14px; height: 14px; border-radius: 3px; border: 1px solid #64748b; background-color: <?= request('com_telefone') == '1' ? '#3b82f6' : 'transparent' ?>; display: inline-flex; align-items: center; justify-content: center; margin-right: 4px;">
                    @if(request('com_telefone') == '1') <x-icons.check width="10" height="10" /> @endif
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
                    <td class="doc-col"><?= App\Helpers\FormatHelper::document($c['NUMERO_DOCUMENTO'] ?? '') ?></td>
                    <td>
                        <?php 
                        $blingTels = array_filter($c['telefones_arr'], fn($t) => $t['origem'] === 'bling');
                        if (empty($blingTels)): ?>
                            <span class="no-phone">Sem telefone</span>
                        <?php else: ?>
                            <div class="tel-list-container">
                            <?php foreach ($blingTels as $t): ?>
                                <div class="tel-item simplified">
                                    <span class="tel-num <?= $t['confirmado'] ? 'confirmed-text' : 'attempt-text' ?>"><?= htmlspecialchars(App\Helpers\FormatHelper::phone($t['num'])) ?></span>
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
                                    <span class="tel-num <?= $t['confirmado'] ? 'confirmed-text' : 'attempt-text' ?>"><?= htmlspecialchars(App\Helpers\FormatHelper::phone($t['num'])) ?></span>
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
