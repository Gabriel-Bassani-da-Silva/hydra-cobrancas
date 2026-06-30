@extends('layouts.app')
@section('title', 'Resultado da Importação')
@section('body_class', 'contatos-page scrollable-page')
@section('content')
<div class="contatos-wrapper">
    <div class="contatos-header-actions">
        <h2>Resultado da Importação</h2>
        <p>Resumo das baixas efetuadas no sistema.</p>
    </div>
    <div class="card p-4 mt-3">
        <h4 class="text-success">Baixas Efetuadas com Sucesso: {{ $log['sucessos'] ?? 0 }}</h4>
        
        @if(!empty($log['erros']))
            <h4 class="text-danger mt-4">Erros ({{ count($log['erros']) }})</h4>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($log['erros'] as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-4 text-center">
            <a href="{{ route('contas-receber-page') }}" class="btn btn-primary">Voltar para Contas a Receber</a>
        </div>
    </div>
</div>
@endsection
