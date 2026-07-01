<?php
namespace App\Controllers;

use Illuminate\Support\Facades\DB;
use App\Repositories\PedidoRepository;

class BaixasImportController extends Controller {

    private $pedidoRepository;

    public function __construct() {
        $this->pedidoRepository = new PedidoRepository();
    }

    public function importar() {
        return view('pages.importar_baixas_index');
    }

    /**
     * Template com as colunas esperadas pelo importador.
     */
    public function downloadTemplate() {
        $dir = __DIR__ . '/../../Libraries/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (!file_exists($dir . 'SimpleXLSXGen.php')) {
            $content = @file_get_contents('https://raw.githubusercontent.com/shuchkin/simplexlsxgen/master/src/SimpleXLSXGen.php');
            if ($content) file_put_contents($dir . 'SimpleXLSXGen.php', $content);
        }
        if (!file_exists($dir . 'SimpleXLSXGen.php')) die('Erro ao baixar a biblioteca SimpleXLSXGen.');

        require_once $dir . 'SimpleXLSXGen.php';

        $linhas = [
            ['NUM_PEDIDO', 'NOME_CLIENTE',     'TOTAL_PEDIDO', 'DATA_VENCIMENTO', 'VALOR_PAGO', 'COLABORADOR'],
            ['1234',       'João da Silva',     '500.00',       '2026-07-15',      '500.00',     'gabriel'],
            ['NF-5678',    'Empresa ABC Ltda',  '320.50',       '2026-07-20',      '320.50',     'admin'],
        ];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($linhas);
        $xlsx->downloadAs('template_importacao_baixas.xlsx');
    }

    /**
     * Lê o XLSX e exibe a tela de mapeamento de colunas.
     */
    public function processarImportacao() {
        $dir = __DIR__ . '/../../Libraries/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (!file_exists($dir . 'SimpleXLSX.php')) {
            $content = @file_get_contents('https://raw.githubusercontent.com/shuchkin/simplexlsx/master/src/SimpleXLSX.php');
            if ($content) file_put_contents($dir . 'SimpleXLSX.php', $content);
        }
        if (!file_exists($dir . 'SimpleXLSX.php')) die('Erro ao baixar a biblioteca SimpleXLSX.');

        require_once $dir . 'SimpleXLSX.php';

        if (!request()->isMethod('post') || !request()->hasFile('arquivo_xlsx')) {
            session()->flash('flash_msg', "Nenhum arquivo enviado.");
            return redirect(url('/') . '/contas-receber/importar');
        }

        $file = request()->file('arquivo_xlsx')->getRealPath();
        $nomeOriginal = request()->file('arquivo_xlsx')->getClientOriginalName();
        if ($xlsx = \Shuchkin\SimpleXLSX::parse($file)) {
            $allRows = $xlsx->rows();
            session()->put('baixas_raw_data', $allRows);
            session()->put('baixas_nome_arquivo', $nomeOriginal);

            $headers = $allRows[0] ?? [];
            $amostra = array_slice($allRows, 0, 6);
            $maxCols = max(array_map('count', $amostra) ?: [0]);
            $headers = array_pad($headers, $maxCols, '');

            return view('pages.importar_baixas_mapeamento', compact('headers', 'amostra'));
        }

        session()->flash('flash_msg', "Erro ao ler arquivo: " . \Shuchkin\SimpleXLSX::parseError());
        return redirect(url('/') . '/contas-receber/importar');
    }

    /**
     * Processa o mapeamento:
     * - Se o pedido existe (por NUM_PEDIDO) → usa o ID interno
     * - Se não existe → cria um novo PEDIDO com ORIGEM='excel', buscando o cliente por nome
     */
    public function processarMapeamento() {
        $map      = request()->input('map', []);
        $ignorar  = request()->input('ignorar_primeira_linha', 0);
        $raw      = session()->get('baixas_raw_data', []);

        if (empty($raw)) {
            session()->flash('flash_msg', "Sessão expirada. Envie o arquivo novamente.");
            return redirect(url('/') . '/contas-receber/importar');
        }

        $idxNum       = array_search('NUM_PEDIDO',      $map);
        $idxNome      = array_search('NOME_CLIENTE',    $map);
        $idxTotal     = array_search('TOTAL_PEDIDO',    $map);
        $idxVenc      = array_search('DATA_VENCIMENTO', $map);
        $idxValor     = array_search('VALOR_PAGO',      $map);
        $idxColab     = array_search('COLABORADOR',     $map);

        if ($idxValor === false || $idxColab === false) {
            session()->flash('flash_msg', "Mapeie as colunas VALOR_PAGO e COLABORADOR obrigatoriamente.");
            return redirect(url('/') . '/contas-receber/importar');
        }

        if ($idxNum === false && $idxNome === false) {
            session()->flash('flash_msg', "Mapeie a coluna NUM_PEDIDO ou NOME_CLIENTE para identificar os pedidos.");
            return redirect(url('/') . '/contas-receber/importar');
        }

        if ($ignorar) array_shift($raw);

        // Função auxiliar para converter datas do Excel d/m/Y para Y-m-d
        $parseDate = function($dateStr) {
            if (empty($dateStr)) return null;
            $dateStr = trim($dateStr);
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateStr)) return substr($dateStr, 0, 10);
            $d = \DateTime::createFromFormat('d/m/Y', $dateStr);
            if ($d) return $d->format('Y-m-d');
            $d = \DateTime::createFromFormat('d/m/y', $dateStr);
            if ($d) return $d->format('Y-m-d');
            return null;
        };

        // Função auxiliar para normalizar valores monetários vindos do Excel
        // Suporta: "R$ 1.447,19", "1.447,19", "1447.19", "1447,19", 1447.19 (número)
        $parseValor = function($raw) {
            if (is_numeric($raw)) return (float)$raw; // célula numérica do Excel, já é decimal
            $s = trim(str_replace(['R$', ' '], '', (string)$raw));
            if ($s === '' || $s === '-') return 0.0;
            // Se tem vírgula E ponto: temos separador de milhar + decimal
            // Ex: "1.447,19" → 1447.19  |  "1,447.19" → 1447.19
            if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
                if (strrpos($s, ',') > strrpos($s, '.')) {
                    // Ponto é milhar, vírgula é decimal (BR): "1.447,19"
                    $s = str_replace('.', '', $s);
                    $s = str_replace(',', '.', $s);
                } else {
                    // Vírgula é milhar, ponto é decimal (US): "1,447.19"
                    $s = str_replace(',', '', $s);
                }
                return (float)$s;
            }
            // Só vírgula: assume decimal BR "1447,19"
            if (strpos($s, ',') !== false) {
                return (float)str_replace(',', '.', $s);
            }
            // Só ponto: já é decimal "1447.19"
            return (float)$s;
        };

        // Extrai linhas válidas
        $linhas = [];
        foreach ($raw as $row) {
            $num   = ($idxNum !== false) ? trim((string)($row[$idxNum] ?? '')) : '';
            $valor = ($row[$idxValor] ?? '');
            $nome  = ($idxNome !== false) ? trim((string)($row[$idxNome] ?? '')) : '';

            if ($valor === '' || $valor === null) continue;
            if (empty($num) && empty($nome)) continue;

            $valorFloat = $parseValor($valor);
            $totalFloat = ($idxTotal !== false) ? $parseValor($row[$idxTotal] ?? 0) : $valorFloat;

            $linhas[] = [
                'num_pedido'      => $num,
                'nome_cliente'    => ($idxNome  !== false) ? trim((string)($row[$idxNome]  ?? '')) : '',
                'total_pedido'    => $totalFloat,
                'data_vencimento' => ($idxVenc  !== false) ? $parseDate($row[$idxVenc] ?? '') : null,
                'valor_pago'      => $valorFloat,
                'nome_colaborador'=> ($idxColab !== false) ? trim((string)($row[$idxColab] ?? '')) : '',
            ];
        }

        // Busca pedidos existentes em lote
        $nums = array_filter(array_unique(array_column($linhas, 'num_pedido')));
        $existentes = [];
        if (!empty($nums)) {
            $chunks = array_chunk($nums, 300);
            foreach ($chunks as $chunk) {
                $in = implode(',', array_fill(0, count($chunk), '?'));
                $stmt = DB::connection()->getPdo()->prepare("
                    SELECT p.ID_PEDIDO, p.NUM_PEDIDO, p.TOTAL_PEDIDO, c.NOME_CONTATO, c.ID_CONTATO_BLING, p.DATA_VENCIMENTO
                    FROM PEDIDO p
                    LEFT JOIN CONTATO_EXTERNO c ON c.ID_CONTATO_BLING = p.ID_CLIENTE
                    WHERE p.NUM_PEDIDO IN ($in)
                ");
                $stmt->execute(array_values($chunk));
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                    $existentes[$r['NUM_PEDIDO']][] = $r;
                }
            }
        }

        $prontos        = [];
        $criados        = [];
        $naoEncontrados = [];

        $colaboradoresRaw = DB::table('COLABORADOR')->get(['ID_COLABORADOR', 'NOME_COLABORADOR']);
        $colabMap = [];
        foreach($colaboradoresRaw as $c) {
            $colabMap[mb_strtolower(trim($c->NOME_COLABORADOR))] = $c->ID_COLABORADOR;
        }

        foreach ($linhas as $item) {
            $num = $item['num_pedido'];
            $nomeColabLower = mb_strtolower(trim($item['nome_colaborador']));
            $idColaboradorPlanilha = $colabMap[$nomeColabLower] ?? null;

            // ── Pedido já existe ─────────────────────────────────────────────
            $p = null;
            if (!empty($num) && isset($existentes[$num])) {
                if (!empty($item['data_vencimento'])) {
                    // Se enviou vencimento, tenta casar com o mesmo vencimento
                    foreach ($existentes[$num] as $ext) {
                        if ($ext['DATA_VENCIMENTO'] === $item['data_vencimento']) {
                            $p = $ext;
                            break;
                        }
                    }
                } else {
                    // Se não enviou vencimento, pega o primeiro que aparecer
                    $p = $existentes[$num][0];
                }
            } elseif (empty($num) && !empty($item['nome_cliente']) && !empty($item['data_vencimento'])) {
                // Tenta achar por cliente + vencimento
                $stmtCliente = DB::connection()->getPdo()->prepare("
                    SELECT ID_CONTATO_BLING FROM CONTATO_EXTERNO
                    WHERE NOME_CONTATO LIKE :nome LIMIT 1
                ");
                $stmtCliente->execute(['nome' => '%' . $item['nome_cliente'] . '%']);
                $rowCliente = $stmtCliente->fetch(\PDO::FETCH_ASSOC);
                $idClienteSearch = $rowCliente['ID_CONTATO_BLING'] ?? null;

                if ($idClienteSearch) {
                    $stmtPedido = DB::connection()->getPdo()->prepare("
                        SELECT p.ID_PEDIDO, p.NUM_PEDIDO, p.TOTAL_PEDIDO, c.NOME_CONTATO, c.ID_CONTATO_BLING, p.DATA_VENCIMENTO
                        FROM PEDIDO p
                        LEFT JOIN CONTATO_EXTERNO c ON c.ID_CONTATO_BLING = p.ID_CLIENTE
                        WHERE p.ID_CLIENTE = :id_cliente AND p.DATA_VENCIMENTO = :venc AND p.SITUACAO_PEDIDO = 2
                        AND (p.NUM_PEDIDO IS NULL OR p.NUM_PEDIDO = '')
                        LIMIT 1
                    ");
                    $stmtPedido->execute(['id_cliente' => $idClienteSearch, 'venc' => $item['data_vencimento']]);
                    $p = $stmtPedido->fetch(\PDO::FETCH_ASSOC);
                    if ($p) {
                        $num = $p['NUM_PEDIDO']; // atualiza o num_pedido com o real
                    }
                }
            }

            if ($p) {
                $prontos[] = [
                    'id_pedido'      => $p['ID_PEDIDO'],
                    'num_pedido'     => $num,
                    'total_pedido'   => $p['TOTAL_PEDIDO'],
                    'cliente'        => $p['NOME_CONTATO'],
                    'valor_pago'     => $item['valor_pago'],
                    'id_colaborador' => $idColaboradorPlanilha,
                    'status'         => 'existente',
                ];
                continue;
            }

            // ── Pedido NÃO existe — tenta criar ─────────────────────────────
            $idCliente = null;
            if (!empty($item['nome_cliente'])) {
                $stmtCliente = DB::connection()->getPdo()->prepare("
                    SELECT ID_CONTATO_BLING FROM CONTATO_EXTERNO
                    WHERE NOME_CONTATO LIKE :nome LIMIT 1
                ");
                $stmtCliente->execute(['nome' => '%' . $item['nome_cliente'] . '%']);
                $rowCliente = $stmtCliente->fetch(\PDO::FETCH_ASSOC);
                $idCliente = $rowCliente['ID_CONTATO_BLING'] ?? null;
            }

            if (!$idCliente) {
                // Cliente não encontrado — não conseguimos criar o pedido
                $naoEncontrados[] = array_merge($item, ['motivo' => 'Cliente não localizado: "' . $item['nome_cliente'] . '"']);
                continue;
            }

            // Busca uma forma de pagamento válida como fallback para evitar erro de Foreign Key
            static $fallbackFormaPagamento = null;
            if ($fallbackFormaPagamento === null) {
                $fallbackFormaPagamento = \Illuminate\Support\Facades\DB::table('FORMA_PAGAMENTO')->value('ID_FORMA_PAGAMENTO');
                if (!$fallbackFormaPagamento) {
                    $fallbackFormaPagamento = 1;
                    \Illuminate\Support\Facades\DB::statement("
                        INSERT IGNORE INTO FORMA_PAGAMENTO (ID_FORMA_PAGAMENTO, COBRANCA_PADRAO) 
                        VALUES (1, 1)
                    ");
                }
            }

            // Cria o pedido com ORIGEM='excel' e EXIBIR=0 (só para baixas)
            $numFinal = $num ?: '';
            $stmtInsert = DB::connection()->getPdo()->prepare("
                INSERT INTO PEDIDO (ORIGEM, NUM_PEDIDO, TOTAL_PEDIDO, DATA_VENCIMENTO, SITUACAO_PEDIDO, ID_CLIENTE, EXIBIR, ID_FORMA_PAGAMENTO)
                VALUES ('excel', :num, :total, :venc, 2, :id_cliente, 0, :forma)
            ");
            $stmtInsert->execute([
                'num'        => $numFinal,
                'total'      => $item['total_pedido'],
                'venc'       => $item['data_vencimento'] ?: null,
                'id_cliente' => $idCliente,
                'forma'      => $fallbackFormaPagamento
            ]);
            $novoId = DB::connection()->getPdo()->lastInsertId();

            $criados[] = [
                'id_pedido'      => $novoId,
                'num_pedido'     => $numFinal,
                'total_pedido'   => $item['total_pedido'],
                'cliente'        => $item['nome_cliente'],
                'valor_pago'     => $item['valor_pago'],
                'id_colaborador' => $idColaboradorPlanilha,
                'status'         => 'criado',
            ];
        }

        // Prontos = existentes + recém criados
        $todosProntos = array_merge($prontos, $criados);

        session()->put('baixas_prontas',        $todosProntos);
        session()->put('baixas_nao_encontradas', $naoEncontrados);

        return view('pages.importar_baixas_preview', [
            'prontos'        => $todosProntos,
            'criados'        => $criados,
            'naoEncontrados' => $naoEncontrados,
            'colaboradoresDb'=> $colaboradoresRaw,
        ]);
    }

    /**
     * Efetiva as baixas na tela de preview e registra o lote de importação.
     */
    public function confirmarImportacao() {
        $prontos = session()->get('baixas_prontas', []);
        $colabsEditados   = request()->input('colaboradores', []);
        $isChequeEditados = request()->input('is_cheque', []);
        $nomeArquivo      = session()->get('baixas_nome_arquivo', 'planilha');

        if (empty($prontos)) {
            session()->flash('flash_msg', "Não há registros para importar.");
            return redirect(url('/') . '/contas-receber');
        }

        $sucessos = 0;
        $erros    = [];
        $idFormaCheque = config('hydra.bling.formas_pagamento.cheque', 7179734);

        // Cria o registro do lote (pode falhar silenciosamente se a tabela ainda não existir)
        $idLote = null;
        try {
            $idLote = DB::table('LOTE_IMPORTACAO')->insertGetId([
                'NOME_ARQUIVO'  => $nomeArquivo,
                'QTD_REGISTROS' => count($prontos),
                'ID_USUARIO'    => auth()->id(),
                'DATA_CRIACAO'  => now(),
            ]);
        } catch (\Exception $e) {
            // Tabela ainda não existe (antes do deploy da migration) — continua sem lote
        }

        foreach ($prontos as $idx => $item) {
            try {
                $idColab = $colabsEditados[$idx] ?? $item['id_colaborador'];
                if (!$idColab) {
                    throw new \Exception("Colaborador não selecionado.");
                }

                $this->pedidoRepository->registrarBaixaManual([
                    [
                        'id'        => $item['id_pedido'],
                        'valor'     => $item['valor_pago'],
                        'data_pago' => null,
                    ]
                ], $idColab, true);

                // Vincula o REGISTRO_PAGAMENTO ao lote
                if ($idLote) {
                    try {
                        $rp = DB::selectOne("
                            SELECT rp.ID_REGISTRO 
                            FROM REGISTRO_PAGAMENTO rp
                            JOIN DETALHE_PAGAMENTO dp ON dp.ID_REGISTRO = rp.ID_REGISTRO
                            WHERE dp.ID_PEDIDO = ? AND rp.ID_LOTE IS NULL
                            ORDER BY rp.ID_REGISTRO DESC LIMIT 1
                        ", [$item['id_pedido']]);
                        
                        if ($rp) {
                            DB::table('REGISTRO_PAGAMENTO')
                                ->where('ID_REGISTRO', $rp->ID_REGISTRO)
                                ->update(['ID_LOTE' => $idLote]);
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Erro ao vincular lote: " . $e->getMessage());
                    }
                }

                if (!empty($isChequeEditados[$idx])) {
                    \Illuminate\Support\Facades\DB::table('PEDIDO')
                        ->where('ID_PEDIDO', $item['id_pedido'])
                        ->update([
                            'ID_FORMA_PAGAMENTO' => $idFormaCheque,
                            'STATUS_CHEQUE' => 'pendente'
                        ]);
                }

                $sucessos++;
            } catch (\Exception $e) {
                $erros[] = "Pedido #{$item['num_pedido']}: " . $e->getMessage();
            }
        }

        session()->put('baixas_log_importacao', ['sucessos' => $sucessos, 'erros' => $erros]);
        session()->forget(['baixas_raw_data', 'baixas_prontas', 'baixas_nao_encontradas', 'baixas_nome_arquivo']);

        return redirect(url('/') . '/contas-receber/importar/log');
    }

    public function logImportacao() {
        $log = session()->get('baixas_log_importacao', ['sucessos' => 0, 'erros' => []]);
        return view('pages.importar_baixas_log', ['log' => $log]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // HISTÓRICO DE LOTES
    // ═══════════════════════════════════════════════════════════════════

    public function historico() {
        try {
            $lotes = DB::select("
                SELECT l.ID_LOTE, l.DATA_CRIACAO, l.NOME_ARQUIVO, l.QTD_REGISTROS,
                       u.name AS NOME_USUARIO,
                       COUNT(DISTINCT rp.ID_REGISTRO) AS REGISTROS_EXISTENTES
                FROM LOTE_IMPORTACAO l
                LEFT JOIN users u ON u.id = l.ID_USUARIO
                LEFT JOIN REGISTRO_PAGAMENTO rp ON rp.ID_LOTE = l.ID_LOTE
                GROUP BY l.ID_LOTE
                ORDER BY l.DATA_CRIACAO DESC
            ");
        } catch (\Exception $e) {
            $lotes = [];
        }

        return view('pages.importar_baixas_historico', compact('lotes'));
    }

    public function editarLote($id) {
        try {
            $lote = DB::table('LOTE_IMPORTACAO')->where('ID_LOTE', $id)->first();
            if (!$lote) abort(404);

            $registros = DB::select("
                SELECT
                    rp.ID_REGISTRO,
                    rp.VALOR_REGISTRO,
                    rp.DATA_REGISTRO,
                    rp.ID_COLABORADOR,
                    col.NOME_COLABORADOR,
                    dp.ID_PEDIDO,
                    dp.VALOR_PAGO_PEDIDO,
                    p.NUM_PEDIDO,
                    p.ORIGEM,
                    c.NOME_CONTATO AS NOME_CLIENTE,
                    p.ID_FORMA_PAGAMENTO,
                    p.STATUS_CHEQUE
                FROM REGISTRO_PAGAMENTO rp
                JOIN DETALHE_PAGAMENTO dp ON dp.ID_REGISTRO = rp.ID_REGISTRO
                JOIN PEDIDO p ON p.ID_PEDIDO = dp.ID_PEDIDO
                JOIN COLABORADOR col ON col.ID_COLABORADOR = rp.ID_COLABORADOR
                LEFT JOIN CONTATO_EXTERNO c ON c.ID_CONTATO_BLING = p.ID_CLIENTE
                WHERE rp.ID_LOTE = ?
                ORDER BY rp.ID_REGISTRO
            ", [$id]);

            $colaboradores = DB::table('COLABORADOR')->get(['ID_COLABORADOR', 'NOME_COLABORADOR']);
            $idFormaCheque = config('hydra.bling.formas_pagamento.cheque', 7179734);

            return view('pages.importar_baixas_editar_lote', compact('lote', 'registros', 'colaboradores', 'idFormaCheque'));
        } catch (\Exception $e) {
            session()->flash('flash_msg', 'Erro ao carregar lote: ' . $e->getMessage());
            return redirect(url('/') . '/contas-receber/importar/historico');
        }
    }

    public function salvarLote($id) {
        $colabs   = request()->input('colaboradores', []);
        $cheques  = request()->input('is_cheque', []);
        $idFormaCheque = config('hydra.bling.formas_pagamento.cheque', 7179734);

        try {
            foreach ($colabs as $idRegistro => $idColab) {
                if (!$idColab) continue;
                DB::table('REGISTRO_PAGAMENTO')
                    ->where('ID_REGISTRO', $idRegistro)
                    ->where('ID_LOTE', $id)
                    ->update(['ID_COLABORADOR' => $idColab]);

                // Recupera ID_PEDIDO para atualizar cheque
                $detalhes = DB::table('DETALHE_PAGAMENTO')->where('ID_REGISTRO', $idRegistro)->get();
                foreach ($detalhes as $det) {
                    if (!empty($cheques[$idRegistro])) {
                        DB::table('PEDIDO')->where('ID_PEDIDO', $det->ID_PEDIDO)->update([
                            'ID_FORMA_PAGAMENTO' => $idFormaCheque,
                            'STATUS_CHEQUE'      => 'pendente',
                        ]);
                    } else {
                        // Remove marcação de cheque caso tenha sido desmarcado
                        DB::table('PEDIDO')
                            ->where('ID_PEDIDO', $det->ID_PEDIDO)
                            ->where('ID_FORMA_PAGAMENTO', $idFormaCheque)
                            ->update(['STATUS_CHEQUE' => null]);
                    }
                }
            }

            session()->flash('flash_msg', "Lote #{$id} atualizado com sucesso.");
        } catch (\Exception $e) {
            session()->flash('flash_msg', "Erro ao salvar: " . $e->getMessage());
        }

        return redirect(url('/') . '/contas-receber/importar/historico');
    }

    public function excluirLote($id) {
        try {
            DB::connection()->getPdo()->beginTransaction();

            // Busca todos os REGISTRO_PAGAMENTO do lote
            $registros = DB::table('REGISTRO_PAGAMENTO')->where('ID_LOTE', $id)->get();

            foreach ($registros as $reg) {
                // Busca pedidos vinculados a esse registro
                $detalhes = DB::table('DETALHE_PAGAMENTO')->where('ID_REGISTRO', $reg->ID_REGISTRO)->get();

                // Apaga os detalhes de pagamento
                DB::table('DETALHE_PAGAMENTO')->where('ID_REGISTRO', $reg->ID_REGISTRO)->delete();

                foreach ($detalhes as $det) {
                    // Se o pedido foi criado pela importação (ORIGEM='excel'), apaga permanentemente
                    $pedido = DB::table('PEDIDO')->where('ID_PEDIDO', $det->ID_PEDIDO)->first();
                    if ($pedido && $pedido->ORIGEM === 'excel') {
                        // Apaga outros detalhes do mesmo pedido (caso haja)
                        DB::table('DETALHE_PAGAMENTO')->where('ID_PEDIDO', $det->ID_PEDIDO)->delete();
                        DB::table('PEDIDO')->where('ID_PEDIDO', $det->ID_PEDIDO)->delete();
                    }
                    // Se ORIGEM='bling' ou outro, mantém o pedido — só apagou a baixa acima
                }

                // Apaga o registro de pagamento
                DB::table('REGISTRO_PAGAMENTO')->where('ID_REGISTRO', $reg->ID_REGISTRO)->delete();
            }

            // Apaga o lote
            DB::table('LOTE_IMPORTACAO')->where('ID_LOTE', $id)->delete();

            DB::connection()->getPdo()->commit();

            session()->flash('flash_msg', "Lote #{$id} excluído com sucesso. As baixas foram revertidas.");
        } catch (\Exception $e) {
            DB::connection()->getPdo()->rollBack();
            session()->flash('flash_msg', "Erro ao excluir lote: " . $e->getMessage());
        }

        return redirect(url('/') . '/contas-receber/importar/historico');
    }
}
