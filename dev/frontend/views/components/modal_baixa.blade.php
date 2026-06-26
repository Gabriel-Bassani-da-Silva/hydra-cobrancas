<!-- MODAL UNIFICADO DE BAIXA -->
<div id="modal-baixa" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay"></div>
    <div class="cr-modal-container modal-container-md">
        <div class="cr-modal-header">
            <h3 class="cr-modal-title" id="modal-baixa-title">Baixa</h3>
            <button class="cr-modal-close" onclick="fecharModalBaixa()">&times;</button>
        </div>
        <div class="cr-modal-body" id="modal-baixa-body">
            <!-- conteúdo dinâmico será inserido via JS (tabela de parcelas ou detalhes) -->
        </div>
        <div class="modal-actions-between">
            <div class="modal-actions" style="gap:15px;">
                <div>
                    <span class="baixa-summary-label">Total a Baixar</span>
                    <strong id="baixa-total-display" class="baixa-summary-value">R$ 0,00</strong>
                </div>
                <div class="baixa-divider"></div>
                <button class="btn-modal-select-all" onclick="baixarTodasParcelas()">Baixar Todos</button>
            </div>
            <div class="modal-actions">
                <button class="btn-modal-cancel" onclick="fecharModalBaixa()">Cancelar</button>
                <button class="btn-modal-confirm-blue" onclick="confirmarBaixa()">
                    <x-icons.check-heavy width="16" height="16" /> Confirmar Baixa
                </button>
            </div>
        </div>
    </div>
</div>
