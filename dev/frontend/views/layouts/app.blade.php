<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gerenciador de Cobranças')</title>
    <meta name="base-url" content="{{ url('/') }}">
    <link rel="icon" type="image/png" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAAsTAAALEwEAmpwYAAAHe0lEQVR4nO1bCWxVRRQ9bBW0aIGCW5FFoI0JwYi0osUoLmgi0WrY4hJN1AoaExKkghoMEqxiNFIsiqLGFpCKNhoEWV2AuqDEuKFBNFE2sexCKaX95iZn9Gac9/77v7//vZqe5KU/M2/mzXKXc+9MgTa0IUo4H8AkAIsBbAFQC+AEn1qWLQIwEUB//E/QAcAEAJsAxBJ4mgBsBDCefbRKXAvgRzWpw9z9ewAMA9ATQCc+8jufdUv4rmn3A4Cr0IrQBcACNYFdAO4DcGoCfci7dwPYpvqZD6AzIo6eADZzwMcBPAbgtGb0J9JRwr6kz88BZCPCk9/Kge4GMNzxTi4ntJbv/gWgHsCKOH1fCmCPUonsKIr9Zg7wWwC9rfqLAazzMXpfqXfFE6wCMMTq4zz2Le9/FjV1WKB2frclwvNo1aV+H4CFAG6kNIh6ZFh9GcN5EsCTjkUwklCOCFn7GIA6S+y7AVjPumMAZgE4PUB/8s4z5AcyWRuXKZswEiGjg9oxMXh6583kdwAY6tFe+MEGj7ochyoZTGPf34fNEyYoV6et/Tw1eZmIF4wNiIdiPgaiNtvZdixCxCYOQvy8NnhNFHvXzsuuLrPIjvyupl1w4SD7LFBl97KtlwSlhds3cfCa5BhrLzrvmvw+H2+w30Psn2D9R6pMJO4Ix9AXIWASByX01iBXWfszHG2WsX45gHNVeQ65gNRVOdplUgqkPk+VL2WZVo+0YTE/LvzdoIRl4upcMGKf4zCCvVl3yKPti6yfocqKWVaJELCFH5fAxmANy8TPB1kA2wjKYnzo0baI7wpJMihg2ZcIAbX8uKalP7FskEebatav4CIE9QKgnsu7v6uyXizbixBQz49nOHa4q0ebXBo62/jF8wKgoTWkyuAUFXq1igUwul5FXQ/qBcA+zWJFYgFqk1ABL8TzAtrDCPOMhAps4cclk2MbwZsCUt9EvMDNDiN4SZhGcJHDDU5l2atJeAGzAOLvXXid9VMcbrACIWAiPy45PFtM93sQIS8vIJNfyd9CbmxkATjA+oGqvIplQovTjv4eVHgtBzU7AS9gnlqP4Okph/hnMqMkY+iDkLCRA5MEpsFQDqqOgVEQL3CIO++afD77agRwoSMY8iJOacF4DmIbcwAGc1MUDsti7eQ7z1nu7xeWj0GI6MAkZYxxgEFHpQo7LLqMgNQ3X01+Nfs0mK7yj+0RMq5WZESyt9pwmUWoY35PyuIhizp/XE1etyskCRM1uwIRwXwOdg8Tlwaya89Tf413eI2BTR4NWSZ/F9HVGWvfSLHXOy/G7g/WlyFC6MxDCyOWehEEFymSFORZbRk8sM/vWF9DOxApZCt7sIfZWxviwx/iBLcyo3OE7VaR5AxwtCtUOy+L0AMRRTYPLYxNmObI+yeCDBq8erXzkZ28VodyJc7b6bMTOSPMZBvj6pqo8y0q9h2pZyJutwJ4mOntCqacZnNQFwTsbyTz9mYhjpDsFDOT04s7nMHfBcwuV5HhxZRNSZm1HwjgBgD3AyhlQCNs7jceRQU1Ul9TX118X0N89Dj6e3M8FuRpIj8Ykyo/f1aAWxonuRAbuTCyQA/w8OM2AI9SEg5YFFb8+5kBxtCPu1/J8LWWui3PnyyroJSlPM39lkpXL6eOTqOoF1L0tb/1g+jhLSoOMETnBU4ykjjKgaY6arqcoa0R7wbu8GBEDDEr6NDqkIqjJSEtbyo7IgvynkWNI7UAG1RZKsPJAbwrYHi8IS4vkd+/TCv/Ck+WxN0lirOpugvpIXYy5vCNCmNxws5U4xwAcyyD6Xr2WjlFF7ozTiiz3KrrkYVtF4UFMBAfP4q7XcK/45hOq+GYDlvnhEKYrgPwND2DCaTMIxzhA1LqfHqgIh7RS/2diNAC+KGdyhG+A+BxquYJa8LiJj/mmeAIH2o9gu9/EYYRTBZDPPjIZtqMUQlS6EPWyVHajWCi6KQmPZfnCXK3KBl0Yz8SNf4HLaUCbwP4BsDkJKK0LhT7GMPh5i7kG35H5bEWWgCbDVaSHMVzY7NIf01bodrJ2pGxKg9x2COfgJZagI68C/C+Za1/pkjfDuB6HmtNYVaoQb0nx2ujk/z2aAZlpq9feVSGsIxgHwAzVSbX6xEr/y6AK5P8zjUq6RKjzk+mSnkiFsAI5tFHT6XPHu9zVO2H9oztZzC6W0laLBJxF4lNMijkJamYSrBOD8om69hIr1Iew9MlvOLqFZevoxiHhWHq3NDo+cyAKfZ/8KHa7aUeE95Nqz6HPrha3cwyqiJkI10YzDGYSPMox5adbGe7rQnv4u4XW1fPNISEPKguKMcY/kqau6UwiDfQGhUTLKP3aBay6DLu8JkwfBbiERXcNFGS/O70JIq+vFdgvEQDg5vQTn5d6E7yYi46NDK8bc5/efVi+q3O6jPRazZp/++QUhX31zPml9xjUPRgH8eUeq1xnAhFGn048ZMqVC2NY6G70s0etCbuukvQajCY5MZM6AAN10jqdj+eIpdbE1/vcYTWajFc/aOE31PT2v4XMBmJeJaUeydd7qdkgy3pQtvQBvyLvwG2ahPplLxd0wAAAABJRU5ErkJggg==">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}?v={{ time() }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C11 21 3 13 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z" />
                    </svg>
                    <span>Telefones</span>
                </a>

                <a href="{{ url('/contas-receber') }}" class="header-icon-btn" title="Contas a Receber">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                    </svg>
                    <span>Contas</span>
                </a>

                <a href="{{ url('/divergencias') }}" class="header-icon-btn" title="Divergência Bling">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M12 2L1 21h22L12 2zm1 17h-2v-2h2v2zm0-4h-2V9h2v6z"/>
                    </svg>
                    <span>Divergência Bling</span>
                </a>

                <a href="{{ url('/perfil') }}" class="header-icon-btn profile" title="Meu Perfil">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z" />
                    </svg>
                </a>

                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="header-icon-btn" title="Sair" style="color: #d9534f; margin-left: 10px; background:none; border:none; cursor:pointer; padding:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                            <path d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 012 2v2h-2V4H5v16h9v-2h2v2a2 2 0 01-2 2H5a2 2 0 01-2-2V4a2 2 0 012-2h9z"/>
                        </svg>
                    </button>
                </form>
            </nav>
        </header>
    @endif

    <main>
        @yield('content')
    </main>

    <!-- Linkando o JS original do sistema legado temporariamente -->
    <script src="{{ asset('js/main.js') }}?v={{ time() }}"></script>
    @stack('scripts')
</body>

</html>
