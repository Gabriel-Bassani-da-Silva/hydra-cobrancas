@extends('layouts.app')
@section('title', 'Regras do Ranking')
@section('body_class', 'contatos-page')

@section('content')
<style>
    .ranking-config-container {
        max-width: 700px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    
    .premium-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        border: 1px solid #f1f5f9;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .premium-card:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: translateY(-2px);
    }
    
    .premium-card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 2rem;
        border-bottom: 1px solid #e2e8f0;
        position: relative;
    }
    
    .premium-card-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .premium-card-header p {
        margin: 0.5rem 0 0 0;
        color: #64748b;
        font-size: 0.95rem;
        line-height: 1.5;
    }
    
    .premium-card-body {
        padding: 2rem;
    }
    
    .form-group {
        margin-bottom: 2rem;
        position: relative;
    }
    
    .form-group:last-child {
        margin-bottom: 0;
    }
    
    .premium-label {
        display: block;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }
    
    .premium-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 1rem;
        color: #334155;
        background-color: #f8fafc;
        transition: all 0.2s ease;
        box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.02);
    }
    
    .premium-input:focus {
        outline: none;
        border-color: #3b82f6;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    
    .input-hint {
        display: block;
        margin-top: 0.5rem;
        font-size: 0.85rem;
        color: #64748b;
        line-height: 1.4;
    }
    
    .premium-btn {
        background: linear-gradient(to right, #2563eb, #3b82f6);
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3), 0 2px 4px -1px rgba(59, 130, 246, 0.2);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .premium-btn:hover {
        background: linear-gradient(to right, #1d4ed8, #2563eb);
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4), 0 4px 6px -2px rgba(59, 130, 246, 0.2);
        transform: translateY(-1px);
    }
    
    .premium-btn:active {
        transform: translateY(1px);
        box-shadow: 0 2px 4px -1px rgba(59, 130, 246, 0.3);
    }
    
    .custom-alert {
        background-color: #ecfdf5;
        border-left: 4px solid #10b981;
        padding: 1rem 1.5rem;
        border-radius: 0 8px 8px 0;
        margin-bottom: 2rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #065f46;
        font-weight: 500;
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-10px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    .input-icon-wrapper {
        position: relative;
    }
    
    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }
    
    .premium-input.with-icon {
        padding-left: 3.2rem !important;
    }
</style>

<div class="ranking-config-container">
    @if (session('success_message'))
        <div class="custom-alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            {{ session('success_message') }}
        </div>
    @endif

    <div class="premium-card">
        <div class="premium-card-header">
            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                Configurações do Ranking
            </h2>
            <p>Ajuste os parâmetros que controlam como a pontuação é calculada e exibida na dashboard pública.</p>
        </div>
        
        <div class="premium-card-body">
            <form action="{{ route('regras-ranking.update') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="data_inicio_diario" class="premium-label">Data de Corte (Ranking Diário/Vigente)</label>
                    <div class="input-icon-wrapper">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <input type="date" class="premium-input with-icon" id="data_inicio_diario" name="data_inicio_diario" value="{{ $config->DATA_INICIO_DIARIO ?? date('Y-m-d') }}" required>
                    </div>
                    <span class="input-hint">Apenas os pedidos com <strong>vencimento a partir desta data</strong> serão contabilizados no ranking "Diário". Títulos com vencimento anterior aparecerão apenas no "Total".</span>
                </div>

                <div class="form-group">
                    <label for="pontos_pedido_pago" class="premium-label">Pontos por Pedido Pago</label>
                    <div class="input-icon-wrapper">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <input type="number" class="premium-input with-icon" id="pontos_pedido_pago" name="pontos_pedido_pago" value="{{ $config->PONTOS_PEDIDO_PAGO ?? 10 }}" min="0" required>
                    </div>
                    <span class="input-hint">Quantidade de pontos que o colaborador recebe quando suas baixas quitam 100% o valor de um pedido.</span>
                </div>

                <div class="form-group" style="margin-top: 2.5rem; text-align: right; border-top: 1px solid #f1f5f9; padding-top: 1.5rem;">
                    <button type="submit" class="premium-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
