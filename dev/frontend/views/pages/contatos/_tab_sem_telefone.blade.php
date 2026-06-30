{{-- _tab_sem_telefone.blade.php --}}
<div class="card">
    <div class="table-filters">
        <div class="search-box">
            <x-icons.search-circle width="18" height="18" />
            <input type="text" id="search-table" placeholder="Buscar por nome ou documento...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="phone-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF/CNPJ</th>
                </tr>
            </thead>
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
                    <td class="doc-col"><?= App\Helpers\FormatHelper::document($s['NUMERO_DOCUMENTO'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
