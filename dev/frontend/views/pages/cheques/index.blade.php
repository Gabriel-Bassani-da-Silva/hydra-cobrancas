@extends('layouts.app')

@section('title', 'Cheques a Receber e Compensados')

@section('content')
<div class="cheques-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestão de Cheques</h2>
    </div>

    @if (session('error_message'))
        <div class="alert alert-danger">{{ session('error_message') }}</div>
    @endif
    @if (session('success_message'))
        <div class="alert alert-success">{{ session('success_message') }}</div>
    @endif

    <ul class="nav nav-tabs mb-4" id="chequesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="pendentes-tab" data-bs-toggle="tab" data-bs-target="#pendentes" type="button" role="tab" style="color: #ea580c;">
                Pendentes (A Receber) <span class="badge bg-warning text-dark">{{ count($pendentes) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="compensados-tab" data-bs-toggle="tab" data-bs-target="#compensados" type="button" role="tab" style="color: #059669;">
                Compensados <span class="badge bg-success">{{ count($compensados) }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="chequesTabsContent">
        <!-- PENDENTES -->
        <div class="tab-pane fade show active" id="pendentes" role="tabpanel">
            <div class="card p-0">
                <div class="table-responsive">
                    <table class="cr-table table mb-0">
                        <thead>
                            <tr>
                                <th>Pedido / Venc.</th>
                                <th>Cliente</th>
                                <th>Colaborador</th>
                                <th>Data da Baixa</th>
                                <th class="text-end">Valor do Cheque</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendentes as $cheque)
                                <tr>
                                    <td>
                                        <strong>{{ $cheque->NUM_PEDIDO }}</strong><br>
                                        <small class="text-muted">Venc: {{ date('d/m/Y', strtotime($cheque->DATA_VENCIMENTO)) }}</small>
                                    </td>
                                    <td>{{ $cheque->CLIENTE ?? 'N/A' }}</td>
                                    <td>{{ $cheque->NOME_COLABORADOR ?? 'N/A' }}</td>
                                    <td>{{ date('d/m/Y', strtotime($cheque->DATA_REGISTRO)) }}</td>
                                    <td class="text-end fw-bold" style="color: #ea580c;">
                                        R$ {{ number_format($cheque->VALOR_PAGO_PEDIDO, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('cheques-compensar', $cheque->ID_PEDIDO) }}" class="d-inline" onsubmit="return confirm('Deseja dar este cheque como compensado no caixa?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success fw-bold" title="Compensar Cheque">Compensar</button>
                                        </form>
                                        <form method="POST" action="{{ route('cheques-devolver', $cheque->ID_DETALHE) }}" class="d-inline" onsubmit="return confirm('ATENÇÃO: O cheque foi devolvido ou não foi pago?\nIsso vai excluir a baixa e o pedido voltará para cobrança. Deseja continuar?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger fw-bold ms-1" title="Cheque Devolvido/Não Pago">Não Pago</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">Nenhum cheque pendente de compensação.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- COMPENSADOS -->
        <div class="tab-pane fade" id="compensados" role="tabpanel">
            <div class="card p-0">
                <div class="table-responsive">
                    <table class="cr-table table mb-0">
                        <thead>
                            <tr>
                                <th>Pedido / Venc.</th>
                                <th>Cliente</th>
                                <th>Colaborador</th>
                                <th>Data da Baixa</th>
                                <th class="text-end">Valor do Cheque</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($compensados as $cheque)
                                <tr style="opacity: 0.8;">
                                    <td>
                                        <strong>{{ $cheque->NUM_PEDIDO }}</strong><br>
                                        <small class="text-muted">Venc: {{ date('d/m/Y', strtotime($cheque->DATA_VENCIMENTO)) }}</small>
                                    </td>
                                    <td>{{ $cheque->CLIENTE ?? 'N/A' }}</td>
                                    <td>{{ $cheque->NOME_COLABORADOR ?? 'N/A' }}</td>
                                    <td>{{ date('d/m/Y', strtotime($cheque->DATA_REGISTRO)) }}</td>
                                    <td class="text-end fw-bold" style="color: #059669;">
                                        R$ {{ number_format($cheque->VALOR_PAGO_PEDIDO, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">Compensado</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">Nenhum cheque compensado ainda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
