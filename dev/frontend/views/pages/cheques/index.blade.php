@extends('layouts.app')

@section('title', 'Cheques a Receber e Compensados')
@section('body_class', 'contas-receber-page')

@section('content')
<div class="cr-wrapper">
    <!-- Barra de Ações -->
    <div class="cr-header-actions">
        <div class="header-title-section">
            <h2>Gestão de Cheques</h2>
            <p>Gerencie cheques pendentes e compensados</p>
        </div>
    </div>

    @if (session('error_message'))
        <div class="flash-message flash-danger">
            <span>{{ session('error_message') }}</span>
            <button class="flash-close" onclick="this.parentElement.remove()">✕</button>
        </div>
    @endif
    @if (session('success_message'))
        <div class="flash-message flash-success">
            <span>{{ session('success_message') }}</span>
            <button class="flash-close" onclick="this.parentElement.remove()">✕</button>
        </div>
    @endif

    <!-- Abas Principais -->
    <div class="tabs" id="chequesTabs">
        <a href="#" class="tab active" data-target="pendentes">
            Pendentes (A Receber) <span class="tab-count">{{ count($pendentes) }}</span>
        </a>
        <a href="#" class="tab" data-target="compensados">
            Compensados <span class="tab-count">{{ count($compensados) }}</span>
        </a>
    </div>

    <div class="tab-content" id="chequesTabsContent">
        <!-- PENDENTES -->
        <div class="tab-pane active show" id="pendentes" style="display: block;">
            <div class="cr-table-wrapper">
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th>Pedido / Venc.</th>
                            <th>Cliente</th>
                            <th>Colaborador</th>
                            <th>Data da Baixa</th>
                            <th class="text-right">Valor do Cheque</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendentes as $cheque)
                            <tr>
                                <td>
                                    <strong>{{ $cheque->NUM_PEDIDO }}</strong><br>
                                    <span class="text-xs text-muted">Venc: {{ date('d/m/Y', strtotime($cheque->DATA_VENCIMENTO)) }}</span>
                                </td>
                                <td>{{ $cheque->CLIENTE ?? 'N/A' }}</td>
                                <td>{{ $cheque->NOME_COLABORADOR ?? 'N/A' }}</td>
                                <td>{{ date('d/m/Y', strtotime($cheque->DATA_REGISTRO)) }}</td>
                                <td class="text-right font-semibold">
                                    R$ {{ number_format($cheque->VALOR_PAGO_PEDIDO, 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('cheques-compensar', $cheque->ID_PEDIDO) }}" style="display:inline;" onsubmit="return confirm('Deseja dar este cheque como compensado no caixa?');">
                                        @csrf
                                        <button type="submit" style="display:inline-flex; align-items:center; gap:4px; padding: 4px 10px; border-radius: 4px; border: 1px solid #10b981; background: #ecfdf5; color: #047857; font-weight: 500; font-size: 0.85rem; cursor:pointer; transition: 0.2s;" onmouseover="this.style.background='#d1fae5'" onmouseout="this.style.background='#ecfdf5'">
                                            <x-icons.check width="14" height="14" />
                                            Compensar
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('cheques-devolver', $cheque->ID_DETALHE) }}" style="display:inline;" onsubmit="return confirm('ATENÇÃO: O cheque foi devolvido ou não foi pago?\nIsso vai excluir a baixa e o pedido voltará para cobrança. Deseja continuar?');">
                                        @csrf
                                        <button type="submit" style="display:inline-flex; align-items:center; gap:4px; margin-left:4px; padding: 4px 10px; border-radius: 4px; border: 1px solid #f87171; background: #fef2f2; color: #b91c1c; font-weight: 500; font-size: 0.85rem; cursor:pointer; transition: 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                                            <x-icons.icon-11 width="14" height="14" />
                                            Não Pago
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 24px; color: #64748b;">Nenhum cheque pendente de compensação.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- COMPENSADOS -->
        <div class="tab-pane" id="compensados" style="display: none;">
            <div class="cr-table-wrapper">
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th>Pedido / Venc.</th>
                            <th>Cliente</th>
                            <th>Colaborador</th>
                            <th>Data da Baixa</th>
                            <th class="text-right">Valor do Cheque</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($compensados as $cheque)
                            <tr style="opacity: 0.8;">
                                <td>
                                    <strong>{{ $cheque->NUM_PEDIDO }}</strong><br>
                                    <span class="text-xs text-muted">Venc: {{ date('d/m/Y', strtotime($cheque->DATA_VENCIMENTO)) }}</span>
                                </td>
                                <td>{{ $cheque->CLIENTE ?? 'N/A' }}</td>
                                <td>{{ $cheque->NOME_COLABORADOR ?? 'N/A' }}</td>
                                <td>{{ date('d/m/Y', strtotime($cheque->DATA_REGISTRO)) }}</td>
                                <td class="text-right font-semibold">
                                    R$ {{ number_format($cheque->VALOR_PAGO_PEDIDO, 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="status-badge badge-success" style="font-size:0.7rem; margin-right: 8px;">Compensado</span>
                                    <form method="POST" action="{{ route('cheques-descompensar', $cheque->ID_PEDIDO) }}" style="display:inline;" onsubmit="return confirm('Deseja descompensar este cheque? O valor dele voltará para Pendente no ranking.');">
                                        @csrf
                                        <button type="submit" style="display:inline-flex; align-items:center; gap:4px; padding: 4px 10px; border-radius: 4px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 500; font-size: 0.85rem; cursor:pointer; transition: 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f8fafc'">
                                            <x-icons.icon-14 width="14" height="14" />
                                            Descompensar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 24px; color: #64748b;">Nenhum cheque compensado ainda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('#chequesTabs .tab');
    const tabPanes = document.querySelectorAll('#chequesTabsContent .tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            // Remove active from all
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => {
                p.classList.remove('active', 'show');
                p.style.display = 'none';
            });

            // Add active to clicked
            this.classList.add('active');
            const targetId = this.getAttribute('data-target');
            const targetPane = document.getElementById(targetId);
            if(targetPane) {
                targetPane.classList.add('active', 'show');
                targetPane.style.display = 'block';
            }
        });
    });
});
</script>
@endsection
