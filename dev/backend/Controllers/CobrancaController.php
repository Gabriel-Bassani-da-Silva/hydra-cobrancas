<?php
namespace App\Controllers;

use App\Services\CobrancaService;
use App\Repositories\PedidoRepository;


use Illuminate\Support\Facades\DB;

class CobrancaController extends Controller {
    private $cobrancaService;
    private $pedidoModel;

    public function __construct(CobrancaService $cobrancaService) {
        $this->cobrancaService = $cobrancaService;
        $this->pedidoModel = new PedidoRepository();
    }

    public function index() {
        $aba = request()->query()['aba'] ?? 'clientes';
        
        $isPagos = false; // Na tela de cobrança só interessa os em aberto/parciais

        // Puxamos os resumos gerais do PedidoModel, mas apenas com os clientes 'livres' (não cobrados atualmente)
        $resumoClientes = $this->pedidoModel->getResumoClientes(true);
        $resumoRepresentantes = $this->pedidoModel->getResumoRepresentantes(true);
        
        $resCli = $this->pedidoModel->getResumoContatosFinanceirosClientes(true);
        $resRep = $this->pedidoModel->getResumoContatosFinanceirosRepresentantes(true);
        $resumoContatosFinanceiros = [];
        foreach ($resCli as $row) {
            $resumoContatosFinanceiros[$row['ID_CF']] = $row;
        }
        foreach ($resRep as $row) {
            if (!isset($resumoContatosFinanceiros[$row['ID_CF']])) {
                $resumoContatosFinanceiros[$row['ID_CF']] = $row;
            } else {
                $resumoContatosFinanceiros[$row['ID_CF']]['QTD_CONTAS'] += $row['QTD_CONTAS'];
                $resumoContatosFinanceiros[$row['ID_CF']]['QTD_PARCELAS'] += $row['QTD_PARCELAS'];
                $resumoContatosFinanceiros[$row['ID_CF']]['TOTAL_VALOR'] += $row['TOTAL_VALOR'];
                if ($row['VENCIMENTO_MAIS_ANTIGO'] < $resumoContatosFinanceiros[$row['ID_CF']]['VENCIMENTO_MAIS_ANTIGO']) {
                    $resumoContatosFinanceiros[$row['ID_CF']]['VENCIMENTO_MAIS_ANTIGO'] = $row['VENCIMENTO_MAIS_ANTIGO'];
                }
            }
        }

        // Puxamos quem está cobrando o quê
        $cobrancasAtivas = $this->cobrancaService->getCobrancasAtivas();

        $title = 'Tela de Cobranças';
        
        return view('pages.cobrancas', [
            'aba' => $aba,
            'resumoClientes' => $resumoClientes,
            'resumoRepresentantes' => $resumoRepresentantes,
            'resumoContatosFinanceiros' => $resumoContatosFinanceiros,
            'cobrancasAtivas' => $cobrancasAtivas,
            'title' => $title
        ]);
    }

    public function puxar() {
        if (!request()->isMethod('post')) {
            return response()->json(['success' => false, 'error' => 'Método inválido'], 405 ?: 200);
        }

        $input = request()->json()->all();

        $tipo = $input['tipo'] ?? ''; // 'clientes', 'representantes' ou 'financeiros'
        $idAgrupamento = $input['id'] ?? '';
        $clientesSelecionados = $input['clientes'] ?? []; // array de ID_CONTATO_BLING dos clientes selecionados

        $idColaborador = auth()->user()->ID_COLABORADOR ?? null;

        if (!$idColaborador || !$tipo || !$idAgrupamento || empty($clientesSelecionados)) {
            return response()->json(['success' => false, 'error' => 'Parâmetros incompletos.'], 400 ?: 200);
        }

        try {
            // Bloqueio de cobrança para o SAC
            if ($tipo === 'financeiros' || $tipo === 'representantes') {
                $nome = '';
                if ($tipo === 'financeiros') {
                    $nome = DB::table('CONTATO_FINANCEIRO')->where('ID_CONTATO', $idAgrupamento)->value('NOME_CONTATO');
                } else {
                    $nome = DB::table('CONTATO_EXTERNO')->where('ID_CONTATO_BLING', $idAgrupamento)->value('NOME_CONTATO');
                }
                
                if ($nome && stripos($nome, 'sac por') !== false) {
                    throw new \Exception('Não é permitido realizar cobrança para o SAC.');
                }
            }

            $idCobranca = $this->cobrancaService->puxarCobranca($idColaborador, $tipo, $idAgrupamento, $clientesSelecionados);
            return response()->json(['success' => true, 'id_cobranca' => $idCobranca]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500 ?: 200);
        }
    }

