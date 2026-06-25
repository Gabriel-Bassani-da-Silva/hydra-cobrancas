@extends('layouts.app')

@section('title', 'Importar Contatos')
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
            <h2>Importação de Contatos</h2>
            <p>Importe clientes, representantes e contatos financeiros em lote através de uma planilha CSV.</p>
        </div>
        <div class="actions-buttons">
            <a href="{{ route('contatos-page') }}" class="btn-cancel">
                <x-icons.arrow-left width="16" height="16" />
                Voltar para Contatos
            </a>
            <a href="{{ route('baixar-template-importacao') }}" class="btn-sync secondary" title="Baixar planilha base">
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
        <form action="{{ route('processar-planilha-importacao') }}" method="POST" enctype="multipart/form-data" class="import-form">
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
                    Processar Importação
                </button>
            </div>
        </form>

            <div class="col-md-6 mb-4" style="margin-top: 40px;">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Como funciona?</h5>
                    </div>
                    <div class="card-body">
                        <p>A importação permite que você adicione ou atualize dezenas de contatos e telefones de uma vez só usando uma planilha Excel (<code>.xlsx</code>).</p>
                        
                        <h6>Colunas Obrigatórias:</h6>
                        <ul class="list-unstyled">
                            <li><strong>NOME:</strong> Nome completo ou Razão Social</li>
                            <li><strong>CPF_CNPJ:</strong> Apenas os números. Usado para não duplicar contatos existentes.</li>
                            <li><strong>TELEFONE:</strong> Número do telefone com DDD (apenas números).</li>
                            <li><strong>STATUS:</strong> Escreva <code>Confirmado</code> ou <code>Tentativa</code>.</li>
                        </ul>

                        <div class="alert alert-info mt-3 mb-0">
                            <strong>Dica:</strong> Clique em "Baixar Planilha Base" para obter um arquivo já com as colunas certas para preencher!
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>


<script src="{{ asset('js/importar_contatos.js') }}?v=<?= time() ?>"></script>

@endsection
