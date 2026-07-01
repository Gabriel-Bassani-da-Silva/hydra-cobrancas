@extends('layouts.app')
@section('title', 'Histórico de Importações')
@section('body_class', 'cr-page scrollable-page')
@section('content')
<div class="cr-wrapper">
    <div class="cr-header-top">
        <div>
            <h1 class="cr-title">Histórico de Importações</h1>
            <p class="cr-subtitle">Lotes de baixas importados via planilha</p>
        </div>
        <div class="cr-header-actions">
            <a href="{{ url('/contas-receber/importar') }}" class="btn-cr-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Nova Importação
            </a>
        </div>
    </div>

    @if(session('flash_msg'))
        <div class="info-box info-box--{{ str_contains(session('flash_msg'), 'ucesso') ? 'success' : 'warning' }} mb-4">
            {{ session('flash_msg') }}
        </div>
    @endif

    @if(empty($lotes))
        <div class="info-box info-box--info">
            Nenhuma importação registrada ainda. Faça a primeira importação de planilha!
        </div>
    @else
        <div class="cr-table-wrapper">
            <table class="cr-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Data</th>
                        <th>Arquivo</th>
                        <th>Registros</th>
                        <th>Usuário</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lotes as $lote)
                    <tr>
                        <td><span class="badge-status badge-status--info">Lote #{{ $lote->ID_LOTE }}</span></td>
                        <td>{{ \Carbon\Carbon::parse($lote->DATA_CRIACAO)->format('d/m/Y H:i') }}</td>
                        <td>{{ $lote->NOME_ARQUIVO ?? '—' }}</td>
                        <td>
                            <span>{{ $lote->REGISTROS_EXISTENTES }} / {{ $lote->QTD_REGISTROS }}</span>
                            @if($lote->REGISTROS_EXISTENTES < $lote->QTD_REGISTROS)
                                <small class="text-muted"> (parcial)</small>
                            @endif
                        </td>
                        <td>{{ $lote->NOME_USUARIO ?? 'Sistema' }}</td>
                        <td>
                            @if($lote->REGISTROS_EXISTENTES > 0)
                                <span class="badge-status badge-status--pago">Ativo</span>
                            @else
                                <span class="badge-status badge-status--cancelado">Sem registros</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:.5rem;align-items:center;">
                                <a href="{{ url('/contas-receber/importar/lote/' . $lote->ID_LOTE . '/editar') }}"
                                   class="btn-cr-secondary" style="padding:.35rem .75rem;font-size:.8rem;">
                                    ✏️ Editar
                                </a>
                                <form method="POST" action="{{ url('/contas-receber/importar/lote/' . $lote->ID_LOTE . '/excluir') }}"
                                      onsubmit="return confirm('Tem certeza? Isso reverterá TODAS as baixas deste lote e excluirá permanentemente os pedidos criados pela planilha.')">
                                    @csrf
                                    <button type="submit" class="btn-cr-danger" style="padding:.35rem .75rem;font-size:.8rem;">
                                        🗑️ Excluir
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
