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
use App\Controllers\RankingConfigController;
use App\Controllers\ChequesController;

// Exportação Google Sheets (sem auth middleware para ser lido publicamente pelo Sheets)
Route::get('/api/exportar-contatos-csv', [CsvExportController::class, 'exportarContatos'])->name('api-export-csv');

// Autenticação
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout']); // Fallback para get se necessário

// Rota temporária para limpar nomes de colaboradores
Route::get('/limpar-nomes', function () {
    try {
        \Illuminate\Support\Facades\DB::statement("UPDATE COLABORADOR SET NOME_COLABORADOR = REPLACE(NOME_COLABORADOR, '@gmail.com', '') WHERE NOME_COLABORADOR LIKE '%@gmail.com'");
        return 'Nomes dos colaboradores atualizados com sucesso (removido @gmail.com)!';
    } catch (\Exception $e) {
        return 'Erro: ' . $e->getMessage();
    }
});
// Rota temporária para criar coluna STATUS_CHEQUE e atualizar views de ranking
Route::get('/update-ranking-cheques', function () {
    try {
        // 1. Adicionar coluna STATUS_CHEQUE na tabela PEDIDO
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `PEDIDO` ADD COLUMN `STATUS_CHEQUE` ENUM('pendente', 'compensado') NOT NULL DEFAULT 'pendente'");
        } catch (\Exception $e) {
            // Ignora erro se a coluna já existir
        }

        // 2. Recriar vw_ranking_diario
        \Illuminate\Support\Facades\DB::statement("DROP VIEW IF EXISTS `vw_ranking_diario`");
        \Illuminate\Support\Facades\DB::statement("
            CREATE VIEW `vw_ranking_diario` AS
            SELECT 
                c.ID_COLABORADOR,
                c.NOME_COLABORADOR,
                COUNT(DISTINCT rp.ID_REGISTRO) as QTD_BAIXAS,
                SUM(CASE WHEN p.ID_FORMA_PAGAMENTO = 7179734 AND p.STATUS_CHEQUE = 'pendente' THEN 0 ELSE dp.VALOR_PAGO_PEDIDO END) as TOTAL_RECEBIDO,
                SUM(CASE WHEN p.ID_FORMA_PAGAMENTO = 7179734 AND p.STATUS_CHEQUE = 'pendente' THEN dp.VALOR_PAGO_PEDIDO ELSE 0 END) as TOTAL_CHEQUES
            FROM REGISTRO_PAGAMENTO rp
            JOIN COLABORADOR c ON c.ID_COLABORADOR = rp.ID_COLABORADOR
            JOIN DETALHE_PAGAMENTO dp ON dp.ID_REGISTRO = rp.ID_REGISTRO
            JOIN PEDIDO p ON p.ID_PEDIDO = dp.ID_PEDIDO
            WHERE p.DATA_VENCIMENTO >= (SELECT DATA_INICIO_DIARIO FROM CONFIGURACOES_RANKING LIMIT 1)
            GROUP BY c.ID_COLABORADOR, c.NOME_COLABORADOR
        ");

        // 3. Recriar vw_ranking_total
        \Illuminate\Support\Facades\DB::statement("DROP VIEW IF EXISTS `vw_ranking_total`");
        \Illuminate\Support\Facades\DB::statement("
            CREATE VIEW `vw_ranking_total` AS
            SELECT 
                c.ID_COLABORADOR,
                c.NOME_COLABORADOR,
                COUNT(DISTINCT rp.ID_REGISTRO) as QTD_BAIXAS,
                SUM(CASE WHEN p.ID_FORMA_PAGAMENTO = 7179734 AND p.STATUS_CHEQUE = 'pendente' THEN 0 ELSE dp.VALOR_PAGO_PEDIDO END) as TOTAL_RECEBIDO,
                SUM(CASE WHEN p.ID_FORMA_PAGAMENTO = 7179734 AND p.STATUS_CHEQUE = 'pendente' THEN dp.VALOR_PAGO_PEDIDO ELSE 0 END) as TOTAL_CHEQUES,
                (
                    SELECT COUNT(DISTINCT p2.ID_PEDIDO) * (SELECT PONTOS_PEDIDO_PAGO FROM CONFIGURACOES_RANKING LIMIT 1)
                    FROM DETALHE_PAGAMENTO dp2
                    JOIN REGISTRO_PAGAMENTO rp2 ON rp2.ID_REGISTRO = dp2.ID_REGISTRO
                    JOIN PEDIDO p2 ON p2.ID_PEDIDO = dp2.ID_PEDIDO
                    WHERE rp2.ID_COLABORADOR = c.ID_COLABORADOR
                    AND (p2.VALOR_PAGO_BLING >= p2.TOTAL_PEDIDO OR (SELECT SUM(dp3.VALOR_PAGO_PEDIDO) FROM DETALHE_PAGAMENTO dp3 WHERE dp3.ID_PEDIDO = p2.ID_PEDIDO) >= p2.TOTAL_PEDIDO)
                ) as PONTOS_TOTAIS
            FROM REGISTRO_PAGAMENTO rp
            JOIN COLABORADOR c ON c.ID_COLABORADOR = rp.ID_COLABORADOR
            JOIN DETALHE_PAGAMENTO dp ON dp.ID_REGISTRO = rp.ID_REGISTRO
            JOIN PEDIDO p ON p.ID_PEDIDO = dp.ID_PEDIDO
            GROUP BY c.ID_COLABORADOR, c.NOME_COLABORADOR
        ");

        return 'Banco de dados e views de ranking (Cheques) atualizados com sucesso!';
    } catch (\Exception $e) {
        return 'Erro: ' . $e->getMessage();
    }
});

Route::get('/', [HomeController::class, 'index'])->name('home');

// Rotas Protegidas
Route::middleware('auth')->group(function () {
    // Regras do Ranking
    Route::get('/regras-ranking', [RankingConfigController::class, 'edit'])->name('regras-ranking.edit');
    Route::post('/regras-ranking', [RankingConfigController::class, 'update'])->name('regras-ranking.update');

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
    Route::get('/contas-receber/api-baixas-cliente', [ContasReceberController::class, 'apiBaixasCliente'])->name('api-baixas-cliente');
    Route::post('/contas-receber/toggle-pedra', [ContasReceberController::class, 'togglePedra'])->name('toggle-pedra-contas-receber');

    // Contas a Receber - Importação (Excel)
    Route::get('/contas-receber/importar', [\App\Controllers\BaixasImportController::class, 'importar'])->name('importar-baixas-page');
    Route::get('/contas-receber/importar/template', [\App\Controllers\BaixasImportController::class, 'downloadTemplate'])->name('baixar-template-baixas');
    Route::post('/contas-receber/importar/processar', [\App\Controllers\BaixasImportController::class, 'processarImportacao'])->name('processar-importacao-baixas');
    Route::post('/contas-receber/importar/mapeamento', [\App\Controllers\BaixasImportController::class, 'processarMapeamento'])->name('salvar-mapeamento-baixas');
    Route::post('/contas-receber/importar/confirmar', [\App\Controllers\BaixasImportController::class, 'confirmarImportacao'])->name('confirmar-importacao-baixas');
    Route::get('/contas-receber/importar/log', [\App\Controllers\BaixasImportController::class, 'logImportacao'])->name('log-importacao-baixas');

    // Contatos
    Route::get('/contatos', [ContatosController::class, 'index'])->name('contatos-page');
    Route::post('/contatos/salvar-telefone', [ContatosController::class, 'salvarTelefone'])->name('salvar-telefone-contato');
    Route::post('/contatos/excluir-telefone', [ContatosController::class, 'excluirTelefone'])->name('excluir-telefone-contato');
    Route::post('/contatos/toggle-confirmado', [ContatosController::class, 'toggleConfirmado'])->name('toggle-telefone-confirmado');
    Route::post('/contatos/toggle-origem', [ContatosController::class, 'toggleOrigem'])->name('toggle-origem-telefone');
    Route::post('/contatos/toggle-pedra', [ContatosController::class, 'togglePedra'])->name('toggle-pedra-contato');
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

    Route::post('/cobrancas/marcar-pago', [ContasReceberController::class, 'marcarPagoLocal']);

    Route::post('/baixas/editar', [\App\Controllers\BaixaController::class, 'editar']);
    Route::post('/baixas/estornar', [\App\Controllers\BaixaController::class, 'estornar']);

    // Cheques
    Route::get('/contas-receber/cheques', [ChequesController::class, 'index'])->name('cheques-page');
    Route::post('/contas-receber/cheques/{id}/compensar', [ChequesController::class, 'compensar'])->name('cheques-compensar');
    Route::post('/contas-receber/cheques/{id}/descompensar', [ChequesController::class, 'descompensar'])->name('cheques-descompensar');
    Route::post('/contas-receber/cheques/{id}/devolver', [ChequesController::class, 'devolver'])->name('cheques-devolver');
    Route::post('/contas-receber/cheques/{id}/converter', [ChequesController::class, 'converterParaCheque'])->name('cheques-converter');

    // Divergências (ações, sem página própria — integrado na aba Baixas do Contas a Receber)
    Route::get('/divergencias/api-divergencias-cliente', [DivergenciaController::class, 'apiDivergenciasCliente']);
    Route::post('/divergencias/corrigir-baixa', [DivergenciaController::class, 'corrigirBaixa'])->name('corrigir-baixa');
    Route::post('/divergencias/estornar', [DivergenciaController::class, 'estornarBaixa']);

    // Perfil
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil-page');
    Route::get('/perfil/api/pedidos', [PerfilController::class, 'apiPedidos'])->name('api-pedidos-perfil');
    Route::get('/perfil/api-baixas-colaborador', [PerfilController::class, 'apiBaixasColaborador']);
    // Exportação
    Route::get('/exportar/tudo', [ExportController::class, 'exportarTudo'])->name('exportar-tudo');
    Route::get('/exportar/{tabela}', [ExportController::class, 'exportarTabela'])->name('exportar-tabela');

    // Testes de Sistema (Isolados)
    Route::get('/testes', [\App\Controllers\TestesController::class, 'index'])->name('testes-page');
    Route::post('/testes/baixas', [\App\Controllers\TestesController::class, 'testarBaixas']);
    Route::post('/testes/contatos', [\App\Controllers\TestesController::class, 'testarContatos']);
    Route::post('/testes/webhook', [\App\Controllers\TestesController::class, 'testarWebhook']);
    Route::post('/testes/cobrancas', [\App\Controllers\TestesController::class, 'testarCobrancas']);
    Route::post('/testes/divergencias', [\App\Controllers\TestesController::class, 'testarDivergencias']);
});
