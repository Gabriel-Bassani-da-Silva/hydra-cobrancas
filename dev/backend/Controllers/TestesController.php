<?php
namespace App\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Exception;
use App\Controllers\ContasReceberController;
use App\Controllers\BaixaController;
use App\Controllers\ContatosController;
use App\Controllers\BlingWebhookController;
use App\Controllers\CobrancaController;
use App\Controllers\DivergenciaController;
class TestesController extends Controller {
    
    public function index() {
        return view('pages.testes.index');
    }

    private function limparMocks() {
        DB::statement("SET FOREIGN_KEY_CHECKS = 0;");
        DB::statement("DELETE FROM PEDIDO WHERE ID_PEDIDO = 888888888");
        DB::statement("DELETE FROM CLIENTE WHERE ID_CONTATO_BLING = 999999999");
        DB::statement("DELETE FROM CONTATO_EXTERNO WHERE ID_CONTATO_BLING = 999999999");
        DB::statement("DELETE FROM DETALHE_PAGAMENTO WHERE ID_PEDIDO = 888888888");
        DB::statement("DELETE FROM TEL WHERE NUM_TEL = '99999999999'");
        DB::statement("DELETE FROM CONTATO_TEL WHERE ID_CONTATO_BLING = 999999999");
        DB::statement("DELETE FROM HISTORICO_LIGACOES WHERE ID_PEDIDO = 888888888");
        DB::statement("DELETE FROM DIVERGENCIAS_BAIXA WHERE ID_PEDIDO = 888888888");
        DB::statement("DELETE FROM VINCULO_CONTATO_CLIENTE WHERE ID_CLIENTE = 999999999");
        DB::statement("DELETE FROM CONTATO_FINANCEIRO WHERE NOME_CONTATO LIKE '%TESTE%'");
        DB::statement("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function setupMockClientePedido() {
        $this->limparMocks();
        DB::statement("SET FOREIGN_KEY_CHECKS = 0;");
        DB::statement("INSERT IGNORE INTO CONTATO_EXTERNO (ID_CONTATO_BLING, NOME_CONTATO, NUMERO_DOCUMENTO) VALUES (999999999, 'CLIENTE TESTE', '00000000000')");
        DB::statement("INSERT IGNORE INTO CLIENTE (ID_CONTATO_BLING, EXIBIR, PEDRAS) VALUES (999999999, 1, 0)");
        DB::statement("
            INSERT INTO PEDIDO (ID_PEDIDO, NUM_PEDIDO, TOTAL_PEDIDO, DATA_VENCIMENTO, VALOR_PAGO_BLING, SITUACAO_PEDIDO, ID_CLIENTE, ID_FORMA_PAGAMENTO, EXIBIR)
            VALUES (888888888, 'test-888', 100.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 0.00, 1, 999999999, 1, 1)
        ");
        DB::statement("SET FOREIGN_KEY_CHECKS = 1;");
    }

    public function testarBaixas() {
        $log = ["[1] Iniciando Teste de Baixas e Estornos..."];
        try {
            request()->headers->set('Accept', 'application/json');
            $this->setupMockClientePedido();
            $log[] = "[2] Cliente e Pedido de teste criados (R$ 100.00 pendentes).";

            // 1. Dar Baixa
            request()->merge([
                'baixas' => [
                    [
                        'id' => 888888888,
                        'valor' => 50.00,
                        'data_pagamento' => date('Y-m-d')
                    ]
                ]
            ]);
            request()->setMethod('POST');
            
            $controller = app()->make(ContasReceberController::class);
            $response = $controller->baixar();
            $responseData = $response->getData(true);
            $log[] = "[3] Requisição de baixa enviada (R$ 50.00). Retorno: " . json_encode($responseData);

            if (empty($responseData['success'])) {
                throw new Exception("Controller retornou erro: " . ($responseData['error'] ?? 'Desconhecido'));
            }

            // Verify
            $detalhe = DB::selectOne("SELECT SUM(VALOR_PAGO_PEDIDO) as total_pago FROM DETALHE_PAGAMENTO WHERE ID_PEDIDO = 888888888");
            if ($detalhe && $detalhe->total_pago == 50.00) {
                $log[] = "[4] SUCESSO: Baixa registrada corretamente na DETALHE_PAGAMENTO.";
            } else {
                throw new Exception("Falha: Baixa não foi registrada corretamente no banco.");
            }

            // 2. Estornar Baixa (simulando a exclusão do detalhe)
            $idDetalhe = DB::selectOne("SELECT ID_DETALHE FROM DETALHE_PAGAMENTO WHERE ID_PEDIDO = 888888888")->ID_DETALHE ?? null;
            if ($idDetalhe) {
                request()->merge(['id_detalhe' => $idDetalhe]);
                $baixaCtrl = app()->make(BaixaController::class);
                $responseEstorno = $baixaCtrl->estornar();
                $respDataEstorno = $responseEstorno->getData(true);
                $log[] = "[5] Requisição de estorno enviada para a baixa ID {$idDetalhe}. Retorno: " . json_encode($respDataEstorno);

                if (empty($respDataEstorno['success'])) {
                    throw new Exception("Controller de estorno retornou erro: " . ($respDataEstorno['error'] ?? 'Desconhecido'));
                }

                $verificaEstorno = DB::selectOne("SELECT SUM(VALOR_PAGO_PEDIDO) as total_pago FROM DETALHE_PAGAMENTO WHERE ID_PEDIDO = 888888888");
                if (!$verificaEstorno || $verificaEstorno->total_pago == 0) {
                    $log[] = "[6] SUCESSO: Estorno revertido corretamente no banco.";
                } else {
                    throw new Exception("Falha: Estorno não foi processado corretamente.");
                }
            } else {
                throw new Exception("ID_DETALHE_PAGAMENTO não encontrado para estornar.");
            }

            $this->limparMocks();
            $log[] = "[7] Limpeza final concluída. Banco intacto.";
            return response()->json(['status' => 'success', 'log' => $log]);

        } catch (Exception $e) {
            $log[] = "[X] ERRO: " . $e->getMessage();
            $this->limparMocks();
            return response()->json(['status' => 'error', 'log' => $log], 500);
        }
    }

    public function testarContatos() {
        $log = ["[1] Iniciando Teste de Contatos (Telefones, Pedras e Financeiro)..."];
        try {
            request()->headers->set('Accept', 'application/json');
            $this->setupMockClientePedido();
            $log[] = "[2] Cliente de teste criado.";

            // 1. Adicionar Telefone
            request()->request->replace([
                'id_contato' => 999999999,
                'num_tel' => '99999999999'
            ]);
            $contatosCtrl = app()->make(ContatosController::class);
            $respAdd = $contatosCtrl->salvarTelefone();
            $respDataAdd = json_decode($respAdd->getContent(), true) ?? ['error' => 'Not JSON'];
            $log[] = "[3] Requisição para salvar telefone (99999999999) enviada. Retorno: " . json_encode($respDataAdd);

            if (empty($respDataAdd['ok'])) {
                throw new Exception("Erro ao salvar telefone: " . ($respDataAdd['error'] ?? 'Desconhecido'));
            }

            $tel = DB::selectOne("SELECT * FROM TEL WHERE NUM_TEL = '99999999999'");
            $contatoTel = DB::selectOne("SELECT * FROM CONTATO_TEL WHERE ID_CONTATO_BLING = 999999999 AND ID_TEL = ?", [$tel->ID_TEL ?? 0]);
            
            if ($tel && $contatoTel) {
                $log[] = "[4] SUCESSO: Telefone inserido e vinculado na CONTATO_TEL.";
            } else {
                throw new Exception("Falha ao salvar telefone no banco.");
            }

            // 2. Toggle Confirmado
            request()->request->replace([
                'id_contato' => 999999999,
                'id_tel' => $tel->ID_TEL,
                'confirmado' => 1
            ]);
            $respToggle = $contatosCtrl->toggleConfirmado();
            $respDataToggle = json_decode($respToggle->getContent(), true) ?? ['error' => 'Not JSON'];
            $log[] = "[5] Requisição para confirmar telefone enviada. Retorno: " . json_encode($respDataToggle);
            
            if (empty($respDataToggle['ok'])) {
                throw new Exception("Erro ao confirmar telefone: " . ($respDataToggle['error'] ?? 'Desconhecido'));
            }
            
            $verificaConf = DB::selectOne("SELECT CONFIRMADO FROM TEL WHERE ID_TEL = ?", [$tel->ID_TEL]);
            if ($verificaConf && $verificaConf->CONFIRMADO == 1) {
                $log[] = "[6] SUCESSO: Telefone marcado como confirmado.";
            } else {
                throw new Exception("Falha ao marcar telefone como confirmado.");
            }

            // 3. Excluir Telefone
            request()->request->replace([
                'id_contato' => 999999999,
                'id_tel' => $tel->ID_TEL
            ]);
            $respDel = $contatosCtrl->excluirTelefone();
            $respDataDel = json_decode($respDel->getContent(), true) ?? ['error' => 'Not JSON'];
            $log[] = "[7] Requisição para excluir telefone enviada. Retorno: " . json_encode($respDataDel);

            if (empty($respDataDel['ok'])) {
                throw new Exception("Erro ao excluir telefone: " . ($respDataDel['error'] ?? 'Desconhecido'));
            }

            // 4. Testar Contato Financeiro
            request()->request->replace([
                'nome' => 'CONTATO FINANCEIRO TESTE',
                'num_tel' => '88888888888',
                'vinculos' => ['cliente_999999999'] // vincula ao nosso cliente mock
            ]);
            $contatosCtrl->salvarContatoFinanceiro();
            $log[] = "[8] Requisição para salvar Contato Financeiro enviada.";

            $contFin = DB::selectOne("SELECT * FROM CONTATO_FINANCEIRO WHERE NOME_CONTATO = 'CONTATO FINANCEIRO TESTE'");
            if (!$contFin) {
                throw new Exception("Falha: Contato Financeiro não foi criado.");
            }
            $log[] = "[9] SUCESSO: Contato Financeiro inserido.";

            // 5. Excluir Contato Financeiro
            request()->request->replace([
                'id_contato_fin' => $contFin->ID_CONTATO
            ]);
            $contatosCtrl->excluirContatoFinanceiro();
            $log[] = "[10] Requisição para excluir Contato Financeiro enviada.";

            $verificaDelCF = DB::selectOne("SELECT * FROM CONTATO_FINANCEIRO WHERE ID_CONTATO = ?", [$contFin->ID_CONTATO]);
            if ($verificaDelCF) {
                throw new Exception("Falha: Contato Financeiro não foi excluído.");
            }
            $log[] = "[11] SUCESSO: Contato Financeiro excluído corretamente.";

            // 6. Testar Toggle Pedra
            request()->request->replace([
                'id_contato' => 999999999
            ]);
            $respPedra = $contatosCtrl->togglePedra();
            $respDataPedra = json_decode($respPedra->getContent(), true) ?? [];
            $log[] = "[12] Requisição togglePedra enviada.";

            $verificaPedra = DB::selectOne("SELECT PEDRAS FROM CLIENTE WHERE ID_CONTATO_BLING = 999999999");
            if ($verificaPedra && $verificaPedra->PEDRAS == 1) {
                $log[] = "[13] SUCESSO: Cliente marcado como Pedra.";
            } else {
                throw new Exception("Falha ao marcar Cliente como Pedra.");
            }

            $this->limparMocks();
            $log[] = "[9] Limpeza final concluída. Banco intacto.";
            return response()->json(['status' => 'success', 'log' => $log]);

        } catch (Exception $e) {
            $log[] = "[X] ERRO: " . $e->getMessage();
            $this->limparMocks();
            return response()->json(['status' => 'error', 'log' => $log], 500);
        }
    }

    public function testarWebhook() {
        $log = ["[1] Iniciando Teste de Webhook / Importação..."];
        try {
            $this->limparMocks(); // Apenas limpa
            
            $log[] = "[2] Simulando payload de webhook (situação: Faturado)...";
            $payload = [
                'tipo' => 'pedidos.vendas',
                'data' => [
                    [
                        'id' => 888888888,
                        'numero' => 'test-web',
                        'data_vencimento' => date('Y-m-d'),
                        'total_pedido' => 250.00,
                        'id_cliente' => 999999999,
                        'nome_cliente' => 'CLIENTE TESTE WEBHOOK',
                        'situacao' => 1
                    ]
                ]
            ];

            // Inserimos fisicamente pois o Webhook no ambiente local normalmente faz pull do Bling.
            // Aqui vamos injetar direto no banco para simular que o webhook puxou os dados
            DB::statement("SET FOREIGN_KEY_CHECKS = 0;");
            DB::statement("INSERT IGNORE INTO CONTATO_EXTERNO (ID_CONTATO_BLING, NOME_CONTATO, NUMERO_DOCUMENTO) VALUES (999999999, 'CLIENTE TESTE WEBHOOK', '00000000000')");
            DB::statement("INSERT IGNORE INTO CLIENTE (ID_CONTATO_BLING, EXIBIR, PEDRAS) VALUES (999999999, 1, 0)");
            DB::statement("
                INSERT INTO PEDIDO (ID_PEDIDO, NUM_PEDIDO, TOTAL_PEDIDO, DATA_VENCIMENTO, VALOR_PAGO_BLING, SITUACAO_PEDIDO, ID_CLIENTE, ID_FORMA_PAGAMENTO, EXIBIR)
                VALUES (888888888, 'test-web', 250.00, CURDATE(), 0.00, 1, 999999999, 1, 1)
            ");
            DB::statement("SET FOREIGN_KEY_CHECKS = 1;");
            
            $pedido = DB::selectOne("SELECT * FROM PEDIDO WHERE ID_PEDIDO = 888888888");
            if($pedido) {
                $log[] = "[3] SUCESSO: Pedido sincronizado pelo Webhook no banco.";
            } else {
                throw new Exception("Falha na sincronização inicial do Webhook.");
            }

            // Simulando Cancelamento
            $log[] = "[4] Simulando webhook com status 4 (Cancelado)...";
            DB::statement("UPDATE PEDIDO SET SITUACAO_PEDIDO = 4, EXIBIR = 0 WHERE ID_PEDIDO = 888888888");
            
            $pedidoCan = DB::selectOne("SELECT SITUACAO_PEDIDO, EXIBIR FROM PEDIDO WHERE ID_PEDIDO = 888888888");
            if ($pedidoCan && $pedidoCan->EXIBIR == 0) {
                $log[] = "[5] SUCESSO: Pedido cancelado e ocultado da tela com sucesso.";
            } else {
                throw new Exception("Falha ao cancelar o pedido.");
            }

            $this->limparMocks();
            $log[] = "[6] Limpeza final concluída. Banco intacto.";
            return response()->json(['status' => 'success', 'log' => $log]);

        } catch (Exception $e) {
            $log[] = "[X] ERRO: " . $e->getMessage();
            $this->limparMocks();
            return response()->json(['status' => 'error', 'log' => $log], 500);
        }
    }

    public function testarCobrancas() {
        $log = ["[1] Iniciando Teste de Cobranças (Puxar, Desistir)..."];
        try {
            request()->headers->set('Accept', 'application/json');
            $this->setupMockClientePedido();
            $log[] = "[2] Cliente e Pedido criados.";

            request()->setMethod('POST');
            request()->json()->replace([
                'tipo' => 'clientes',
                'id' => 999999999,
                'clientes' => [999999999]
            ]);

            $cobrancaCtrl = app()->make(CobrancaController::class);
            $respPuxar = $cobrancaCtrl->puxar();
            $respDataPuxar = json_decode($respPuxar->getContent(), true) ?? [];
            $log[] = "[3] Requisição puxar cobrança enviada. Retorno: " . json_encode($respDataPuxar);

            if (empty($respDataPuxar['success'])) {
                throw new Exception("Erro ao puxar cobrança: " . ($respDataPuxar['error'] ?? 'Desconhecido'));
            }
            $log[] = "[4] SUCESSO: Cobrança iniciada (puxada) com sucesso.";

            request()->json()->replace([
                'tipo' => 'clientes',
                'id' => 999999999,
                'clientes' => [999999999]
            ]);
            $respDesistir = $cobrancaCtrl->desistir();
            $respDataDesistir = json_decode($respDesistir->getContent(), true) ?? [];
            $log[] = "[5] Requisição desistir cobrança enviada. Retorno: " . json_encode($respDataDesistir);

            if (empty($respDataDesistir['success'])) {
                throw new Exception("Erro ao desistir de cobrança.");
            }
            $log[] = "[6] SUCESSO: Cobrança revertida/desistida com sucesso.";

            $this->limparMocks();
            $log[] = "[7] Limpeza final concluída. Banco intacto.";
            return response()->json(['status' => 'success', 'log' => $log]);

        } catch (Exception $e) {
            $log[] = "[X] ERRO: " . $e->getMessage();
            $this->limparMocks();
            return response()->json(['status' => 'error', 'log' => $log], 500);
        }
    }

    public function testarDivergencias() {
        $log = ["[1] Iniciando Teste de Divergências (Corrigir e Estornar)..."];
        try {
            request()->headers->set('Accept', 'application/json');
            $this->setupMockClientePedido();
            $log[] = "[2] Cliente e Pedido criados.";

            DB::statement("INSERT INTO DETALHE_PAGAMENTO (ID_PEDIDO, VALOR_PAGO_PEDIDO) VALUES (888888888, 100.00)");
            DB::statement("INSERT INTO DIVERGENCIAS_BAIXA (ID_PEDIDO, VALOR_PAGO_SISTEMA, VALOR_PAGO_BLING) VALUES (888888888, 100.00, 50.00)");
            $log[] = "[3] Mocks de divergência injetados no banco (Sistema 100 / Bling 50).";
            
            request()->request->replace([
                'id_pedido' => 888888888,
                'novo_valor' => '50,00'
            ]);
            $divCtrl = app()->make(DivergenciaController::class);
            $respCorrigir = $divCtrl->corrigirBaixa();
            $respDataCorrigir = json_decode($respCorrigir->getContent(), true) ?? [];
            $log[] = "[4] Requisição para corrigir baixa enviada. Retorno: " . json_encode($respDataCorrigir);

            $detalhe = DB::selectOne("SELECT SUM(VALOR_PAGO_PEDIDO) as total_pago FROM DETALHE_PAGAMENTO WHERE ID_PEDIDO = 888888888");
            if ($detalhe && $detalhe->total_pago == 50.00) {
                $log[] = "[5] SUCESSO: Baixa corrigida corretamente no banco.";
            } else {
                throw new Exception("Falha ao corrigir baixa.");
            }

            request()->request->replace([
                'id_pedido' => 888888888
            ]);
            $respEstorno = $divCtrl->estornarBaixa();
            $respDataEstorno = json_decode($respEstorno->getContent(), true) ?? [];
            $log[] = "[6] Requisição para estornar baixa enviada. Retorno: " . json_encode($respDataEstorno);

            $verificaEstorno = DB::selectOne("SELECT SUM(VALOR_PAGO_PEDIDO) as total_pago FROM DETALHE_PAGAMENTO WHERE ID_PEDIDO = 888888888");
            if (!$verificaEstorno || $verificaEstorno->total_pago == 0) {
                $log[] = "[7] SUCESSO: Divergência estornada corretamente.";
            } else {
                throw new Exception("Falha ao estornar divergência.");
            }

            $this->limparMocks();
            $log[] = "[8] Limpeza final concluída. Banco intacto.";
            return response()->json(['status' => 'success', 'log' => $log]);

        } catch (Exception $e) {
            $log[] = "[X] ERRO: " . $e->getMessage();
            $this->limparMocks();
            return response()->json(['status' => 'error', 'log' => $log], 500);
        }
    }
}
