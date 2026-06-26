<?php

use Illuminate\Support\Facades\Route;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\BlingController;
use App\Controllers\BlingWebhookController;
use App\Controllers\CobrancaController;
use App\Controllers\ContasReceberController;
use App\Controllers\ContatosController;
use App\Controllers\DivergenciaController;
use App\Controllers\PerfilController;

use App\Controllers\ExportController;
use App\Controllers\CsvExportController;

// Exportação Google Sheets (sem auth middleware para ser lido publicamente pelo Sheets)
Route::get('/api/exportar-contatos-csv', [CsvExportController::class, 'exportarContatos'])->name('api-export-csv');

// Autenticação
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout']); // Fallback para get se necessário

// Rotas de Debug (Sem Auth)
Route::get('/debug-erro', function() {
    ob_start();
    try {
        require_once __DIR__ . '/../Controllers/ContasReceberController.php';
        echo "Controller OK. ";
        $controller = new \App\Controllers\ContasReceberController();
        echo "Instanciou OK. ";
    } catch (\Throwable $e) {
        echo "Erro Fatal: " . $e->getMessage() . " na linha " . $e->getLine() . " do arquivo " . $e->getFile();
    }
    return ob_get_clean();
});

// Bling Callbacks (Geralmente sem auth para receber webhook ou retorno OAuth)
Route::get('/bling/callback', [BlingController::class, 'callback']);
Route::post('/bling/webhook', [BlingWebhookController::class, 'handle']);

// Rotas Protegidas
Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Bling
    Route::get('/config-bling', [BlingController::class, 'index'])->name('bling-page');
    Route::post('/bling/config', [BlingController::class, 'saveConfig'])->name('salvar-config-bling');
    Route::post('/bling/exibir-ate', [BlingController::class, 'saveExibirAte'])->name('salvar-exibir-ate');
    Route::get('/bling/auth', [BlingController::class, 'auth'])->name('autorizar-bling');
    Route::post('/bling/manual-callback', [BlingController::class, 'manualCallback'])->name('callback-manual-bling');

    // Cobrança
    Route::get('/cobranca', [CobrancaController::class, 'index'])->name('cobranca-page');
    Route::post('/cobranca/puxar', [CobrancaController::class, 'puxar'])->name('puxar-cobrancas');
    Route::post('/cobranca/atualizar-pedidos', [CobrancaController::class, 'atualizarPedidos'])->name('atualizar-pedidos-cobranca');
    Route::post('/cobranca/desistir', [CobrancaController::class, 'desistir'])->name('desistir-cobranca');
    Route::get('/cobranca/api/clientes', [CobrancaController::class, 'apiClientesAgrupamento'])->name('api-clientes-cobranca');

    // Contas a Receber
    Route::get('/contas-receber', [ContasReceberController::class, 'index'])->name('contas-receber-page');
    Route::get('/contas-receber/detalhes-contato-financeiro/{id}', [ContasReceberController::class, 'detalhesContatoFinanceiro'])->name('detalhes-contato-financeiro');
    Route::any('/contas-receber/sincronizar-unico', [ContasReceberController::class, 'sincronizarUnico'])->name('sincronizar-conta-unica');
    Route::any('/contas-receber/sincronizar', [ContasReceberController::class, 'sincronizar'])->name('sincronizar-contas-receber');
    Route::post('/contas-receber/atualizar', [ContasReceberController::class, 'atualizar'])->name('atualizar-contas-receber');
    Route::any('/contas-receber/vincular-representantes', [ContasReceberController::class, 'vincularRepresentantes'])->name('vincular-reps-contas');
    Route::post('/contas-receber/baixar', [ContasReceberController::class, 'baixar'])->name('baixar-parcelas');
    Route::post('/contas-receber/recuperar', [ContasReceberController::class, 'recuperar'])->name('recuperar-parcelas');
    Route::any('/contas-receber/api/parcelas-por-ids', [ContasReceberController::class, 'apiParcelasPorIds'])->name('api-parcelas-contas');
    Route::get('/contas-receber/api/lista', [ContasReceberController::class, 'apiLista'])->name('api-lista-contas');
    Route::get('/contas-receber/api/detalhe', [ContasReceberController::class, 'apiDetalhe'])->name('api-detalhe-conta');

    // Contatos
    Route::get('/contatos', [ContatosController::class, 'index'])->name('contatos-page');
    Route::post('/contatos/salvar-telefone', [ContatosController::class, 'salvarTelefone'])->name('salvar-telefone-contato');
    Route::post('/contatos/excluir-telefone', [ContatosController::class, 'excluirTelefone'])->name('excluir-telefone-contato');
    Route::post('/contatos/toggle-confirmado', [ContatosController::class, 'toggleConfirmado'])->name('toggle-telefone-confirmado');
    Route::post('/contatos/toggle-origem', [ContatosController::class, 'toggleOrigem'])->name('toggle-origem-telefone');
    Route::post('/contatos/salvar-contato-financeiro', [ContatosController::class, 'salvarContatoFinanceiro'])->name('salvar-contato-financeiro');
    Route::post('/contatos/excluir-contato-financeiro', [ContatosController::class, 'excluirContatoFinanceiro'])->name('excluir-contato-financeiro');
    Route::get('/contatos/api/contatos', [ContatosController::class, 'apiContatos'])->name('api-contatos');
    Route::get('/contatos/api/telefones', [ContatosController::class, 'apiTelefones'])->name('api-telefones');
    Route::any('/contatos/sincronizar-contatos', [ContatosController::class, 'sincronizarContatos'])->name('sincronizar-contatos-bling');
    Route::any('/contatos/sincronizar-vendedores', [ContatosController::class, 'sincronizarVendedores'])->name('sincronizar-vendedores-bling');
    Route::any('/contatos/sincronizar-unico', [ContatosController::class, 'sincronizarUnico'])->name('sincronizar-contato-unico');
    
    // Contatos - Importação
    Route::get('/contatos/importar', [ContatosController::class, 'importar'])->name('importar-contatos-page');
    Route::get('/contatos/importar/template', [ContatosController::class, 'downloadTemplate'])->name('baixar-template-importacao');
    Route::post('/contatos/importar/processar', [ContatosController::class, 'processarImportacao'])->name('processar-planilha-importacao');
    Route::post('/contatos/importar/mapeamento', [ContatosController::class, 'processarMapeamento'])->name('salvar-mapeamento-importacao');
    Route::post('/contatos/importar/confirmar', [ContatosController::class, 'confirmarImportacao'])->name('confirmar-importacao');
    Route::get('/contatos/importar/log', [ContatosController::class, 'logImportacao'])->name('log-importacao-page');

    // Divergências
    Route::get('/divergencias', [DivergenciaController::class, 'index'])->name('divergencias-page');
    Route::post('/divergencias/corrigir-baixa', [DivergenciaController::class, 'corrigirBaixa'])->name('corrigir-baixa');

    // Perfil
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil-page');
    Route::get('/perfil/api/pedidos', [PerfilController::class, 'apiPedidos'])->name('api-pedidos-perfil');
    // Exportação
    Route::get('/exportar/tudo', [ExportController::class, 'exportarTudo'])->name('exportar-tudo');
    Route::get('/exportar/{tabela}', [ExportController::class, 'exportarTabela'])->name('exportar-tabela');
});
