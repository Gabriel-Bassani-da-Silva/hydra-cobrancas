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
            <p style="color: #64748b; margin-bottom: 1rem;">Total geral de recebimentos e pontos conquistados.</p>
            
            <div class="table-responsive">
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">Pos</th>
                            <th>Colaborador</th>
                            <th class="text-center">Baixas</th>
                            <th class="text-center">Pontos</th>
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
                                <td class="text-center" style="color: #3b82f6; font-weight: 700;">{{ $item->PONTOS_TOTAIS ?? 0 }} pts</td>
                                <td class="valor-col" style="color: #059669; font-weight: 600;">R$ {{ number_format($item->TOTAL_RECEBIDO, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center" style="padding: 2rem; color: #64748b;">Nenhum recebimento registrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RANKING DIÁRIO / VIGENTE -->
        <div class="card" style="margin-bottom: 0;">
            <div style="margin-bottom: 1rem;">
                <h2>Ranking Diário (Vigente)</h2>
                <p style="color: #64748b; margin-bottom: 0;">
                    Baixas recebidas a partir da data de corte: 
                    <strong style="color: #0f172a;">{{ date('d/m/Y', strtotime($dataFiltro)) }}</strong>
                </p>
            </div>
            
            <div class="table-responsive">
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">Pos</th>
                            <th>Colaborador</th>
                            <th class="text-center">Baixas</th>
                            <th class="text-center">Pontos</th>
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
                                <td class="text-center" style="color: #3b82f6; font-weight: 700;">{{ $item->PONTOS_TOTAIS ?? 0 }} pts</td>
                                <td class="valor-col" style="color: #059669; font-weight: 600;">R$ {{ number_format($item->TOTAL_RECEBIDO, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center" style="padding: 2rem; color: #64748b;">Nenhum recebimento registrado para este período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
