@extends('layouts.app')
@section('title', 'Mapeamento - Importar Baixas')
@section('body_class', 'contatos-page scrollable-page')
@section('content')
<style>
.scrollable-page main, .scrollable-page .contatos-wrapper { height: auto !important; overflow: visible !important; }
.map-table th { background: #1a1a2e; color: #fff; }
</style>

<div class="contatos-wrapper">
    <div class="contatos-header-actions">
        <div class="header-title-section">
            <h2>Mapeamento de Colunas</h2>
            <p>Associe cada coluna da sua planilha ao campo correspondente do sistema.</p>
        </div>
    </div>

    <div class="card p-4 mt-3" style="max-width: 960px; margin: 20px auto;">
        <div class="alert alert-info mb-4" style="font-size: 13px;">
            <strong>Como funciona?</strong><br>
            O sistema tentará encontrar o pedido pelo <strong>NUM_PEDIDO</strong>.<br>
            Se o pedido <strong>não existir</strong>, ele será <strong>criado automaticamente</strong> com origem <code>excel</code>, 
            usando o <strong>NOME_CLIENTE</strong> para localizar o contato no banco.
        </div>

        <form action="{{ route('salvar-mapeamento-baixas') }}" method="POST">
            @csrf
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="ignorar_primeira_linha" id="ignorar_primeira_linha" value="1" checked>
                <label class="form-check-label" for="ignorar_primeira_linha">
                    A primeira linha é cabeçalho (ignorar na importação)
                </label>
            </div>

            <table class="table table-bordered map-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Coluna na Planilha</th>
                        <th>Amostra</th>
                        <th>Corresponde a...</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($headers as $idx => $headerName)
                    <tr>
                        <td class="text-muted small">{{ $idx + 1 }}</td>
                        <td><strong>{{ $headerName }}</strong></td>
                        <td class="text-muted small">{{ $amostra[1][$idx] ?? ($amostra[2][$idx] ?? '—') }}</td>
                        <td>
                            <select name="map[{{ $idx }}]" class="form-select form-select-sm">
                                <option value="">— Ignorar —</option>
                                <optgroup label="Identificação do Pedido">
                                    <option value="NUM_PEDIDO">NUM_PEDIDO — Nº da Nota Fiscal ⚠️ Obrigatório</option>
                                    <option value="NOME_CLIENTE">NOME_CLIENTE — Nome do cliente (para criar pedidos)</option>
                                </optgroup>
                                <optgroup label="Dados do Pedido (ao criar novo)">
                                    <option value="TOTAL_PEDIDO">TOTAL_PEDIDO — Valor total do pedido</option>
                                    <option value="DATA_VENCIMENTO">DATA_VENCIMENTO — Data de vencimento</option>
                                </optgroup>
                                <optgroup label="Dados do Pagamento">
                                    <option value="VALOR_PAGO">VALOR_PAGO — Valor recebido ⚠️ Obrigatório</option>
                                    <option value="DATA_PAGO">DATA_PAGO — Data do recebimento</option>
                                    <option value="COLABORADOR">COLABORADOR — Nome do usuário ⚠️ Obrigatório</option>
                                </optgroup>
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4 d-flex justify-content-between align-items-center">
                <a href="{{ route('importar-baixas-page') }}" class="btn btn-outline-secondary">← Voltar</a>
                <button type="submit" class="btn btn-primary px-5">
                    Verificar Cruzamento →
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
