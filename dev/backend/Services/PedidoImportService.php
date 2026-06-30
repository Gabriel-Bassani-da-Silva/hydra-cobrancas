<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoImportService {

    public function importarPedidos(array $contas, $blingService = null, string $mode = 'upsert'): array {
        $inseridos = 0;
        $atualizados = 0;
        $ignorados = 0;

        if (empty($contas)) {
            return ['inseridos' => 0, 'atualizados' => 0, 'ignorados' => 0];
        }

        // Carrega o mapa de ID_VENDEDOR para ID_CONTATO_BLING
        $stmtRep = DB::connection()->getPdo()->query("SELECT ID_VENDEDOR, ID_CONTATO_BLING FROM REPRESENTANTE");
        $mapaRepresentantes = [];
        while ($row = $stmtRep->fetch(\PDO::FETCH_ASSOC)) {
            $mapaRepresentantes[$row['ID_VENDEDOR']] = $row['ID_CONTATO_BLING'];
        }

        // 1. Pre-carregar IDs existentes e VALOR_PAGO_BLING atual
        $allIds = array_filter(array_column($contas, 'id'));
        $existingData = [];
        if (!empty($allIds)) {
            $chunks = array_chunk($allIds, 500);
            foreach ($chunks as $chunk) {
                $inQuery = implode(',', array_fill(0, count($chunk), '?'));
                $stmtIds = DB::connection()->getPdo()->prepare("SELECT ID_PEDIDO, VALOR_PAGO_BLING, NUM_PEDIDO, ID_REPRESENTANTE FROM PEDIDO WHERE ID_PEDIDO IN ($inQuery)");
                $stmtIds->execute(array_values($chunk));
                while ($row = $stmtIds->fetch(\PDO::FETCH_ASSOC)) {
                    $existingData[$row['ID_PEDIDO']] = [
                        'valor' => (float)$row['VALOR_PAGO_BLING'],
                        'num_pedido' => $row['NUM_PEDIDO'],
                        'id_representante' => $row['ID_REPRESENTANTE']
                    ];
                }
            }
        }

        $registrosParaInserir = [];

        foreach ($contas as $conta) {
            $id = $conta['id'] ?? null;
            if (!$id) {
                $ignorados++;
                continue;
            }

            $existsInDb = isset($existingData[$id]);

            if ($mode === 'insert' && $existsInDb) {
                $ignorados++;
                continue;
            }
            if ($mode === 'update' && !$existsInDb) {
                $ignorados++;
                continue;
            }

            $situacaoBling = !empty($conta['situacao']) ? (int)$conta['situacao'] : 1;
            if ($situacaoBling < 1 || $situacaoBling > 5) $situacaoBling = 1;

            // Se for novo e ja vier cancelado (4) ou excluido (5), ignora
            if (!$existsInDb && ($situacaoBling === 4 || $situacaoBling === 5)) {
                $ignorados++;
                continue;
            }

            $idContato = $conta['contato']['id'] ?? null;
            if (!$idContato) {
                $ignorados++;
                continue;
            }

            $valorPagoBling = ($situacaoBling === 1) ? 0 : ($existingData[$id]['valor'] ?? 0);
            if ($situacaoBling === 2) {
                $valorPagoBling = $conta['valor'] ?? 0;
            }

            // Resolver número de origem
            $numeroOrigem = $conta['origem']['numero'] ?? '';
            
            // Se veio vazio da listagem, tenta usar o que já temos salvo localmente
            if (empty($numeroOrigem) && $existsInDb && !empty($existingData[$id]['num_pedido'])) {
                $numeroOrigem = $existingData[$id]['num_pedido'];
            }
            
            if (empty($numeroOrigem)) {
                $numeroOrigem = !empty($conta['numeroDocumento']) ? $conta['numeroDocumento'] : '';
                if (empty($numeroOrigem) && !empty($conta['historico'])) {
                    $hist = $conta['historico'];
                    if (preg_match('/(?:nº|numero|número|pedido|venda)[^\d]*(\d+)/i', $hist, $matches)) {
                        $numeroOrigem = $matches[1];
                    } elseif (preg_match('/(\d+)/', $hist, $matches)) {
                        $numeroOrigem = $matches[1];
                    }
                }
            }

            // Buscar detalhe da API quando necessário:
            $needsDetalhe = false;
            if ($blingService !== null && $id) {
                if ($situacaoBling === 3) {
                    $needsDetalhe = true;
                } elseif (empty($numeroOrigem) && $numeroOrigem !== '—') {
                    $needsDetalhe = true;
                }
            }

            if ($needsDetalhe) {
                $detalhe = $blingService->getContaReceber($id);
                if ($detalhe) {
                    if (empty($numeroOrigem) && !empty($detalhe['historico'])) {
                        $hist = $detalhe['historico'];
                        if (preg_match('/(?:nº|numero|número|pedido|venda)[^\d]*(\d+)/i', $hist, $matches)) {
                            $numeroOrigem = $matches[1];
                        } elseif (preg_match('/(\d+)/', $hist, $matches)) {
                            $numeroOrigem = $matches[1];
                        }
                    }

                    if ($situacaoBling === 3) {
                        $valorTotal = $detalhe['valor'] ?? 0;
                        $saldo = $detalhe['saldo'] ?? 0;
                        $valorPagoBling = max(0, $valorTotal - $saldo);
                    }
                }
            }
            
            if (empty($numeroOrigem)) {
                $numeroOrigem = '—';
            }

            $idFormaPagamento = $conta['formaPagamento']['id'] ?? null;
            $exibir = ($situacaoBling === 4 || $situacaoBling === 5) ? 0 : 1;

            $idVendedor = (!empty($conta['vendedor']['id']) && $conta['vendedor']['id'] != 0) ? $conta['vendedor']['id'] : null;
            $idRepresentanteContato = $idVendedor ? ($mapaRepresentantes[$idVendedor] ?? null) : null;
            
            if ($idRepresentanteContato === null && $existsInDb && !empty($existingData[$id]['id_representante'])) {
                $idRepresentanteContato = $existingData[$id]['id_representante'];
            }

            $registrosParaInserir[] = [
                $id,
                $numeroOrigem,
                $conta['valor'] ?? 0,
                $conta['vencimento'] ?? null,
                $valorPagoBling,
                $situacaoBling,
                $idRepresentanteContato,
                $idContato,
                $idFormaPagamento,
                $exibir
            ];
        }

        if (!empty($registrosParaInserir)) {
            DB::connection()->getPdo()->exec("SET FOREIGN_KEY_CHECKS = 0;");
            DB::connection()->getPdo()->beginTransaction();

            try {
                $chunkSize = 300;
                $chunks = array_chunk($registrosParaInserir, $chunkSize);
                
                foreach ($chunks as $chunk) {
                    $placeholders = [];
                    $flatParams = [];
                    
                    foreach ($chunk as $row) {
                        $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                        $flatParams = array_merge($flatParams, $row);
                    }
                    
                    $sql = "INSERT INTO PEDIDO (
                                ID_PEDIDO, NUM_PEDIDO, TOTAL_PEDIDO, DATA_VENCIMENTO,
                                VALOR_PAGO_BLING, SITUACAO_PEDIDO, ID_REPRESENTANTE, ID_CLIENTE, ID_FORMA_PAGAMENTO, EXIBIR
                            ) VALUES " . implode(',', $placeholders) . "
                            ON DUPLICATE KEY UPDATE
                                NUM_PEDIDO         = VALUES(NUM_PEDIDO),
                                TOTAL_PEDIDO       = VALUES(TOTAL_PEDIDO),
                                DATA_VENCIMENTO    = VALUES(DATA_VENCIMENTO),
                                ID_REPRESENTANTE   = VALUES(ID_REPRESENTANTE),
                                ID_FORMA_PAGAMENTO = VALUES(ID_FORMA_PAGAMENTO),
                                VALOR_PAGO_BLING   = VALUES(VALOR_PAGO_BLING),
                                SITUACAO_PEDIDO    = VALUES(SITUACAO_PEDIDO),
                                EXIBIR             = VALUES(EXIBIR)";
                                
                    $stmt = DB::connection()->getPdo()->prepare($sql);
                    $stmt->execute($flatParams);
                }
                
                DB::connection()->getPdo()->commit();
                DB::connection()->getPdo()->exec("SET FOREIGN_KEY_CHECKS = 1;");
                
                $inseridos += count($registrosParaInserir); 
            } catch (\PDOException $e) {
                DB::connection()->getPdo()->rollBack();
                DB::connection()->getPdo()->exec("SET FOREIGN_KEY_CHECKS = 1;");
                Log::error('Falha no bulk insert de pedidos (importarPedidos).', ['exception' => $e->getMessage()]);
                $ignorados += count($registrosParaInserir);
            }
        }

        if ($mode === 'full_sync' && $blingService !== null) {
            $stmtLocal = DB::connection()->getPdo()->prepare(
                "SELECT ID_PEDIDO FROM PEDIDO 
                 WHERE EXIBIR = 1 
                   AND SITUACAO_PEDIDO IN (1, 3) 
                   AND DATA_VENCIMENTO >= '2025-01-01'"
            );
            $stmtLocal->execute();
            $localPendingIds = $stmtLocal->fetchAll(\PDO::FETCH_COLUMN);

            $missingIds = array_diff($localPendingIds, $allIds);

            foreach ($missingIds as $missingId) {
                $detalhe = $blingService->getContaReceber($missingId);
                if ($detalhe) {
                    $sit = (int)($detalhe['situacao'] ?? 1);
                    if ($sit === 2) {
                        $stmtUpdate = DB::connection()->getPdo()->prepare("UPDATE PEDIDO SET VALOR_PAGO_BLING = TOTAL_PEDIDO, SITUACAO_PEDIDO = 2 WHERE ID_PEDIDO = :id");
                        $stmtUpdate->execute(['id' => $missingId]);
                        $atualizados++;
                    } else if ($sit === 4 || $sit === 5) {
                        $stmtUpdate = DB::connection()->getPdo()->prepare("UPDATE PEDIDO SET EXIBIR = 0, SITUACAO_PEDIDO = :sit WHERE ID_PEDIDO = :id");
                        $stmtUpdate->execute(['id' => $missingId, 'sit' => $sit]);
                        $atualizados++;
                    }
                } else {
                    $stmtUpdate = DB::connection()->getPdo()->prepare("UPDATE PEDIDO SET EXIBIR = 0, SITUACAO_PEDIDO = 5 WHERE ID_PEDIDO = :id");
                    $stmtUpdate->execute(['id' => $missingId]);
                    $atualizados++;
                }
                usleep(200000); // 200ms
            }
        }

        return [
            'inseridos'   => $inseridos,
            'atualizados' => $atualizados,
            'ignorados'   => $ignorados
        ];
    }
}
