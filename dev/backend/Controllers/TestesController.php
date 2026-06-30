<?php
namespace App\Controllers;

use Illuminate\Support\Facades\DB;
use Exception;

class TestesController extends Controller {
    
    public function index() {
        return view('pages.testes.index');
    }

    public function executarTeste() {
        $log = [];
        $log[] = "[1] Iniciando teste automatizado...";

        try {
            DB::statement("SET FOREIGN_KEY_CHECKS = 0;");
            
            // 1. Criar Cliente Fake
            $log[] = "[2] Criando cliente falso (ID: 999999999)...";
            DB::statement("INSERT IGNORE INTO CONTATO_EXTERNO (ID_CONTATO_BLING, NOME_CONTATO, NUMERO_DOCUMENTO) VALUES (999999999, 'CLIENTE TESTE WEBHOOK', '00000000000')");
            DB::statement("INSERT IGNORE INTO CLIENTE (ID_CONTATO_BLING, EXIBIR, PEDRAS) VALUES (999999999, 1, 0)");
            
            // 2. Criar Pedido Fake
            $log[] = "[3] Inserindo pedido falso (ID: 888888888) no banco de dados...";
            DB::statement("
                INSERT INTO PEDIDO (ID_PEDIDO, NUM_PEDIDO, TOTAL_PEDIDO, DATA_VENCIMENTO, VALOR_PAGO_BLING, SITUACAO_PEDIDO, ID_CLIENTE, ID_FORMA_PAGAMENTO, EXIBIR)
                VALUES (888888888, 'test-webhook', 100.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 0.00, 1, 999999999, 1, 1)
                ON DUPLICATE KEY UPDATE SITUACAO_PEDIDO=1, EXIBIR=1, VALOR_PAGO_BLING=0, DATA_VENCIMENTO=DATE_SUB(CURDATE(), INTERVAL 5 DAY);
            ");
            
            // 3. Verifica se gravou
            $pedido = DB::selectOne("SELECT * FROM PEDIDO WHERE ID_PEDIDO = 888888888");
            if ($pedido) {
                $log[] = "[4] Sucesso! Pedido 888888888 localizado no banco (Valor: R$ " . $pedido->TOTAL_PEDIDO . ").";
            } else {
                throw new Exception("Pedido não foi encontrado após a inserção.");
            }

            // 4. Limpeza (Deletar tudo)
            $log[] = "[5] Limpando rastros e deletando cliente e pedido falsos...";
            DB::statement("DELETE FROM PEDIDO WHERE ID_PEDIDO = 888888888");
            DB::statement("DELETE FROM CLIENTE WHERE ID_CONTATO_BLING = 999999999");
            DB::statement("DELETE FROM CONTATO_EXTERNO WHERE ID_CONTATO_BLING = 999999999");

            $log[] = "[6] Teste finalizado com sucesso! A base de dados continua limpa.";

            DB::statement("SET FOREIGN_KEY_CHECKS = 1;");
            return response()->json(['status' => 'success', 'log' => $log]);

        } catch (Exception $e) {
            $log[] = "[X] Erro Crítico: " . $e->getMessage();
            // Tenta limpar em caso de erro
            DB::statement("DELETE FROM PEDIDO WHERE ID_PEDIDO = 888888888");
            DB::statement("DELETE FROM CLIENTE WHERE ID_CONTATO_BLING = 999999999");
            DB::statement("DELETE FROM CONTATO_EXTERNO WHERE ID_CONTATO_BLING = 999999999");
            DB::statement("SET FOREIGN_KEY_CHECKS = 1;");

            return response()->json(['status' => 'error', 'log' => $log], 500);
        }
    }
}
