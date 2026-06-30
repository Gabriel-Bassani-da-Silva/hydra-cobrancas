@extends('layouts.app')
@section('title', 'Preview da Importação')
@section('body_class', 'contatos-page scrollable-page')
@section('content')
<div class="contatos-wrapper">
    <div class="contatos-header-actions">
        <h2>Pré-Visualização do Cruzamento</h2>
        <p>Verifique quais baixas encontraram um Pedido no sistema.</p>
    </div>
    <div class="card p-4 mt-3">
        <h4 class="text-success">✅ Encontrados e Prontos ({{ count($prontos) }})</h4>
        @if(count($prontos) > 0)
        <table class="table table-sm table-bordered mt-2">
            <thead class="table-light">
                <tr>
                    <th>Nº Pedido</th>
                    <th>Cliente</th>
                    <th>Valor do Pedido</th>
                    <th>Valor a Baixar</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($prontos, 0, 50) as $p)
                <tr>
                    <td>{{ $p['num_pedido'] }}</td>
                    <td>{{ $p['cliente'] }}</td>
                    <td>R$ {{ number_format($p['total_pedido'], 2, ',', '.') }}</td>
                    <td class="text-success fw-bold">R$ {{ number_format($p['valor_pago'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if(count($prontos) > 50)
            <p class="text-muted small">Exibindo os primeiros 50 registros...</p>
        @endif
        @else
            <p class="text-muted">Nenhum pedido foi cruzado com sucesso.</p>
        @endif

        <hr class="my-4">

        <h4 class="text-danger">⚠️ Não Encontrados ({{ count($naoEncontrados) }})</h4>
        @if(count($naoEncontrados) > 0)
        <p class="text-muted">Os números abaixo não foram localizados no sistema e serão IGNORADOS nesta versão.</p>
        <ul class="list-unstyled">
            @foreach(array_slice($naoEncontrados, 0, 20) as $n)
                <li><span class="badge bg-secondary">{{ $n['num_pedido'] }}</span> (R$ {{ number_format($n['valor_pago'], 2, ',', '.') }})</li>
            @endforeach
        </ul>
        @if(count($naoEncontrados) > 20)
            <p class="text-muted small">E mais {{ count($naoEncontrados) - 20 }} não encontrados...</p>
        @endif
        @endif

        <div class="mt-4 text-end">
            <form action="{{ route('confirmar-importacao-baixas') }}" method="POST">
                @csrf
                <a href="{{ route('importar-baixas-page') }}" class="btn btn-outline-secondary me-2">Cancelar</a>
                @if(count($prontos) > 0)
                <button type="submit" class="btn btn-success">
                    Confirmar e Efetuar {{ count($prontos) }} Baixas
                </button>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
