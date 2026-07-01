<!-- MODAL DE BAIXA MANUAL -->
<div id="modal-baixa-manual" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay"></div>
    <div class="cr-modal-container modal-container-md">
        <div class="cr-modal-header">
            <h3 class="cr-modal-title">Registrar Baixa Manual</h3>
            <button class="cr-modal-close" onclick="fecharModalBaixa()">&times;</button>
        </div>
        <div class="cr-modal-body">
            @php
                $colaboradoresDb = \Illuminate\Support\Facades\DB::table('COLABORADOR')->orderBy('NOME_COLABORADOR')->get();
                $meuId = auth()->user()->ID_COLABORADOR ?? 0;
            @endphp
            <div style="margin-bottom: 15px;">
                <label for="modal-colaborador-select" style="font-size: 0.85rem; color: #475569; font-weight: 600; margin-bottom: 4px; display: block;">Vincular Baixa a:</label>
                <select id="modal-colaborador-select" class="cr-input" style="width: 100%; padding: 8px;">
                    @foreach($colaboradoresDb as $colab)
                        <option value="{{ $colab->ID_COLABORADOR }}" {{ $colab->ID_COLABORADOR == $meuId ? 'selected' : '' }}>
                            {{ $colab->NOME_COLABORADOR }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div id="baixa-parcelas-container" class="baixa-container">
                <!-- Preenchido via JS -->
            </div>

            <div class="modal-actions-between">
                <div class="modal-actions" style="gap:15px;">
                    <div>
                        <span class="baixa-summary-label">Total a Baixar</span>
                        <strong id="baixa-total-display" class="baixa-summary-value">R$ 0,00</strong>
                    </div>
                    <div class="baixa-divider"></div>
                    <button class="btn-modal-select-all" onclick="baixarTodasParcelas()">
                        Baixar Todos
                    </button>
                </div>
                <div class="modal-actions">
                    <button class="btn-modal-cancel" onclick="fecharModalBaixa()">
                        Cancelar
                    </button>
                    <button class="btn-modal-confirm-blue" onclick="confirmarBaixa()">
                        <x-icons.check-heavy width="16" height="16" />
                        Confirmar Baixa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
