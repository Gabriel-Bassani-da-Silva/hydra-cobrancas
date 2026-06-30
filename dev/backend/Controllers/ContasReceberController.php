<?php
namespace App\Controllers;

use App\Repositories\PedidoRepository;


use App\Integrations\Bling\BlingService;
use App\Services\CobrancaService;
use App\Services\ContasReceberFiltroService;

class ContasReceberController extends Controller {
    private $model;
    private $cobrancaService;
    private $filtroService;

    public function __construct() {
        $this->model = new PedidoRepository();
        $this->cobrancaService = new CobrancaService();
        $this->filtroService = new ContasReceberFiltroService($this->model, $this->cobrancaService);
    }



    /**
     * Retorna o HTML das contas a receber de um contato financeiro (AJAX).
     */
    public function detalhesContatoFinanceiro(int $idContato) {
        $contas = $this->model->getContasContatoFinanceiro($idContato);
        return view('partials.tabela_contas', ['contas' => $contas]);
    }

    /**
     * Página principal de Contas a Receber.
     * Exibe resumos por cliente e representante em abas.
     */
    public function index() {
        $aba = request()->query('aba', 'clientes');
        $grupo = request()->query('grupo', 'padrao'); // 'padrao' ou 'financeiro'
        $status = request()->query('status', 'pendentes');

        $dados = $this->filtroService->compilarDadosDaAba($aba, $grupo, $status);

        return view('pages.contas_receber.index', [
            'aba' => $aba,
            'grupo' => $grupo,
            'resumoClientes' => $dados['resumoClientes'],
            'resumoRepresentantes' => $dados['resumoRepresentantes'],
            'resumoContatosFinanceiros' => $dados['resumoContatosFinanceiros'],
            'todosPedidos' => $dados['todosPedidos'],
            'isPagos' => ($status === 'pagos'),
            'todasBaixas' => $dados['todasBaixas'],
            'divergencias' => $dados['divergencias'],
            'totalInadimplenteFiltrado' => $dados['totalBannerVermelho'],
            'totais' => $dados['totais'],
            'ultimaSinc' => $dados['ultimaSinc'],
            'contagensAbas' => $dados['contagensAbas'],
            'cobrancasAtivas' => $dados['cobrancasAtivas'],
            'exibirAte' => $dados['exibirAte'],
            'exibirAPartirDe' => $dados['exibirAPartirDe'],
        ]);
    }

    /**
     * Sincroniza uma conta a receber específica do Bling pelo ID (ou múltiplas separadas por vírgula).
     */
    public function sincronizarUnico() {
        $idsRaw = request()->query()['id'] ?? null;
        if (!$idsRaw) {
            session()->flash('flash_msg', "ID da conta não informado.");
            session()->flash('flash_type', 'warning');
            return redirect(url('/') . '/contas-receber');
        }

        $blingService = new BlingService();
        if (!$blingService->isConnected()) {
            session()->flash('flash_msg', "Erro: Bling não está conectado. Configure as credenciais primeiro.");
            session()->flash('flash_type', 'error');
            return redirect(url('/') . '/contas-receber');
        }

        $ids = explode(',', $idsRaw);
        $contasEncontradas = [];
        foreach ($ids as $idStr) {
            $id = (int)trim($idStr);
            if ($id > 0) {
                $conta = $blingService->getContaReceber($id);
                if ($conta) {
                    $contasEncontradas[] = $conta;
                }
            }
        }

        if (empty($contasEncontradas)) {
            session()->flash('flash_msg', "Nenhuma das contas informadas foi encontrada no Bling ou houve erro na API.");
            session()->flash('flash_type', 'error');
            return redirect(url('/') . '/contas-receber');
        }

        $importResult = $this->model->importarPedidos($contasEncontradas, $blingService, 'upsert');

        $msg = "Sincronização individual concluída com sucesso!";
        if (!empty($importResult['erros'])) {
            $msg .= " Avisos: " . implode(', ', array_slice($importResult['erros'], 0, 3));
        }

        session()->flash('flash_msg', $msg);
        session()->flash('flash_type', 'success');

        $aba = request()->query()['aba'] ?? 'pedidos';
        return redirect(url('/') . '/contas-receber?aba=' . $aba);
        exit;
    }

