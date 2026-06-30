@extends('layouts.app')

@section('title', 'Testes e Diagnósticos - Hydra')

@section('content')
<div class="content-header">
    <h1>Testes de Sistema</h1>
    <div class="header-actions">
        <a href="/contas-receber" class="btn btn-secondary">
            <i class="ph ph-arrow-left"></i> Voltar para Contas a Receber
        </a>
    </div>
</div>

<div class="testes-container" style="max-width: 800px; margin: 0 auto; background: var(--bg-card); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
    
    <div style="margin-bottom: 2rem;">
        <h2 style="margin-bottom: 1rem; color: var(--text-primary); display: flex; align-items: center; gap: 0.5rem;">
            <i class="ph ph-plugs" style="color: var(--primary-color);"></i> Simulação de Webhook Isolada
        </h2>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem; line-height: 1.5;">
            Clique no botão abaixo para rodar um teste completo e isolado. O sistema irá:
        </p>
        <ul style="color: var(--text-secondary); margin-bottom: 1.5rem; line-height: 1.5; padding-left: 1.5rem;">
            <li>Criar um cliente de teste no banco de dados.</li>
            <li>Inserir um pedido falso simulando a carga inicial.</li>
            <li>Verificar se o pedido realmente foi gravado (validação).</li>
            <li><strong>Deletar fisicamente</strong> tudo o que foi criado para não sujar a base.</li>
        </ul>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem; background: var(--bg-body); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <button onclick="executarTesteCompleto()" class="btn btn-primary" style="min-width: 200px;">
                    <i class="ph ph-play-circle"></i> Executar Teste Isolado
                </button>
                <div style="color: var(--text-secondary); font-size: 0.9rem;">
                    O teste é executado em segundo plano e não deixará lixo no banco de dados.
                </div>
            </div>
        </div>
    </div>

    <!-- Output de Log -->
    <div style="margin-top: 2rem;">
        <h3 style="margin-bottom: 0.5rem; font-size: 1rem; color: var(--text-primary);">Log de Execução:</h3>
        <div id="teste-resultado" style="background: #1e1e1e; color: #a6e22e; padding: 1rem; border-radius: var(--radius-md); font-family: monospace; min-height: 200px; white-space: pre-wrap; font-size: 0.9rem; border: 1px solid #333;">Aguardando ação...</div>
    </div>

</div>

<script>
async function executarTesteCompleto() {
    const box = document.getElementById('teste-resultado');
    box.style.color = '#fd971f';
    box.innerText = 'Executando teste completo... Aguarde...\n';

    try {
        const response = await fetch('/testes/executar', {
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
