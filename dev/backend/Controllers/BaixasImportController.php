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

    public function downloadTemplate() {
        require_once __DIR__ . '/../../Libraries/SimpleXLSXGen.php';
        
        $linhas = [
            ['NUM_PEDIDO', 'VALOR_PAGO', 'DATA_PAGAMENTO', 'OBSERVACAO'],
            ['12345', '150.00', '2026-07-01', 'Baixa manual via Excel'],
            ['67890', '320.50', '2026-07-02', '']
        ];
        
        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($linhas);
        $xlsx->downloadAs('template_importacao_baixas.xlsx');
    }

    public function processarImportacao() {
        $dir = __DIR__ . '/../../Libraries/';
        if (!file_exists($dir . 'SimpleXLSX.php')) {
            die('Biblioteca SimpleXLSX ausente.');
        }
        require_once $dir . 'SimpleXLSX.php';

        if (!request()->isMethod('post') || !request()->hasFile('arquivo_xlsx')) {
            session()->flash('flash_msg', "Nenhum arquivo enviado.");
            return redirect(url('/') . '/contas-receber/importar');
        }

        $file = request()->file('arquivo_xlsx')->getRealPath();
        if ($xlsx = \Shuchkin\SimpleXLSX::parse($file)) {
            $allRows = $xlsx->rows();
            session()->put('baixas_raw_data', $allRows);

            $primeiraLinha = $allRows[0] ?? [];
            $amostra = array_slice($allRows, 0, 6);
            $maxCols = 0;
            foreach ($amostra as $row) {
                if (is_array($row) && count($row) > $maxCols) {
                    $maxCols = count($row);
                }
            }
            $primeiraLinha = array_pad($primeiraLinha, $maxCols, '');

            return view('pages.importar_baixas_mapeamento', [
                'headers' => $primeiraLinha,
                'amostra' => $amostra
            ]);
        } else {
            session()->flash('flash_msg', "Erro ao ler arquivo: " . \Shuchkin\SimpleXLSX::parseError());
            return redirect(url('/') . '/contas-receber/importar');
        }
    }

    public function processarMapeamento() {
        $map = request()->input('map', []);
        $ignorarPrimeiraLinha = request()->input('ignorar_primeira_linha', 0);
        $raw = session()->get('baixas_raw_data', []);

        if (empty($raw)) {
            session()->flash('flash_msg', "Sessão expirada. Envie o arquivo novamente.");
            return redirect(url('/') . '/contas-receber/importar');
        }

        $idxNum = array_search('NUM_PEDIDO', $map);
        $idxValor = array_search('VALOR_PAGO', $map);

        if ($idxNum === false || $idxValor === false) {
            session()->flash('flash_msg', "Você precisa mapear as colunas 'Número do Pedido' e 'Valor Pago' obrigatoriamente.");
            return redirect(url('/') . '/contas-receber/importar');
        }

        if ($ignorarPrimeiraLinha) {
            array_shift($raw);
        }

        $estruturado = [];
        $numsToQuery = [];

        foreach ($raw as $i => $row) {
            $numPedido = $row[$idxNum] ?? '';
            $valor = $row[$idxValor] ?? '';
            if (empty(trim((string)$numPedido)) || empty(trim((string)$valor))) {
                continue;
            }

            // Normaliza valor
            $valor = str_replace(['R$', ' ', '.'], ['', '', ''], $valor);
            $valor = str_replace(',', '.', $valor);

            $estruturado[] = [
                'indice' => $i,
                'num_pedido' => trim((string)$numPedido),
                'valor_pago' => (float)$valor
            ];
            $numsToQuery[] = trim((string)$numPedido);
        }

        // Buscar no banco os pedidos correspondentes
        $numsToQuery = array_unique($numsToQuery);
        $mapaPedidos = [];
        if (!empty($numsToQuery)) {
            $chunks = array_chunk($numsToQuery, 300);
            foreach ($chunks as $chunk) {
                $inQuery = implode(',', array_fill(0, count($chunk), '?'));
                $stmt2 = DB::connection()->getPdo()->prepare("
                    SELECT p.ID_PEDIDO, p.NUM_PEDIDO, p.TOTAL_PEDIDO, c.NOME_CONTATO
                    FROM PEDIDO p 
                    LEFT JOIN CONTATO_EXTERNO c ON c.ID_CONTATO_BLING = p.ID_CLIENTE
                    WHERE p.NUM_PEDIDO IN ($inQuery)
                ");
                $stmt2->execute(array_values($chunk));
                while ($r = $stmt2->fetch(\PDO::FETCH_ASSOC)) {
                    $mapaPedidos[$r['NUM_PEDIDO']] = $r;
                }
            }
        }

        $prontos = [];
        $naoEncontrados = [];

        foreach ($estruturado as $item) {
            $num = $item['num_pedido'];
            if (isset($mapaPedidos[$num])) {
                $item['id_pedido'] = $mapaPedidos[$num]['ID_PEDIDO'];
                $item['total_pedido'] = $mapaPedidos[$num]['TOTAL_PEDIDO'];
                $item['cliente'] = $mapaPedidos[$num]['NOME_CONTATO'];
                $prontos[] = $item;
            } else {
                $naoEncontrados[] = $item;
            }
        }

        session()->put('baixas_prontas', $prontos);
        session()->put('baixas_nao_encontradas', $naoEncontrados);

        return view('pages.importar_baixas_preview', [
            'prontos' => $prontos,
            'naoEncontrados' => $naoEncontrados
        ]);
    }

    public function confirmarImportacao() {
        $prontos = session()->get('baixas_prontas', []);
        
        if (empty($prontos)) {
            session()->flash('flash_msg', "Não há registros válidos para importar.");
            return redirect(url('/') . '/contas-receber');
        }

        $idColaborador = auth()->user()->ID_COLABORADOR ?? 0;
        $sucessos = 0;
        $erros = [];

        foreach ($prontos as $item) {
            try {
                $payloadBaixa = [
                    [
                        'id' => $item['id_pedido'],
                        'valor' => $item['valor_pago']
                    ]
                ];
                $this->pedidoRepository->registrarBaixaManual($payloadBaixa, $idColaborador, true);
                $sucessos++;
            } catch (\Exception $e) {
                $erros[] = "Erro no pedido {$item['num_pedido']}: " . $e->getMessage();
            }
        }

        $log = [
            'sucessos' => $sucessos,
            'erros' => $erros
        ];

        session()->put('baixas_log_importacao', $log);
        session()->forget(['baixas_raw_data', 'baixas_prontas', 'baixas_nao_encontradas']);

        return redirect(url('/') . '/contas-receber/importar/log');
    }

    public function logImportacao() {
        $log = session()->get('baixas_log_importacao', ['sucessos' => 0, 'erros' => []]);
        return view('pages.importar_baixas_log', ['log' => $log]);
    }
}
