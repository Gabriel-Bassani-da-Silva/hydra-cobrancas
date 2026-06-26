<?php
/*
|--------------------------------------------------------------------------
| Rotas de Teste - Webhook Bling (Contas a Receber)
|--------------------------------------------------------------------------
|
| Este arquivo contém as rotas criadas para testar a criação, cancelamento
| e exclusão de pedidos via webhook, contornando a limitação da API v3 do Bling.
|
| Para utilizar no futuro, basta copiar as rotas abaixo e colar no seu
| arquivo `dev/backend/Routes/web.php`.
| IMPORTANTE: Lembre-se de remover do web.php quando for subir para produção!
|
*/

use Illuminate\Support\Facades\Route;

// 1. Rota para CRIAR um pedido de teste vencido
Route::get('/teste-criar', function() {
    try {
        \Illuminate\Support\Facades\DB::statement("SET FOREIGN_KEY_CHECKS = 0;");
        
        // 1. Cria um Cliente Fake (ID 999999999)
        \Illuminate\Support\Facades\DB::statement("INSERT IGNORE INTO CONTATO_EXTERNO (ID_CONTATO_BLING, NOME_CONTATO, NUMERO_DOCUMENTO) VALUES (999999999, 'CLIENTE TESTE WEBHOOK', '00000000000')");
        \Illuminate\Support\Facades\DB::statement("INSERT IGNORE INTO CLIENTE (ID_CONTATO_BLING, EXIBIR, PEDRAS) VALUES (999999999, 1, 0)");
        
        // 2. Cria um Pedido Fake (ID 888888888) vinculado ao Cliente Fake e já vencido há 5 dias
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

// 2. Rota para CANCELAR / ESCONDER o pedido de teste (Simulando o Webhook)
Route::get('/teste-cancelar', function() {
    // Simula o que o importarPedidos faz quando recebe situação 4 (Cancelado) ou 5 (Excluído)
    \Illuminate\Support\Facades\DB::statement("UPDATE PEDIDO SET SITUACAO_PEDIDO = 4, EXIBIR = 0 WHERE ID_PEDIDO = 888888888");
    return "Pedido Fake Cancelado (EXIBIR = 0)! Volte na tela de Contas a Receber, atualize a página e veja que ele sumiu.";
});

// 3. Rota para DELETAR FISICAMENTE o pedido do banco de dados
Route::get('/teste-deletar', function() {
    // Deleta fisicamente apenas o pedido de teste do banco
    \Illuminate\Support\Facades\DB::statement("DELETE FROM PEDIDO WHERE ID_PEDIDO = 888888888");
    // Se quiser deletar o cliente também, descomente as linhas abaixo:
    // \Illuminate\Support\Facades\DB::statement("DELETE FROM CLIENTE WHERE ID_CONTATO_BLING = 999999999");
    // \Illuminate\Support\Facades\DB::statement("DELETE FROM CONTATO_EXTERNO WHERE ID_CONTATO_BLING = 999999999");
    return "Pedido deletado fisicamente! O cliente ainda existe no banco, mas como ele não tem mais parcelas a receber, ele também deve sumir da tela de Contas a Receber.";
});