    /**
     * Sincroniza contas a receber do Bling para o banco local.
     * Utiliza Delta Sync se possível, ou Full Sync.
     */
    public function sincronizar() {
        set_time_limit(0);
        

        $blingService = new BlingService();
        if (!$blingService->isConnected()) {
            session()->flash('flash_msg', "Erro: Bling não está conectado. Configure as credenciais primeiro.");
            session()->flash('flash_type', 'error');
            return redirect(url('/') . '/contas-receber');
        }

        // Se o usuário passar ?full=1, forçamos a busca completa para sanear a base
        $forceFull = isset(request()->query()['full']) && request()->query()['full'] == '1';
        $ultimaSinc = $forceFull ? null : $blingService->getUltimaSincContas();
        
        $resultado = $blingService->getAllContasReceber($ultimaSinc);
        $mode = $resultado['mode'] ?? 'full_sync';
        $contas = $resultado['contas'];

        // Dump de auditoria da última sincronização (útil para depuração).
        // Mantido em storage (fora do versionamento) e protegido contra falhas de IO.
        try {
            $logDir = storage_path('app/logs/sync');
            if (!is_dir($logDir)) {
                mkdir($logDir, 0775, true);
            }
            file_put_contents($logDir . '/last_sync_contas.json', json_encode($contas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Não foi possível gravar o dump de sincronização.', ['exception' => $e->getMessage()]);
        }

        // UPSERT em lote
        $importResult = $this->model->importarPedidos($contas, $blingService, $mode);
        
        if (empty($importResult['erros'])) {
            $blingService->updateUltimaSincContas(date('Y-m-d H:i:s'));
        }

        $msg = "Sincronização (Smart Sync Rápido) concluída! "
             . "Parcelas processadas: {$importResult['inseridos']}";

        if (!empty($importResult['erros'])) {
            $msg .= ". Erros: " . implode(', ', array_slice($importResult['erros'], 0, 3));
        }

        session()->flash('flash_msg', $msg);
        session()->flash('flash_type', empty($importResult['erros']) ? 'success' : 'warning');

        $aba = request()->query()['aba'] ?? 'abertas';
        return redirect(url('/') . '/contas-receber?aba=' . $aba);
    }

    /**
     * Atualiza os saldos das contas a receber do Bling.
     * Agora redireciona para a mesma inteligência do sincronizar (Upsert + Delta Sync).
     */
    public function atualizar() {
        // A lógica foi unificada para garantir melhor performance e bulk inserts.
        $this->sincronizar();
    }

    /**
     * Vincula todos os pedidos sem representante puxando o detalhe do Bling.
     */
    public function vincularRepresentantes() {
        set_time_limit(0);
        

        $blingService = new BlingService();
        if (!$blingService->isConnected()) {
            session()->flash('flash_msg', "Erro: Bling não está conectado. Configure as credenciais primeiro.");
            session()->flash('flash_type', 'error');
            return redirect(url('/') . '/contas-receber');
        }

        $pedidos = $this->model->getPedidosSemRepresentante();
        $atualizados = 0;

        foreach ($pedidos as $pedido) {
            $id = $pedido['ID_PEDIDO'];
            $detalhe = $blingService->getContaReceber($id);
            if ($detalhe && !empty($detalhe['vendedor']['id'])) {
                $idVendedor = $detalhe['vendedor']['id'];
                $this->model->atualizarRepresentantePedido($id, $idVendedor);
                $atualizados++;
            }
            // Bling API Limit = 3 requests / second -> 340ms delay is safe
            usleep(340000); 
        }

        session()->flash('flash_msg', "Processo concluído. Foram vinculados {$atualizados} pedidos pendentes aos seus respectivos representantes (de um total de " . count($pedidos) . " verificados).");
        session()->flash('flash_type', 'success');

        $aba = request()->query()['aba'] ?? 'pedidos';
        return redirect(url('/') . '/contas-receber?aba=' . $aba);
    }

    /**
     * API JSON: Registra baixa manual (pagamento local) para um ou mais pedidos.
     * Recebe via POST JSON: { baixas: [{ id: 123, valor: 50.00 }, ...] }
     */
    public function baixar() {
        
        if (!request()->isMethod('post')) {
            return response()->json(['success' => false, 'error' => 'Método não permitido']);
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (empty($data['baixas']) || !is_array($data['baixas'])) {
            return response()->json(['success' => false, 'error' => 'Dados inválidos']);
        }

        $idColaborador = auth()->user()->ID_COLABORADOR ?? 0;

        try {
            $sucesso = $this->model->registrarBaixaManual($data['baixas'], $idColaborador);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }


    public function togglePedra() {
        $idContato = request()->input('id_contato');
        if (!$idContato) {
            return response()->json(['ok' => false, 'error' => 'ID não informado']);
        }
        try {
            $contatoRepo = new \App\Repositories\ContatoRepository();
            $contatoRepo->togglePedra($idContato);
            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * JSON API: lista contas a receber com filtros.
     * Parâmetros GET: tipo (clientes|representantes), id, q (busca)
     */
    public function apiLista() {
        
        $tipo = request()->query()['tipo'] ?? 'clientes';
        $id = request()->query()['id'] ?? null;
        $q = request()->query()['q'] ?? '';

        try {
            if (!empty($q)) {
                $data = $this->model->buscarContas($q);
            } elseif ($id) {
                if ($tipo === 'representantes') {
                    $data = $this->model->getContasRepresentante((int)$id);
                } elseif ($tipo === 'financeiros') {
                    $data = $this->model->getContasContatoFinanceiro((int)$id);
                } else {
                    $data = $this->model->getContasCliente((int)$id);
                }
            } else {
                if ($tipo === 'representantes') {
                    $data = $this->model->getResumoRepresentantes();
                } elseif ($tipo === 'financeiros') {
                    // Prevenir erro se a função não existir (usar array vazio por enquanto)
                    $data = []; 
                } else {
                    $data = $this->model->getResumoClientes();
                }
            }

            $modalService = new \App\Services\ModalDetalhesService();
            if ($tipo === 'financeiros' || $tipo === 'representantes') {
                $grupos = $modalService->agruparParaFinanceirosOuRepresentantes($data);
            } else {
                $grupos = $modalService->agruparParaClientes($data);
            }

            $html = view('components.modal_detalhes_contas', ['data' => $data, 'grupos' => $grupos, 'tipo' => $tipo])->render();

            return response()->json(['data' => $data, 'total' => count($data), 'html' => $html]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage() . ' | Linha: ' . $e->getLine()]);
        }
    }

    /**
     * JSON API: busca uma conta específica no Bling por ID.
     */
    public function apiDetalhe() {
        
        $id = request()->query()['id'] ?? null;
        if (!$id) {
            return response()->json(['error' => 'ID não informado']);
        }

        $blingService = new BlingService();
        $conta = $blingService->getContaReceber((int)$id);

        if ($conta) {
            return response()->json(['data' => $conta]);
        } else {
            return response()->json(['error' => 'Conta não encontrada no Bling']);
        }
    }

    public function apiBaixasCliente() {
        $idCliente = request()->query()['id'] ?? null;
        if (!$idCliente) {
            return response()->json(['error' => 'ID do cliente não informado']);
        }

        $baixas = \Illuminate\Support\Facades\DB::table('DETALHE_PAGAMENTO as dp')
            ->join('REGISTRO_PAGAMENTO as rp', 'rp.ID_REGISTRO', '=', 'dp.ID_REGISTRO')
            ->join('PEDIDO as p', 'p.ID_PEDIDO', '=', 'dp.ID_PEDIDO')
            ->leftJoin('COLABORADOR as col', 'col.ID_COLABORADOR', '=', 'rp.ID_COLABORADOR')
            ->where('p.ID_CLIENTE', $idCliente)
            ->select(
                'dp.ID_DETALHE', 
                'dp.VALOR_PAGO_PEDIDO', 
                'rp.DATA_REGISTRO', 
                'p.NUM_PEDIDO', 
                'p.ID_PEDIDO', 
                'col.NOME_COLABORADOR'
            )
            ->orderBy('rp.DATA_REGISTRO', 'desc')
            ->get();

        $html = view('components.modal_baixas_cliente', ['baixas' => $baixas])->render();
        return response()->json(['data' => $baixas, 'html' => $html]);
    }

    /**
     * API: Busca detalhes de várias parcelas pelo ID para o modal de baixa local.
     */
    public function apiParcelasPorIds() {
        $req = request();
        $idsStr = $req->input('ids', '');
        
        if (empty($idsStr)) {
            return response()->json(['success' => false, 'error' => 'Nenhum ID fornecido.']);
        }
        
        $arrayBruto = explode(',', (string)$idsStr);
        $ids = [];
        foreach ($arrayBruto as $val) {
            if (is_numeric($val)) {
                $ids[] = $val;
            }
        }
        
        if (empty($ids)) {
            return response()->json(['success' => false, 'error' => 'IDs inválidos.']);
        }

        try {
            // Sincronizar as contas no Bling antes de buscar localmente
            $blingService = new BlingService();
            if ($blingService->isConnected()) {
                $contasEncontradas = [];
                foreach ($ids as $id) {
                    $conta = $blingService->getContaReceber((int)$id);
                    if ($conta) {
                        $contasEncontradas[] = $conta;
                    }
                }
                if (!empty($contasEncontradas)) {
                    $this->model->importarPedidos($contasEncontradas, $blingService, 'upsert');
                }
            }

            $parcelas = $this->model->getPedidosByIds($ids);
            return response()->json(['success' => true, 'data' => $parcelas]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Recuperação: Re-verifica no Bling todas as contas que foram marcadas
     * como pagas localmente (sit=2, valor_pago=total) para restaurar as que
     * ainda estão em aberto. Corrige dados corrompidos pela heurística de bulk.
     */
    public function recuperar() {
        set_time_limit(0);
        

        $blingService = new BlingService();
        if (!$blingService->isConnected()) {
            session()->flash('flash_msg', "Erro: Bling não está conectado.");
            session()->flash('flash_type', 'error');
            return redirect(url('/') . '/contas-receber');
        }

        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();

        // Busca contas que parecem ter sido dadas baixa em bulk:
        // - Situação 2 (pago) com VALOR_PAGO_BLING == TOTAL_PEDIDO (baixa automática)
        // - Ou Situação 5 (excluído) com EXIBIR = 0
        $stmt = $pdo->query(
            "SELECT ID_PEDIDO, TOTAL_PEDIDO, VALOR_PAGO_BLING, SITUACAO_PEDIDO, EXIBIR 
             FROM PEDIDO 
             WHERE (
                 (SITUACAO_PEDIDO = 2 AND VALOR_PAGO_BLING = TOTAL_PEDIDO AND TOTAL_PEDIDO > 0)
                 OR (SITUACAO_PEDIDO = 5 AND EXIBIR = 0)
             )"
        );
        $candidatas = $stmt->fetchAll();

        $restauradas = 0;
        $confirmadas = 0;
        $erros = 0;

        foreach ($candidatas as $conta) {
            $id = $conta['ID_PEDIDO'];
            $detalhe = $blingService->getContaReceber($id);

            if ($detalhe) {
                $sitBling = (int)($detalhe['situacao'] ?? 1);

                if (in_array($sitBling, [1, 3])) {
                    // Conta AINDA está em aberto no Bling — foi marcada errada!
                    $valorTotal = (float)($detalhe['valor'] ?? $conta['TOTAL_PEDIDO']);
                    $valorPago = 0;

                    if ($sitBling === 3) {
                        // Parcialmente pago — calcula valor pago real
                        $saldo = (float)($detalhe['saldo'] ?? $valorTotal);
                        $valorPago = max(0, $valorTotal - $saldo);
                    }

                    $stmtUpdate = $pdo->prepare(
                        "UPDATE PEDIDO SET 
                            SITUACAO_PEDIDO = :sit,
                            VALOR_PAGO_BLING = :pago,
                            EXIBIR = 1
                         WHERE ID_PEDIDO = :id"
                    );
                    $stmtUpdate->execute([
                        'sit' => $sitBling,
                        'pago' => $valorPago,
                        'id' => $id
                    ]);
                    $restauradas++;
                } else {
                    // Realmente está paga/cancelada — confirmada
                    $confirmadas++;
                }
            } else {
                // Não encontrada na API (excluída de verdade)
                $confirmadas++;
            }

            usleep(200000); // 200ms rate limit
        }

        session()->flash('flash_msg', "Recuperação concluída! Restauradas: {$restauradas} | Confirmadas como pagas: {$confirmadas}");
        session()->flash('flash_type', $restauradas > 0 ? 'warning' : 'success');

        return redirect(url('/') . '/contas-receber');
    }
}
