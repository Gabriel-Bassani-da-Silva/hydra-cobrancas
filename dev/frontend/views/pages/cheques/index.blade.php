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
        <a href="#" class="tab active" data-target="pendentes" style="color: #ea580c;">
            Pendentes (A Receber) <span class="tab-count" style="background: #ea580c; color: white;">{{ count($pendentes) }}</span>
        </a>
        <a href="#" class="tab" data-target="compensados" style="color: #059669;">
            Compensados <span class="tab-count" style="background: #059669; color: white;">{{ count($compensados) }}</span>
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
                                <td class="text-right font-semibold" style="color: #ea580c;">
                                    R$ {{ number_format($cheque->VALOR_PAGO_PEDIDO, 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('cheques-compensar', $cheque->ID_PEDIDO) }}" style="display:inline;" onsubmit="return confirm('Deseja dar este cheque como compensado no caixa?');">
                                        @csrf
                                        <button type="submit" class="btn-action-icon-sm" title="Compensar Cheque" style="color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 4px; padding: 4px 8px; font-weight: 500; cursor: pointer;">
                                            Compensar
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('cheques-devolver', $cheque->ID_DETALHE) }}" style="display:inline;" onsubmit="return confirm('ATENÇÃO: O cheque foi devolvido ou não foi pago?\nIsso vai excluir a baixa e o pedido voltará para cobrança. Deseja continuar?');">
                                        @csrf
                                        <button type="submit" class="btn-action-icon-sm" title="Cheque Devolvido/Não Pago" style="color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; padding: 4px 8px; font-weight: 500; cursor: pointer; margin-left: 4px;">
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
                                <td class="text-right font-semibold" style="color: #059669;">
                                    R$ {{ number_format($cheque->VALOR_PAGO_PEDIDO, 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span style="background: #059669; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Compensado</span>
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
