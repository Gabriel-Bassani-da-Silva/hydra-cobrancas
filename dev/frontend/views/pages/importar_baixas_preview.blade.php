@extends('layouts.app')
@section('title', 'Preview - Importar Baixas')
@section('body_class', 'contas-receber-page scrollable-page')
@section('content')
<style>
.scrollable-page main, .scrollable-page .cr-wrapper { height: auto !important; overflow: visible !important; }
.cr-table th, .cr-table td { padding: 12px 16px; font-size: 0.85rem; vertical-align: middle; }
.table-danger-custom th { background: #fee2e2 !important; color: #991b1b !important; }
.cr-table select { border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 12px; font-size: 0.85rem; background: #fff; width: 100%; outline: none; }
.cr-table select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
</style>
<div class="cr-wrapper">
    <div class="cr-header-actions mb-4">
        <div class="header-title-section">
            <h2>Pré-Visualização da Importação</h2>
            <p>Revise os registros. Pedidos identificados como <span class="status-badge badge-info" style="font-size:0.7rem;">NOVO</span> serão criados no sistema.</p>
        </div>
    </div>

    <div style="max-width: 1200px; margin: 0 auto; width: 100%;">
        {{-- PRONTOS --}}
        <h3 style="font-size: 1.1rem; color: #1e293b; font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <svg width="20" height="20" fill="none" stroke="#10b981" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Prontos para importar ({{ count($prontos) }})
            @if(count($criados ?? []) > 0)
                <span style="font-size: 0.85rem; font-weight: 400; color: #64748b; margin-left: auto;">
                    {{ count($criados) }} serão criados como novos pedidos
                </span>
            @endif
        </h3>

        @if(count($prontos) > 0)
        <form action="{{ route('confirmar-importacao-baixas') }}" method="POST">
            @csrf
            <div class="cr-table-wrapper" style="margin-bottom: 32px;">
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th class="text-center">Status</th>
                            <th>Nº Pedido</th>
                            <th>Cliente</th>
                            <th class="text-right">Total Pedido</th>
                            <th class="text-right">Valor a Baixar</th>
                            <th>Colaborador</th>
                            <th class="text-center" style="color: #ea580c;">É Cheque?</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prontos as $idx => $p)
                        <tr>
                            <td class="text-center">
                                @if(($p['status'] ?? '') === 'criado')
                                    <span class="status-badge badge-info" style="font-size:0.7rem;">NOVO</span>
                                @else
                                    <span class="status-badge badge-success" style="font-size:0.7rem;">EXISTE</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $p['num_pedido'] ?: '-' }}</strong>
                            </td>
                            <td>{{ $p['cliente'] }}</td>
                            <td class="text-right text-muted">R$ {{ number_format($p['total_pedido'], 2, ',', '.') }}</td>
                            <td class="text-right font-semibold" style="color: #059669;">R$ {{ number_format($p['valor_pago'], 2, ',', '.') }}</td>
                            <td>
                                <select name="colaboradores[{{ $idx }}]" required>
                                    <option value="">-- Selecione --</option>
                                    @foreach($colaboradoresDb ?? [] as $colab)
                                        <option value="{{ $colab->ID_COLABORADOR }}" {{ ($p['id_colaborador'] ?? null) == $colab->ID_COLABORADOR ? 'selected' : '' }}>
                                            {{ $colab->NOME_COLABORADOR }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="is_cheque[{{ $idx }}]" value="1" title="Marcar como cheque" style="width:16px; height:16px; accent-color: #ea580c; cursor: pointer;">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="cr-table-wrapper" style="padding: 32px; text-align: center; color: #64748b; margin-bottom: 32px;">
                Nenhum pedido localizado ou criável com base na planilha.
            </div>
        @endif

        {{-- NÃO ENCONTRADOS --}}
        @if(count($naoEncontrados) > 0)
            <h3 style="font-size: 1.1rem; color: #ef4444; font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Ignorados — cliente não localizado ({{ count($naoEncontrados) }})
            </h3>
            
            <div class="cr-table-wrapper" style="margin-bottom: 32px; border-color: #fca5a5;">
                <table class="cr-table">
                    <thead class="table-danger-custom">
                        <tr>
                            <th>Nº Pedido</th>
                            <th>Cliente Informado</th>
                            <th class="text-right">Valor</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($naoEncontrados, 0, 30) as $n)
                        <tr>
                            <td><strong>{{ $n['num_pedido'] ?: '-' }}</strong></td>
                            <td>{{ $n['nome_cliente'] }}</td>
                            <td class="text-right font-semibold text-muted">R$ {{ number_format($n['valor_pago'], 2, ',', '.') }}</td>
                            <td style="color: #b91c1c;">{{ $n['motivo'] ?? 'Não localizado' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- AÇÕES --}}
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
            <a href="{{ route('importar-baixas-page') }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; border: 1px solid #cbd5e1; border-radius: 8px; color: #475569; font-weight: 500; text-decoration: none; background: #fff; transition: 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                Cancelar
            </a>
            @if(count($prontos) > 0)
                <button type="submit" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px; border: none; border-radius: 8px; background: #10b981; color: #fff; font-weight: 600; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    Confirmar e Baixar {{ count($prontos) }} registro(s)
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
