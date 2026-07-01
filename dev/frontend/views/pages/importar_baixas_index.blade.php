@extends('layouts.app')

@section('title', 'Importar Baixas')
@section('body_class', 'contatos-page scrollable-page')

@section('content')
<style>
.scrollable-page main,
.scrollable-page .contatos-wrapper {
    height: auto !important;
    overflow: visible !important;
}
</style>

<div class="contatos-wrapper">
    <div class="contatos-header-actions">
        <div class="header-title-section">
            <h2>Importação de Baixas</h2>
            <p>Importe baixas de contas a receber (planilhas Excel) em lote.</p>
        </div>
        <div class="actions-buttons">
            <a href="{{ route('contas-receber-page') }}" class="btn-cancel">
                <x-icons.arrow-left width="16" height="16" />
                Voltar
            </a>
            <a href="{{ route('historico-importacao-baixas') }}" class="btn-sync" title="Histórico de Importações" style="background: #64748b;">
                <x-icons.history width="16" height="16" />
                Histórico
            </a>
            <a href="{{ route('baixar-template-baixas') }}" class="btn-sync secondary" title="Baixar planilha base">
                <x-icons.download width="16" height="16" />
                Baixar Planilha Base
            </a>
        </div>
    </div>

    @if(session('flash_msg'))
        <div class="flash-message">
            <span>{{ session('flash_msg') }}</span>
            <button class="flash-close" onclick="this.parentElement.remove()">✕</button>
        </div>
    @endif

    <div class="card" style="max-width: 800px; margin: 20px auto; padding: 30px;">
        <form action="{{ route('processar-importacao-baixas') }}" method="POST" enctype="multipart/form-data" class="import-form">
            @csrf
            
            <div id="drop-zone" class="form-group" style="margin-bottom: 25px; text-align: center; padding: 40px; border: 2px dashed #ccc; border-radius: 8px; background: #f9f9f9; cursor: pointer; transition: all 0.3s ease;">
                <x-icons.upload-1 width="48" height="48" style="margin-bottom: 15px;" />
                <h3 style="pointer-events: none;">Arraste o arquivo XLSX (Excel) aqui</h3>
                <p style="color: #666; margin-bottom: 15px; pointer-events: none;">Ou clique para selecionar na sua máquina</p>
                <input type="file" name="arquivo_xlsx" accept=".xlsx" required id="arquivo_xlsx" style="display: none;">
                <p id="file-name-display" style="color: #0d6efd; font-weight: 500; margin-top: 15px;"></p>
            </div>

            <div style="text-align: right;">
                <button type="submit" class="btn-sync" style="cursor:pointer; width: 100%; justify-content: center; padding: 15px; font-size: 16px;">
                    <x-icons.check-circle width="18" height="18" />
                    Ler Arquivo e Mapear Colunas
                </button>
            </div>
        </form>

        <div class="col-md-6 mb-4" style="margin-top: 40px;">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Como funciona?</h5>
                </div>
                <div class="card-body">
                    <p>A importação permite dar baixa (registrar pagamento) em vários pedidos ao mesmo tempo enviando uma planilha Excel.</p>
                    
                    <h6>Colunas Recomendadas:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Número do Pedido:</strong> Usado para encontrar a conta no banco de dados.</li>
                        <li><strong>Valor Pago:</strong> Opcional. Se estiver na planilha, tentaremos deduzir exatamente este valor.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // JS básico para o drag and drop
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('arquivo_xlsx');
    const fileNameDisplay = document.getElementById('file-name-display');

    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
        if(fileInput.files.length > 0) {
            fileNameDisplay.textContent = fileInput.files[0].name;
            dropZone.style.borderColor = '#0d6efd';
            dropZone.style.background = '#e9f2ff';
        }
    });
</script>
@endsection
