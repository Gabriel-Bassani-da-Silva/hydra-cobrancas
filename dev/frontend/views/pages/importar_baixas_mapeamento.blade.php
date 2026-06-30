@extends('layouts.app')
@section('title', 'Mapeamento - Baixas')
@section('body_class', 'contatos-page scrollable-page')
@section('content')
<div class="contatos-wrapper">
    <div class="contatos-header-actions">
        <div class="header-title-section">
            <h2>Mapeamento de Colunas</h2>
            <p>Associe as colunas da sua planilha aos campos do sistema.</p>
        </div>
    </div>
    <div class="card p-4 mt-3">
        <form action="{{ route('salvar-mapeamento-baixas') }}" method="POST">
            @csrf
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="ignorar_primeira_linha" id="ignorar_primeira_linha" value="1" checked>
                <label class="form-check-label" for="ignorar_primeira_linha">
                    A primeira linha é cabeçalho (Ignorar ao importar)
                </label>
            </div>
            
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Sua Coluna (Amostra)</th>
                        <th>Significa...</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($headers as $idx => $headerName)
                    <tr>
                        <td>
                            <strong>{{ $headerName }}</strong>
                            <div class="text-muted small">
                                Ex: {{ $amostra[1][$idx] ?? '' }}
                            </div>
                        </td>
                        <td>
                            <select name="map[{{ $idx }}]" class="form-select">
                                <option value="">-- Ignorar --</option>
                                <option value="NUM_PEDIDO">Número do Pedido (NFe)</option>
                                <option value="VALOR_PAGO">Valor Pago</option>
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary">Próximo Passo (Verificar Cruzamento)</button>
            </div>
        </form>
    </div>
</div>
@endsection
