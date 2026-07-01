@extends('layouts.app')
@section('title', 'Editar Lote #' . $lote->ID_LOTE)
@section('body_class', 'cr-page scrollable-page')
@section('content')
<div class="cr-wrapper">
    <div class="cr-header-top">
        <div>
            <h1 class="cr-title">Editar Lote #{{ $lote->ID_LOTE }}</h1>
            <p class="cr-subtitle">
                Importado em {{ \Carbon\Carbon::parse($lote->DATA_CRIACAO)->format('d/m/Y \à\s H:i') }}
                &nbsp;·&nbsp; Arquivo: <strong>{{ $lote->NOME_ARQUIVO ?? '—' }}</strong>
            </p>
        </div>
        <div class="cr-header-actions">
            <a href="{{ url('/contas-receber/importar/historico') }}" class="btn-cr-secondary">
                ← Voltar ao Histórico
            </a>
        </div>
    </div>

    @if(empty($registros))
        <div class="info-box info-box--warning">
            Este lote não possui registros ativos (as baixas podem ter sido revertidas manualmente).
        </div>
    @else
        <form method="POST" action="{{ url('/contas-receber/importar/lote/' . $lote->ID_LOTE . '/salvar') }}">
            @csrf
            <div class="cr-table-wrapper">
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Origem</th>
                            <th>Valor Baixado</th>
                            <th>Colaborador</th>
                            <th>Cheque?</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registros as $reg)
                        <tr>
                            <td>
                                <span class="badge-status badge-status--info">
                                    {{ $reg->NUM_PEDIDO ?: '(s/n)' }}
                                </span>
                            </td>
                            <td>{{ $reg->NOME_CLIENTE ?? '—' }}</td>
                            <td>
                                @if($reg->ORIGEM === 'excel')
                                    <span class="badge-status badge-status--pendente">Planilha</span>
                                @else
                                    <span class="badge-status badge-status--pago">Bling</span>
                                @endif
                            </td>
                            <td>R$ {{ number_format($reg->VALOR_PAGO_PEDIDO, 2, ',', '.') }}</td>
                            <td>
                                <select name="colaboradores[{{ $reg->ID_REGISTRO }}]" class="cr-select" style="min-width:140px;">
                                    <option value="">— Selecione —</option>
                                    @foreach($colaboradores as $col)
                                        <option value="{{ $col->ID_COLABORADOR }}"
                                            {{ $col->ID_COLABORADOR == $reg->ID_COLABORADOR ? 'selected' : '' }}>
                                            {{ $col->NOME_COLABORADOR }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center">
                                <label class="cheque-toggle" style="cursor:pointer;display:flex;align-items:center;gap:.4rem;justify-content:center;">
                                    <input type="checkbox"
                                           name="is_cheque[{{ $reg->ID_REGISTRO }}]"
                                           value="1"
                                           {{ ($reg->ID_FORMA_PAGAMENTO == $idFormaCheque && $reg->STATUS_CHEQUE === 'pendente') ? 'checked' : '' }}>
                                    <span>Cheque</span>
                                </label>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1.5rem;">
                <a href="{{ url('/contas-receber/importar/historico') }}" class="btn-cr-secondary">Cancelar</a>
                <button type="submit" class="btn-cr-primary">💾 Salvar Alterações</button>
            </div>
        </form>
    @endif
</div>
@endsection
