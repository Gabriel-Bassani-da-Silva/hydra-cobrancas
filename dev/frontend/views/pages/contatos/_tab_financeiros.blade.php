{{-- _tab_financeiros.blade.php --}}
<div class="cf-section">
    <div class="card">
        <div class="table-filters">
            <div class="search-box">
                <x-icons.search-circle width="18" height="18" />
                <input type="text" id="search-table" placeholder="Buscar por nome, documento ou telefone...">
            </div>
            
            <div class="filters-group">
                <button class="btn btn-primary" onclick="openNewCFModal()">
                    <x-icons.plus width="16" height="16" />
                    Novo Contato
                </button>
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
                        <td class="tel-num"><?= htmlspecialchars(App\Helpers\FormatHelper::phone($cf['NUM_TEL'])) ?></td>
                        <td><span class="vinculos-text"><?= htmlspecialchars($cf['VINCULOS'] ?? 'Sem vínculo') ?></span></td>
                        <td class="actions-col">
                            <div class="dropdown">
                                <button type="button" class="btn-icon dropdown-toggle" onclick="toggleDropdown(event, this)" title="Opções">
                                    <x-icons.icon-23 width="16" height="16" />
                                </button>
                                <div class="dropdown-menu">
                                    <button class="dropdown-item btn-edit-cf" data-id="<?= $cf['ID_CONTATO'] ?>" data-nome="<?= htmlspecialchars($cf['NOME_CF'], ENT_QUOTES, 'UTF-8') ?>" data-tel="<?= htmlspecialchars(App\Helpers\FormatHelper::phone($cf['NUM_TEL']), ENT_QUOTES, 'UTF-8') ?>" data-vinculos="<?= htmlspecialchars($cf['VINCULOS_RAW'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