    public function atualizarPedidos() {
        if (!request()->isMethod('post')) {
            return response()->json(['success' => false, 'error' => 'Método inválido'], 405 ?: 200);
        }

        $input = request()->json()->all();
        $idCobranca = $input['id_cobranca'] ?? 0;

        if (!$idCobranca) {
            return response()->json(['success' => false, 'error' => 'ID da cobrança não informado.'], 400 ?: 200);
        }

        try {
            $this->cobrancaService->atualizarPedidosCobranca($idCobranca);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500 ?: 200);
        }
    }

    public function desistir() {
        if (!request()->isMethod('post')) {
            return response()->json(['success' => false, 'error' => 'Método inválido'], 405 ?: 200);
        }

        $input = request()->json()->all();
        $idCobranca = $input['id_cobranca'] ?? 0;
        $idColaborador = auth()->user()->ID_COLABORADOR ?? 0;

        if (!$idCobranca) {
            return response()->json(['success' => false, 'error' => 'ID da cobrança não informado.'], 400 ?: 200);
        }

        try {
            $this->cobrancaService->cancelarCobranca($idCobranca, $idColaborador);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500 ?: 200);
        }
    }

    public function apiClientesAgrupamento() {
        $tipo = request()->query()['tipo'] ?? '';
        $id = (int)(request()->query()['id'] ?? 0);

        if (!$tipo || !$id) {
            return response()->json(['success' => false, 'error' => 'Parâmetros inválidos'], 400 ?: 200);
        }

        $clientes = [];

        try {
            if ($tipo === 'representantes') {
                $clientes = DB::select("
                    SELECT DISTINCT c.ID_CONTATO_BLING as id, ce.NOME_CONTATO as nome
                    FROM CLIENTE c
                    JOIN CONTATO_EXTERNO ce ON ce.ID_CONTATO_BLING = c.ID_CONTATO_BLING
                    JOIN PEDIDO p ON p.ID_CLIENTE = c.ID_CONTATO_BLING
                    WHERE p.ID_REPRESENTANTE = ? AND p.SITUACAO_PEDIDO IN (1, 3) AND p.EXIBIR = 1 AND p.DATA_VENCIMENTO < CURDATE()
                ", [$id]);
                // Cast properties to array for blade template
                $clientes = array_map(function($c) { return (array) $c; }, $clientes);
            } elseif ($tipo === 'financeiros') {
                // Para contatos financeiros, eles podem estar atrelados a um cliente ou representante
                // Vamos pegar os clientes dos pedidos que batem nos vínculos desse contato financeiro
                $clientes = DB::select("
                    SELECT DISTINCT c.ID_CONTATO_BLING as id, ce.NOME_CONTATO as nome
                    FROM PEDIDO p
                    JOIN CLIENTE c ON c.ID_CONTATO_BLING = p.ID_CLIENTE
                    JOIN CONTATO_EXTERNO ce ON ce.ID_CONTATO_BLING = c.ID_CONTATO_BLING
                    LEFT JOIN VINCULO_CONTATO_CLIENTE vcc ON vcc.ID_CLIENTE = p.ID_CLIENTE AND vcc.ID_CONTATO = ?
                    LEFT JOIN VINCULO_CONTATO_REPRESENTANTE vcr ON vcr.ID_REPRESENTANTE = p.ID_REPRESENTANTE AND vcr.ID_CONTATO = ?
                    WHERE (vcc.ID_CONTATO IS NOT NULL OR vcr.ID_CONTATO IS NOT NULL)
                      AND p.SITUACAO_PEDIDO IN (1, 3) AND p.EXIBIR = 1 AND p.DATA_VENCIMENTO < CURDATE()
                ", [$id, $id]);
                $clientes = array_map(function($c) { return (array) $c; }, $clientes);
            }

            // Buscar quem já está cobrando o quê
            $cobrancasAtivas = $this->cobrancaService->getCobrancasAtivas();
            $clientesAtivos = $cobrancasAtivas['clientes'] ?? [];

            $html = view('components.modal_cobranca_clientes', [
                'clientes' => $clientes,
                'clientesAtivos' => $clientesAtivos
            ])->render();

            return response()->json(['success' => true, 'html' => $html, 'clientes' => $clientes]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500 ?: 200);
        }
    }
}
