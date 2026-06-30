@extends('layouts.app')
@section('title', 'Preview - Importar Baixas')
@section('body_class', 'contatos-page scrollable-page')
@section('content')
<style>
.scrollable-page main, .scrollable-page .contatos-wrapper { height: auto !important; overflow: visible !important; }
.badge-criado { background: #0d6efd; color: #fff; font-size: 11px; padding: 2px 7px; border-radius: 4px; }
.badge-exist  { background: #198754; color: #fff; font-size: 11px; padding: 2px 7px; border-radius: 4px; }
</style>
<div class="contatos-wrapper">
    <div class="contatos-header-actions">
        <h2>Pré-Visualização da Importação</h2>
        <p>Revise os registros. Pedidos marcados como <span class="badge-criado">NOVO</span> serão criados durante a confirmação.</p>
    </div>

    <div class="card p-4 mt-3" style="max-width: 1100px; margin: 20px auto;">

        {{-- PRONTOS --}}
        <h5 class="mb-2">
            ✅ Prontos para importar ({{ count($prontos) }})
            @if(count($criados ?? []) > 0)
                — <span class="text-primary">{{ count($criados) }} serão criados como novos pedidos</span>
            @endif
        </h5>

        @if(count($prontos) > 0)
        <form action="{{ route('confirmar-importacao-baixas') }}" method="POST">
            @csrf
        <div style="overflow-x:auto;">
            <table class="table table-sm table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Status</th>
                        <th>NUM_PEDIDO</th>
                        <th>Cliente</th>
                        <th>Total Pedido</th>
                        <th>Valor a Baixar</th>
                        <th>Data Pago</th>
                        <th>Colaborador</th>
                        <th class="text-center" style="color: #ea580c;">É Cheque?</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prontos as $idx => $p)
                    <tr>
                        <td>
                            @if(($p['status'] ?? '') === 'criado')
                                <span class="badge-criado">NOVO</span>
                            @else
                                <span class="badge-exist">EXISTE</span>
                            @endif
                        </td>
                        <td>{{ $p['num_pedido'] }}</td>
                        <td>{{ $p['cliente'] }}</td>
                        <td>R$ {{ number_format($p['total_pedido'], 2, ',', '.') }}</td>
                        <td class="text-success fw-bold">R$ {{ number_format($p['valor_pago'], 2, ',', '.') }}</td>
                        <td>{{ $p['data_pago'] ?: '(hoje)' }}</td>
                        <td>
                            <select name="colaboradores[{{ $idx }}]" class="form-select form-select-sm" required style="min-width:150px;">
                                <option value="">-- Selecione --</option>
                                @foreach($colaboradoresDb ?? [] as $colab)
                                    <option value="{{ $colab->ID_COLABORADOR }}" {{ ($p['id_colaborador'] ?? null) == $colab->ID_COLABORADOR ? 'selected' : '' }}>
                                        {{ $colab->NOME_COLABORADOR }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <input class="form-check-input" type="checkbox" name="is_cheque[{{ $idx }}]" value="1" title="Marcar como cheque">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </table>
        </div>
        @else
            <p class="text-muted">Nenhum pedido localizado ou criável.</p>
        @endif

        <hr class="my-4">

        {{-- NÃO ENCONTRADOS --}}
        <h5 class="text-danger mb-2">⚠️ Ignorados — cliente não localizado ({{ count($naoEncontrados) }})</h5>
        @if(count($naoEncontrados) > 0)
        <table class="table table-sm table-bordered">
            <thead class="table-danger">
                <tr><th>NUM_PEDIDO</th><th>Cliente Informado</th><th>Valor</th><th>Motivo</th></tr>
            </thead>
            <tbody>
                @foreach(array_slice($naoEncontrados, 0, 30) as $n)
                <tr>
                    <td>{{ $n['num_pedido'] }}</td>
                    <td>{{ $n['nome_cliente'] }}</td>
                    <td>R$ {{ number_format($n['valor_pago'], 2, ',', '.') }}</td>
                    <td class="text-danger small">{{ $n['motivo'] ?? 'Não localizado' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <p class="text-success small">🎉 Todos os registros foram processados!</p>
        @endif

        {{-- AÇÕES --}}
        <div class="mt-4 d-flex justify-content-between align-items-center">
            <a href="{{ route('importar-baixas-page') }}" class="btn btn-outline-secondary">✕ Cancelar</a>
            @if(count($prontos) > 0)
                <button type="submit" class="btn btn-success px-5">
                    ✓ Confirmar e Baixar {{ count($prontos) }} registro(s)
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
