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
    
    // Rotas de Teste Temporárias (Fake DB Test)
    Route::get('/teste-criar', function() {
        try {
            \Illuminate\Support\Facades\DB::statement("SET FOREIGN_KEY_CHECKS = 0;");
            
            // 1. Cria um Cliente Fake (ID 999999999)
            \Illuminate\Support\Facades\DB::statement("INSERT IGNORE INTO CONTATO_EXTERNO (ID_CONTATO_BLING, NOME_CONTATO, NUMERO_DOCUMENTO) VALUES (999999999, 'CLIENTE TESTE WEBHOOK', '00000000000')");
            \Illuminate\Support\Facades\DB::statement("INSERT IGNORE INTO CLIENTE (ID_CONTATO_BLING, EXIBIR, PEDRAS) VALUES (999999999, 1, 0)");
            
            // 2. Cria um Pedido Fake (ID 888888888) vinculado ao Cliente Fake e já vencido (para aparecer em Inadimplentes)
            \Illuminate\Support\Facades\DB::statement("
                INSERT INTO PEDIDO (ID_PEDIDO, NUM_PEDIDO, TOTAL_PEDIDO, DATA_VENCIMENTO, VALOR_PAGO_BLING, SITUACAO_PEDIDO, ID_CLIENTE, ID_FORMA_PAGAMENTO, EXIBIR)
                VALUES (888888888, 'test-webhook', 100.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 0.00, 1, 999999999, 1, 1)
                ON DUPLICATE KEY UPDATE SITUACAO_PEDIDO=1, EXIBIR=1, VALOR_PAGO_BLING=0, DATA_VENCIMENTO=DATE_SUB(CURDATE(), INTERVAL 5 DAY);
            ");
            
            \Illuminate\Support\Facades\DB::statement("SET FOREIGN_KEY_CHECKS = 1;");
            return "Pedido Fake de R$ 100 criado com sucesso! Volte na tela de Contas a Receber e procure por 'CLIENTE TESTE WEBHOOK'.";
        } catch (\Exception $e) {
            return "Erro ao criar: " . $e->getMessage();
        }
    });

    Route::get('/teste-cancelar', function() {
        // Simula o que o importarPedidos faz quando recebe situação 4 ou 5
        \Illuminate\Support\Facades\DB::statement("UPDATE PEDIDO SET SITUACAO_PEDIDO = 4, EXIBIR = 0 WHERE ID_PEDIDO = 888888888");
        return "Pedido Fake Cancelado (EXIBIR = 0)! Volte na tela de Contas a Receber, atualize a página e veja que ele sumiu.";
    });

    Route::any('/contas-receber/vincular-representantes', [ContasReceberController::class, 'vincularRepresentantes'])->name('vincular-reps-contas');
    Route::post('/contas-receber/baixar', [ContasReceberController::class, 'baixar'])->name('baixar-parcelas');
    Route::post('/contas-receber/recuperar', [ContasReceberController::class, 'recuperar'])->name('recuperar-parcelas');
    Route::post('/contas-receber/api/parcelas-por-ids', [ContasReceberController::class, 'apiParcelasPorIds'])->name('api-parcelas-contas');
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

    // Perfil
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil-page');
    Route::get('/perfil/api/pedidos', [PerfilController::class, 'apiPedidos'])->name('api-pedidos-perfil');
    // Exportação
    Route::get('/exportar/tudo', [ExportController::class, 'exportarTudo'])->name('exportar-tudo');
    Route::get('/exportar/{tabela}', [ExportController::class, 'exportarTabela'])->name('exportar-tabela');
});
