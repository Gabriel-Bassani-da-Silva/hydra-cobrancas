{{-- _modais.blade.php --}}

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

<!-- ═══ MODAL: Gerenciar Telefones do Cliente/Representante ═══ -->
<div id="modal-manage-phones" class="modal-overlay">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <h3>Gerenciar Telefones — <span id="mp-nome-contato"></span></h3>
            <button class="close-btn" onclick="closeModal('modal-manage-phones')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="manage-phones-actions">
                <button class="btn btn-primary" onclick="openAddPhoneModal()">
                    <x-icons.plus width="14" height="14" /> Adicionar Manual
                </button>
            </div>
            <div class="table-responsive">
                <table class="phone-table" id="mp-table">
                    <thead>
                        <tr>
                            <th>Telefone</th>
                            <th>Origem</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="mp-tbody">
                        <!-- JS vai preencher -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL: Adicionar/Editar Contato Financeiro ═══ -->
<div id="modal-cf" class="modal-overlay">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <h3 id="modal-cf-title">Novo Contato Financeiro</h3>
            <button class="close-btn" onclick="closeModal('modal-cf')">&times;</button>
        </div>
        <form method="POST" action="{{ route('salvar-contato-financeiro') }}" class="modal-body">
            <input type="hidden" name="id_contato_fin" id="cf-id">
            <input type="hidden" name="aba" value="financeiros">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="cf-nome">Nome</label>
                    <input type="text" name="nome" id="cf-nome" required>
                </div>
                <div class="form-group">
                    <label for="cf-tel">Telefone Principal</label>
                    <input type="text" name="telefone" id="cf-tel" required placeholder="(00) 00000-0000">
                </div>
            </div>

            <div class="form-group mt-3">
                <label>Vincular a Clientes/Representantes (Opcional)</label>
                <div class="search-box mb-2">
                    <x-icons.search-circle width="16" height="16" />
                    <input type="text" id="cf-search-vinculo" placeholder="Buscar para vincular...">
                </div>
                <div class="vinculos-list" id="cf-vinculos-list">
                    <!-- Lista de checkboxes gerada via JS -->
                </div>
            </div>

            <div class="modal-actions mt-4">
                <button type="button" class="btn btn-cancel" onclick="closeModal('modal-cf')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Contato</button>
            </div>
        </form>
    </div>
</div>
