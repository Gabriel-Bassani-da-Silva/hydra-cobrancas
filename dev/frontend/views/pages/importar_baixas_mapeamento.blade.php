@extends('layouts.app')
@section('title', 'Mapeamento - Importar Baixas')
@section('body_class', 'contas-receber-page scrollable-page')
@section('content')
<style>
.scrollable-page main, .scrollable-page .cr-wrapper { height: auto !important; overflow: visible !important; }
.cr-table th, .cr-table td { padding: 14px 16px; font-size: 0.85rem; vertical-align: middle; }
.cr-table select { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; font-size: 0.85rem; background: #fff; width: 100%; outline: none; transition: 0.2s; color: #1e293b; }
.cr-table select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px; margin-bottom: 24px; color: #1e40af; font-size: 0.85rem; display: flex; gap: 12px; align-items: flex-start; }
.info-box svg { flex-shrink: 0; width: 20px; height: 20px; stroke: #2563eb; }
</style>

<div class="cr-wrapper">
    <div class="cr-header-actions mb-4">
        <div class="header-title-section">
            <h2>Mapeamento de Colunas</h2>
            <p>Associe cada coluna da sua planilha ao campo correspondente do sistema.</p>
        </div>
    </div>

    <div style="max-width: 960px; margin: 0 auto; width: 100%;">
        <div class="info-box">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <strong style="display: block; margin-bottom: 4px; color: #1e3a8a;">Como funciona?</strong>
                O sistema tentará encontrar o pedido pelo <strong>NUM_PEDIDO</strong>. Se a planilha não tiver número, buscará por <strong>NOME_CLIENTE + Vencimento</strong>.<br>
                Se o pedido <strong>não existir</strong>, ele será <strong>criado automaticamente</strong> no sistema com os dados informados.
            </div>
        </div>

        <form action="{{ route('salvar-mapeamento-baixas') }}" method="POST">
            @csrf
            
            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #334155; font-weight: 500; cursor: pointer; margin-bottom: 16px;">
                <input type="checkbox" name="ignorar_primeira_linha" id="ignorar_primeira_linha" value="1" checked style="width:16px; height:16px; accent-color: #3b82f6; cursor: pointer;">
                A primeira linha é cabeçalho (ignorar na importação)
            </label>

            <div class="cr-table-wrapper" style="margin-bottom: 32px;">
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th>Coluna na Planilha</th>
                            <th>Amostra de Dado</th>
                            <th style="width: 350px;">Corresponde a...</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($headers as $idx => $headerName)
                        @php $h = trim(mb_strtoupper($headerName)); @endphp
                        <tr>
                            <td style="text-align: center; color: #94a3b8; font-weight: 500;">{{ $idx + 1 }}</td>
                            <td><strong style="color: #0f172a;">{{ $headerName }}</strong></td>
                            <td style="color: #64748b; font-style: italic;">{{ $amostra[1][$idx] ?? ($amostra[2][$idx] ?? '—') }}</td>
                            <td>
                                <select name="map[{{ $idx }}]">
                                    <option value="" style="color: #94a3b8;">— Ignorar —</option>
                                    <optgroup label="Identificação do Pedido">
                                        <option value="NUM_PEDIDO" {{ $h === 'NUM_PEDIDO' ? 'selected' : '' }}>NUM_PEDIDO — Nº da Nota Fiscal</option>
                                        <option value="NOME_CLIENTE" {{ $h === 'NOME_CLIENTE' ? 'selected' : '' }}>NOME_CLIENTE — Nome do cliente</option>
                                    </optgroup>
                                    <optgroup label="Dados do Pedido (ao criar novo)">
                                        <option value="TOTAL_PEDIDO" {{ $h === 'TOTAL_PEDIDO' ? 'selected' : '' }}>TOTAL_PEDIDO — Valor total do pedido</option>
                                        <option value="DATA_VENCIMENTO" {{ $h === 'DATA_VENCIMENTO' ? 'selected' : '' }}>DATA_VENCIMENTO — Data de vencimento</option>
                                    </optgroup>
                                    <optgroup label="Dados do Pagamento">
                                        <option value="VALOR_PAGO" {{ $h === 'VALOR_PAGO' ? 'selected' : '' }}>VALOR_PAGO — Valor recebido ⚠️</option>
                                        <option value="COLABORADOR" {{ $h === 'COLABORADOR' ? 'selected' : '' }}>COLABORADOR — Nome do usuário ⚠️</option>
                                    </optgroup>
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
                <a href="{{ route('importar-baixas-page') }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; border: 1px solid #cbd5e1; border-radius: 8px; color: #475569; font-weight: 500; text-decoration: none; background: #fff; transition: 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Voltar
                </a>
                <button type="submit" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px; border: none; border-radius: 8px; background: #3b82f6; color: #fff; font-weight: 600; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                    Verificar Cruzamento
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
