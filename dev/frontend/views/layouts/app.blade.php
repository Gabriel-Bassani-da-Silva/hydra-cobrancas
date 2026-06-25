<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gerenciador de Cobranças')</title>
    <meta name="base-url" content="{{ url('/') }}">
    <link rel="icon" type="image/png" href="data:image/png;base64,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">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}?v={{ time() }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Pusher e Echo para WebSockets (Reverb) -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    
    @stack('styles')
</head>

<body class="@yield('body_class')">

    @if (!View::hasSection('hide_header'))
        <header class="header">
            <div class="header-dates">
                <span>Total em Aberto: <strong>R$ {{ number_format($totais['TOTAL_VALOR'] ?? 0, 2, ',', '.') }}</strong></span>
                <span>Contas Pendentes: <strong>{{ $totais['QTD_CONTAS'] ?? 0 }}</strong></span>
                <span>Última Sinc: <strong>{{ $ultimaSinc ?? '-' }}</strong></span>
                <a href="{{ url('/config-bling') }}" class="bling-status {{ !empty($blingConnected) && $blingConnected ? 'ok' : 'error' }}" style="text-decoration: none; cursor: pointer;" title="Clique para configurar o Bling">
                    Bling: {{ (!empty($blingConnected) && $blingConnected) ? 'Conexão ok' : 'Erro de conexão' }}
                </a>
            </div>

            <nav>
                <a href="{{ url('/contatos') }}" class="header-icon-btn" title="Gerenciar Telefones">
                    <x-icons.icon-7 width="20" height="20" />
                    <span>Telefones</span>
                </a>

                <a href="{{ url('/contas-receber') }}" class="header-icon-btn" title="Contas a Receber">
                    <x-icons.icon-8 width="20" height="20" />
                    <span>Contas</span>
                </a>

                <a href="{{ url('/divergencias') }}" class="header-icon-btn" title="Divergência Bling">
                    <x-icons.icon-9 width="20" height="20" />
                    <span>Divergência Bling</span>
                </a>

                <a href="{{ url('/perfil') }}" class="header-icon-btn profile" title="Meu Perfil">
                    <x-icons.icon-10 />
                </a>

                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="header-icon-btn" title="Sair" style="color: #d9534f; margin-left: 10px; background:none; border:none; cursor:pointer; padding:0;">
                        <x-icons.icon-11 width="24" height="24" />
                    </button>
                </form>
            </nav>
        </header>
    @endif

    <main>
        @yield('content')
    </main>

    <!-- Inicialização do WebSockets -->
    <script>
        try {
            // O Reverb precisa dessas variáveis que virão do .env / Railway
            window.Pusher = window.Pusher || (typeof Pusher !== 'undefined' ? Pusher : null);
            if (typeof Echo !== 'undefined') {
                window.EchoApp = new Echo({
                    broadcaster: 'reverb',
                    key: '{{ env("REVERB_APP_KEY", "hydra_key") }}',
                    wsHost: window.location.hostname,
                    wsPort: 80,
                    wssPort: 443,
                    forceTLS: (window.location.protocol === 'https:'),
                    enabledTransports: ['ws', 'wss'],
                });
            }
        } catch(e) {
            console.warn("WebSockets offline (talvez bloqueado por AdBlock). O sistema continuará funcionando.", e);
        }
    </script>

    @stack('scripts')
</body>

</html>
