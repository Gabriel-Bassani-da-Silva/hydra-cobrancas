@extends('layouts.app')

@section('title', 'Início')

@section('body_class', 'home-page')

@section('content')
<div class="dashboard-container">
    
    @if (session('error_message'))
        <div class="alert alert-error">
            {{ session('error_message') }}
        </div>
    @endif

    @if (session('success_message'))
        <div class="alert alert-success">
            {{ session('success_message') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
        
        <!-- RANKING TOTAL -->
        <div class="card" style="margin-bottom: 0;">
            <h2>Ranking Total</h2>
            <p style="color: #64748b; margin-bottom: 1rem;">Total geral de recebimentos baixados por colaborador.</p>
            
            <div class="table-responsive">
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">Pos</th>
                            <th>Colaborador</th>
                            <th class="text-center">Qtd. Baixas</th>
                            <th class="valor-col">Total Recebido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ranking as $index => $item)
                            <tr>
                                <td class="text-center">
                                    @if($index === 0)
                                        <span style="background: #fbbf24; color: #78350f; padding: 4px 8px; border-radius: 999px; font-weight: bold;">1º</span>
                                    @elseif($index === 1)
                                        <span style="background: #94a3b8; color: #0f172a; padding: 4px 8px; border-radius: 999px; font-weight: bold;">2º</span>
                                    @elseif($index === 2)
                                        <span style="background: #d97706; color: #fffbeb; padding: 4px 8px; border-radius: 999px; font-weight: bold;">3º</span>
                                    @else
                                        <span style="color: #64748b; font-weight: bold;">{{ $index + 1 }}º</span>
                                    @endif
                                </td>
                                <td style="font-weight: 500;">{{ $item->NOME_COLABORADOR }}</td>
                                <td class="text-center" style="color: #475569;">{{ $item->QTD_BAIXAS }}</td>
                                <td class="valor-col" style="color: #059669; font-weight: 600;">R$ {{ number_format($item->TOTAL_RECEBIDO, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center" style="padding: 2rem; color: #64748b;">Nenhum recebimento registrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RANKING POR DATA (DIÁRIO) -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <h2>Ranking por Vencimento</h2>
                    <p style="color: #64748b; margin-bottom: 0;">Títulos vencidos a partir de determinada data e que já foram recebidos.</p>
                </div>
                
                <form action="" method="GET" style="display: flex; gap: 0.5rem; align-items: center; background: #f8fafc; padding: 0.5rem; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <label for="data_vencimento" style="font-size: 0.875rem; font-weight: 600; color: #475569; white-space: nowrap;">A partir de:</label>
                    <input type="date" id="data_vencimento" name="data_vencimento" value="{{ $dataFiltro ?? date('Y-m-d') }}" style="padding: 0.25rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.375rem; outline: none; font-size: 0.875rem; color: #0f172a; cursor: pointer;" onchange="this.form.submit()">
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">Pos</th>
                            <th>Colaborador</th>
                            <th class="text-center">Qtd. Baixas</th>
                            <th class="valor-col">Total Recebido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rankingDiario as $index => $item)
                            <tr>
                                <td class="text-center">
                                    @if($index === 0)
                                        <span style="background: #fbbf24; color: #78350f; padding: 4px 8px; border-radius: 999px; font-weight: bold;">1º</span>
                                    @elseif($index === 1)
                                        <span style="background: #94a3b8; color: #0f172a; padding: 4px 8px; border-radius: 999px; font-weight: bold;">2º</span>
                                    @elseif($index === 2)
                                        <span style="background: #d97706; color: #fffbeb; padding: 4px 8px; border-radius: 999px; font-weight: bold;">3º</span>
                                    @else
                                        <span style="color: #64748b; font-weight: bold;">{{ $index + 1 }}º</span>
                                    @endif
                                </td>
                                <td style="font-weight: 500;">{{ $item->NOME_COLABORADOR }}</td>
                                <td class="text-center" style="color: #475569;">{{ $item->QTD_BAIXAS }}</td>
                                <td class="valor-col" style="color: #059669; font-weight: 600;">R$ {{ number_format($item->TOTAL_RECEBIDO, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center" style="padding: 2rem; color: #64748b;">Nenhum recebimento registrado para este período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
