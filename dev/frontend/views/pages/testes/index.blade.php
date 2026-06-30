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
                <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">Contatos e Telefones</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem; min-height: 60px;">Testa a adição, marcação de confirmação e exclusão de telefones.</p>
                <button onclick="executarTeste('/testes/telefones')" class="btn btn-primary" style="width: 100%;">Rodar Teste</button>
            </div>

            <div style="background: var(--bg-body); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center;">
                <i class="ph ph-plugs" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">Webhook (Bling)</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem; min-height: 60px;">Simula a chegada de um pedido e seu posterior cancelamento.</p>
                <button onclick="executarTeste('/testes/webhook')" class="btn btn-primary" style="width: 100%;">Rodar Teste</button>
            </div>

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
        
        const data = await response.json();
        
        if (response.ok) {
            box.style.color = '#a6e22e'; 
            box.innerText = data.log.join('\n');
            toast.success('Teste concluído com sucesso!');
        } else {
            box.style.color = '#f92672'; 
            box.innerText = data.log ? data.log.join('\n') : 'ERRO: ' + (data.message || 'Erro desconhecido');
            toast.error('O teste falhou em alguma etapa.');
        }
    } catch (error) {
        box.style.color = '#f92672';
        box.innerText = 'FALHA DE REDE: ' + error.message;
        toast.error('Erro de conexão ao executar teste.');
    }
}
</script>
@endsection
