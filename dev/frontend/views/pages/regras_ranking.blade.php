@extends('layouts.app')
@section('title', 'Regras do Ranking')
@section('body_class', 'contatos-page')
@section('content')

<div class="contatos-wrapper">
    <div class="contatos-header-actions">
        <div class="header-title-section">
            <h2>Configurações do Ranking</h2>
            <p>Gerencie as regras de pontuação e data de corte do ranking exibido na dashboard.</p>
        </div>
    </div>

    @if (session('success_message'))
        <div class="alert alert-success mt-3">
            {{ session('success_message') }}
        </div>
    @endif

    <div class="card p-4 mt-4" style="max-width: 600px; margin: 0 auto;">
        <form action="{{ route('regras-ranking.update') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="data_inicio_diario" class="form-label" style="font-weight: 600;">Data de Corte (Ranking Diário/Vigente)</label>
                <p class="text-muted small" style="margin-top: -5px; margin-bottom: 10px;">Apenas baixas registradas a partir dessa data entrarão no ranking "Diário".</p>
                <input type="date" class="form-control" id="data_inicio_diario" name="data_inicio_diario" value="{{ $config->DATA_INICIO_DIARIO ?? date('Y-m-d') }}" required>
            </div>

            <div class="mb-4">
                <label for="pontos_pedido_pago" class="form-label" style="font-weight: 600;">Pontos por Pedido Pago</label>
                <p class="text-muted small" style="margin-top: -5px; margin-bottom: 10px;">Quantidade de pontos atribuídos ao colaborador quando as baixas atingem ou ultrapassam o valor total do pedido.</p>
                <input type="number" class="form-control" id="pontos_pedido_pago" name="pontos_pedido_pago" value="{{ $config->PONTOS_PEDIDO_PAGO ?? 10 }}" min="0" required>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4 py-2" style="font-weight: 500;">Salvar Configurações</button>
            </div>
        </form>
    </div>
</div>

@endsection
