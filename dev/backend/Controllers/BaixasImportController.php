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
        if (!file_exists($dir . 'SimpleXLSXGen.php')) die('Biblioteca SimpleXLSXGen ausente.');
        require_once $dir . 'SimpleXLSXGen.php';

        $linhas = [
            ['NUM_PEDIDO', 'NOME_CLIENTE',     'TOTAL_PEDIDO', 'DATA_VENCIMENTO', 'VALOR_PAGO', 'DATA_PAGO'],
            ['1234',       'João da Silva',     '500.00',       '2026-07-15',      '500.00',     '2026-07-10'],
            ['NF-5678',    'Empresa ABC Ltda',  '320.50',       '2026-07-20',      '320.50',     '2026-07-18'],
        ];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($linhas);
        $xlsx->downloadAs('template_importacao_baixas.xlsx');
    }

    /**
     * Lê o XLSX e exibe a tela de mapeamento de colunas.
     */
    public function processarImportacao() {
        $dir = __DIR__ . '/../../Libraries/';
        if (!file_exists($dir . 'SimpleXLSX.php')) die('Biblioteca SimpleXLSX ausente.');
        require_once $dir . 'SimpleXLSX.php';

        if (!request()->isMethod('post') || !request()->hasFile('arquivo_xlsx')) {
            session()->flash('flash_msg', "Nenhum arquivo enviado.");
            return redirect(url('/') . '/contas-receber/importar');
        }

        $file = request()->file('arquivo_xlsx')->getRealPath();
        if ($xlsx = \Shuchkin\SimpleXLSX::parse($file)) {
            $allRows = $xlsx->rows();
            session()->put('baixas_raw_data', $allRows);

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
        $idxDataPago  = array_search('DATA_PAGO',       $map);

        if ($idxValor === false || $idxNum === false) {
            session()->flash('flash_msg', "Mapeie as colunas NUM_PEDIDO e VALOR_PAGO obrigatoriamente.");
            return redirect(url('/') . '/contas-receber/importar');
        }

        if ($ignorar) array_shift($raw);

        // Extrai linhas válidas
        $linhas = [];
        foreach ($raw as $row) {
            $num   = trim((string)($row[$idxNum]   ?? ''));
            $valor = trim((string)($row[$idxValor] ?? ''));
            if (empty($num) || empty($valor)) continue;

            // Normaliza valor
            $valor = str_replace(['R$', ' ', '.'], '', $valor);
            $valor = str_replace(',', '.', $valor);

            $linhas[] = [
                'num_pedido'     => $num,
                'nome_cliente'   => ($idxNome  !== false) ? trim((string)($row[$idxNome]  ?? '')) : '',
                'total_pedido'   => ($idxTotal !== false) ? (float)str_replace(['.', ','], ['', '.'], str_replace('R$', '', $row[$idxTotal] ?? '0')) : (float)$valor,
                'data_vencimento'=> ($idxVenc  !== false) ? trim((string)($row[$idxVenc]  ?? '')) : null,
                'valor_pago'     => (float)$valor,
                'data_pago'      => ($idxDataPago !== false) ? trim((string)($row[$idxDataPago] ?? '')) : '',
            ];
        }

        // Busca pedidos existentes em lote
        $nums = array_unique(array_column($linhas, 'num_pedido'));
        $existentes = [];
        if (!empty($nums)) {
            $chunks = array_chunk($nums, 300);
            foreach ($chunks as $chunk) {
                $in = implode(',', array_fill(0, count($chunk), '?'));
                $stmt = DB::connection()->getPdo()->prepare("
                    SELECT p.ID_PEDIDO, p.NUM_PEDIDO, p.TOTAL_PEDIDO, c.NOME_CONTATO, c.ID_CONTATO_BLING
                    FROM PEDIDO p
                    LEFT JOIN CONTATO_EXTERNO c ON c.ID_CONTATO_BLING = p.ID_CLIENTE
                    WHERE p.NUM_PEDIDO IN ($in)
                ");
                $stmt->execute(array_values($chunk));
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                    $existentes[$r['NUM_PEDIDO']] = $r;
                }
            }
        }

        $prontos        = [];
        $criados        = [];
        $naoEncontrados = [];

        foreach ($linhas as $item) {
            $num = $item['num_pedido'];

            // ── Pedido já existe ─────────────────────────────────────────────
            if (isset($existentes[$num])) {
                $p = $existentes[$num];
                $prontos[] = [
                    'id_pedido'    => $p['ID_PEDIDO'],
                    'num_pedido'   => $num,
                    'total_pedido' => $p['TOTAL_PEDIDO'],
                    'cliente'      => $p['NOME_CONTATO'],
                    'valor_pago'   => $item['valor_pago'],
                    'data_pago'    => $item['data_pago'],
                    'status'       => 'existente',
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

            // Cria o pedido com ORIGEM='excel'
            $stmtInsert = DB::connection()->getPdo()->prepare("
                INSERT INTO PEDIDO (ORIGEM, NUM_PEDIDO, TOTAL_PEDIDO, DATA_VENCIMENTO, SITUACAO_PEDIDO, ID_CLIENTE, EXIBIR)
                VALUES ('excel', :num, :total, :venc, 2, :id_cliente, 1)
            ");
            $stmtInsert->execute([
                'num'        => $num,
                'total'      => $item['total_pedido'],
                'venc'       => $item['data_vencimento'] ?: null,
                'id_cliente' => $idCliente,
            ]);
            $novoId = DB::connection()->getPdo()->lastInsertId();

            $criados[] = [
                'id_pedido'    => $novoId,
                'num_pedido'   => $num,
                'total_pedido' => $item['total_pedido'],
                'cliente'      => $item['nome_cliente'],
                'valor_pago'   => $item['valor_pago'],
                'data_pago'    => $item['data_pago'],
                'status'       => 'criado',
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
        ]);
    }

    /**
     * Efetiva as baixas na tela de preview.
     */
    public function confirmarImportacao() {
        $prontos = session()->get('baixas_prontas', []);

        if (empty($prontos)) {
            session()->flash('flash_msg', "Não há registros para importar.");
            return redirect(url('/') . '/contas-receber');
        }

        $idColaborador = auth()->id() ?? 0;
        $sucessos = 0;
        $erros    = [];

        foreach ($prontos as $item) {
            try {
                $this->pedidoRepository->registrarBaixaManual([
                    [
                        'id'        => $item['id_pedido'],
                        'valor'     => $item['valor_pago'],
                        'data_pago' => !empty($item['data_pago']) ? $item['data_pago'] : null,
                    ]
                ], $idColaborador, true);
                $sucessos++;
            } catch (\Exception $e) {
                $erros[] = "Pedido #{$item['num_pedido']}: " . $e->getMessage();
            }
        }

        session()->put('baixas_log_importacao', ['sucessos' => $sucessos, 'erros' => $erros]);
        session()->forget(['baixas_raw_data', 'baixas_prontas', 'baixas_nao_encontradas']);

        return redirect(url('/') . '/contas-receber/importar/log');
    }

    public function logImportacao() {
        $log = session()->get('baixas_log_importacao', ['sucessos' => 0, 'erros' => []]);
        return view('pages.importar_baixas_log', ['log' => $log]);
    }
}
