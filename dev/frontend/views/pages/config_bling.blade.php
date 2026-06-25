@extends('layouts.app')

@section('title', 'Configuração do Bling')
@section('body_class', 'config-bling-page')

@section('content')
<?php
$title = "Configuração do Bling";
$body_class = "config-bling-page";
$show_header = true;

$blingService = new \App\Integrations\Bling\BlingService();
$isConfigured = $blingService->isConfigured();

// Recupera a config atual (se houver) para preencher o form
$blingConfig = \Illuminate\Support\Facades\DB::table('BLING_CONFIG')->orderBy('ID', 'desc')->first();
$clientId = $blingConfig ? ($blingConfig->CLIENT_ID ?? '') : '';
$clientSecret = $blingConfig ? ($blingConfig->CLIENT_SECRET ?? '') : '';
$redirectUri = $blingConfig ? ($blingConfig->REDIRECT_URI ?? '') : '';


?>

<div class="dashboard-container">
    <h1>Configuração da Integração Bling</h1>
    
    @if(session('error_message'))
        <div class="alert alert-error">
            {{ session('error_message') }}
        </div>
    @endif

    @if(session('success_message'))
        <div class="alert alert-success">
            {{ session('success_message') }}
        </div>
    @endif

    <div class="card">
        <h2>Configurar Credenciais</h2>
        <p>Para o sistema se comunicar com o Bling, informe as credenciais do seu aplicativo (Client ID e Secret).</p>
        
        <form action="{{ route('salvar-config-bling') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="client_id">Client ID</label>
                <input type="text" id="client_id" name="client_id" value="<?= htmlspecialchars($clientId) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="client_secret">Client Secret</label>
                <input type="password" id="client_secret" name="client_secret" value="<?= htmlspecialchars($clientSecret) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="redirect_uri">Redirect URI</label>
                <input type="text" id="redirect_uri" name="redirect_uri" value="<?= htmlspecialchars($redirectUri ?: 'https://tecnologiaporan.github.io/WebsiteRequisitions/') ?>" required>
                <small>A URL acima deve ser <strong>EXATAMENTE IGUAL</strong> à "URL de retorno" configurada no painel do aplicativo no Bling.</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Salvar Configurações</button>
        </form>

        <?php if ($isConfigured): ?>
            <div class="auth-section">
                <h3>Autorização Manual (Github Pages)</h3>
                <p>Como você utiliza uma página externa (Github Pages) para o retorno do Bling, a autorização será feita em duas etapas:</p>
                <ol>
                    <li>Clique no botão <strong>"Passo 1"</strong> abaixo. O Bling vai abrir em uma nova guia, você autoriza o aplicativo e será redirecionado para a sua página no Github Pages.</li>
                    <li>Copie o <strong>Código recebido</strong> que vai aparecer lá na sua página.</li>
                    <li>Cole esse código no campo abaixo e clique no botão <strong>"Passo 2"</strong>.</li>
                </ol>
                
                <div class="auth-step-1">
                    <a href="{{ route('autorizar-bling') }}" target="_blank" class="btn btn-success">Passo 1: Obter Código de Autorização</a>
                </div>

                <form action="{{ route('callback-manual-bling') }}" method="POST" class="auth-step-2-box">
                    @csrf
                    <div class="form-group">
                        <label for="code">Código de Autorização (Cole o código do Github Pages aqui)</label>
                        <input type="text" id="code" name="code" placeholder="Ex: 9fd557d0db45aef2fbca503ec53f7a36b44e7905" required>
                    </div>
                    <button type="submit" class="btn btn-info">Passo 2: Trocar Código por Token</button>
                </form>
            </div>
        <?php else: ?>
            <div class="auth-section">
                <div class="alert alert-warning">
                    <strong>Atenção:</strong> Salve as configurações primeiro para liberar o painel de autorização.
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
