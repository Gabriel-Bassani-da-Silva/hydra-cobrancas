@extends('layouts.app')

@section('title', 'Início')

@section('body_class', 'home-page')

@section('content')
<div class="dashboard-container">
    <h1>Bem-vindo ao Gerenciador de Cobranças (Laravel)</h1>
    
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

    <div class="card">
        <h2>Resumo do Sistema</h2>
        <p>Olá, <strong>{{ auth()->user()->NOME_COLABORADOR }}</strong>!</p>
        <p>Utilize o menu no cabeçalho para gerenciar Telefones e Contas a Receber.</p>
        <p>Se o Bling estiver desconectado, clique no indicador de conexão no topo da página para configurá-lo.</p>
    </div>
</div>
@endsection
