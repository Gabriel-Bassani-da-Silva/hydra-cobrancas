@extends('layouts.app')

@section('title', 'Testes e Diagnósticos - Hydra')

@section('content')
<div class="content-header">
    <h1>Suíte de Testes Isolados</h1>
    <div class="header-actions">
        <a href="/contas-receber" class="btn btn-secondary">
            <i class="ph ph-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="testes-container" style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 2rem;">
    
    <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem; line-height: 1.5;">
            Estes testes simulam ações reais chamando os controladores do sistema, criam dados fictícios para validação e, ao final, <strong>excluem fisicamente</strong> todos os registros criados para não poluir sua base de dados.
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            
            <div style="background: var(--bg-body); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                <i class="ph ph-money" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">Baixas e Estornos</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem; min-height: 60px;">Testa o fluxo de dar baixa em um pedido e estornar o pagamento.</p>
                <button onclick="executarTeste('/testes/baixas')" class="btn btn-primary" style="width: 100%;">Rodar Teste</button>
            </div>

            <div style="background: var(--bg-body); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                <i class="ph ph-phone" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">Contatos, Fin. e Pedras</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem; min-height: 60px;">Testa telefones, contatos financeiros, exclusões e status Pedra.</p>
                <button onclick="executarTeste('/testes/contatos')" class="btn btn-primary" style="width: 100%;">Rodar Teste</button>
            </div>

            <div style="background: var(--bg-body); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                <i class="ph ph-plugs" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">Webhook (Bling)</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem; min-height: 60px;">Simula a chegada de um pedido e seu posterior cancelamento.</p>
                <button onclick="executarTeste('/testes/webhook')" class="btn btn-primary" style="width: 100%;">Rodar Teste</button>
            </div>

            <div style="background: var(--bg-body); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                <i class="ph ph-headset" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">Cobranças (CRM)</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem; min-height: 60px;">Testa a régua de cobrança: iniciar agrupamento e desistir de cobrança.</p>
                <button onclick="executarTeste('/testes/cobrancas')" class="btn btn-primary" style="width: 100%;">Rodar Teste</button>
            </div>

            <div style="background: var(--bg-body); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                <i class="ph ph-warning-circle" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">Divergências</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem; min-height: 60px;">Testa o painel de auditoria, corrigindo valor de baixa e estorno.</p>
                <button onclick="executarTeste('/testes/divergencias')" class="btn btn-primary" style="width: 100%;">Rodar Teste</button>
            </div>

        </div>

        <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
            <button onclick="testarTodasRotas()" style="background-color: #fd971f; color: #ffffff; padding: 10px 20px; border-radius: 6px; border: none; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <i class="ph ph-heartbeat" style="font-size: 1.2rem;"></i> Testar 200 em Todas as Páginas
            </button>
        </div>

        <h3 style="margin-bottom: 0.5rem; font-size: 1rem; color: var(--text-primary);">Log de Execução:</h3>
        <div id="teste-resultado" style="background: #1e1e1e; color: #a6e22e; padding: 1rem; border-radius: var(--radius-md); font-family: monospace; min-height: 200px; white-space: pre-wrap; font-size: 0.9rem; border: 1px solid #333;">Aguardando ação...</div>
    </div>

</div>

<script>
async function executarTeste(url) {
    const box = document.getElementById('teste-resultado');
    box.style.color = '#fd971f';
    box.innerText = `Executando testes [${url}]...\nPor favor, aguarde...\n`;

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            let errorText = "Erro desconhecido";
            try {
                const errorData = await response.json();
                errorText = errorData.log ? errorData.log.join('\n') : JSON.stringify(errorData);
            } catch (e) {
                errorText = `Status ${response.status} - ${response.statusText}`;
            }
            throw new Error(errorText);
        }

        const data = await response.json();
        box.style.color = '#a6e22e';
        box.innerText += data.log.join('\n') + '\n\n';
    } catch (e) {
        box.style.color = '#f92672';
        box.innerText += `\n[X] FALHA CRÍTICA:\n${e.message}\n`;
    }
}

async function testarTodasRotas() {
    const rotas = [
        '/',
        '/contas_receber',
        '/contatos',
        '/cobrancas',
        '/cobrancas/kanban',
        '/divergencias',
        '/perfil',
        '/testes'
    ];

    const box = document.getElementById('teste-resultado');
    box.style.color = '#fd971f';
    box.innerText = `Iniciando varredura de rotas (Health Check)...\n\n`;

    for (let rota of rotas) {
        try {
            let res = await fetch(rota);
            if (res.status === 200) {
                box.innerText += `[200 OK] ${rota}\n`;
            } else {
                box.style.color = '#f92672';
                box.innerText += `[ERROR ${res.status}] ${rota}\n`;
            }
        } catch (e) {
            box.style.color = '#f92672';
            box.innerText += `[FAIL] ${rota} - ${e.message}\n`;
        }
    }
    box.innerText += `\nVarredura concluída.`;
}
</script>
@endsection
