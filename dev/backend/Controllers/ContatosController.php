<?php
namespace App\Controllers;

use App\Repositories\ContatoRepository;

class ContatosController extends Controller {
    private $model;

    public function __construct() {
        
        $this->model = new ContatoRepository();
    }



    public function index() {
        $aba = request()->query()['aba'] ?? 'clientes';
        $somenteComTelefone = isset(request()->query()['com_telefone']) && request()->query()['com_telefone'] == '1';
        $somenteConfirmados = isset(request()->query()['com_confirmado']) && request()->query()['com_confirmado'] == '1';
        $somenteTentativas = isset(request()->query()['com_tentativa']) && request()->query()['com_tentativa'] == '1';
        $inadimplentes = isset(request()->query()['inadimplentes']) && request()->query()['inadimplentes'] == '1';
        $clientes = $this->model->getClientesComTelefones($somenteComTelefone, $somenteConfirmados, $somenteTentativas, $inadimplentes);
        $representantes = $this->model->getRepresentantesComTelefones($somenteComTelefone, $somenteConfirmados, $somenteTentativas, $inadimplentes);
        $semTelefone = $this->model->getClientesSemTelefone($inadimplentes);
        $contatosFinanceiros = $this->model->getAllContatosFinanceiros($somenteConfirmados, $somenteTentativas);

        return view('pages.contatos', [
            'aba' => $aba,
            'somenteComTelefone' => $somenteComTelefone,
            'somenteConfirmados' => $somenteConfirmados,
            'inadimplentes' => $inadimplentes,
            'clientes' => $clientes,
            'representantes' => $representantes,
            'semTelefone' => $semTelefone,
            'contatosFinanceiros' => $contatosFinanceiros
        ]);
    }

    public function salvarTelefone() {
        
        $action = request()->post()['action'] ?? 'add';
        $idContato = request()->post()['id_contato'] ?? null;
        $idTel = request()->post()['id_tel'] ?? null;
        $numTel = request()->post()['num_tel'] ?? '';

        if ($action === 'edit' && $idTel) {
            $this->model->editarTelefone($idTel, $numTel);
        } elseif ($idContato) {
            $this->model->adicionarTelefone($idContato, $numTel);
        }

        $aba = request()->post()['aba'] ?? 'clientes';
        return redirect(url('/') . '/contatos?aba=' . $aba);
        exit;
    }

    public function excluirTelefone() {
        
        $idTel = request()->post()['id_tel'] ?? null;
        if ($idTel) $this->model->excluirTelefone($idTel);

        $aba = request()->post()['aba'] ?? 'clientes';
        return redirect(url('/') . '/contatos?aba=' . $aba);
    }

    public function toggleConfirmado() {
        
        $idTel = request()->post()['id_tel'] ?? null;
        if ($idTel) $this->model->toggleConfirmado($idTel);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return response()->json(['ok' => true]);
        }

        $aba = request()->post()['aba'] ?? 'clientes';
        return redirect(url('/') . '/contatos?aba=' . $aba);
    }

    public function toggleOrigem() {
        
        $idTel = request()->post()['id_tel'] ?? null;
        if ($idTel) $this->model->toggleOrigem($idTel);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return response()->json(['ok' => true]);
        }

        $aba = request()->post()['aba'] ?? 'clientes';
        return redirect(url('/') . '/contatos?aba=' . $aba);
    }

    public function salvarContatoFinanceiro() {
        
        $idContatoFin = request()->post()['id_contato_fin'] ?? null;
        $nome = request()->post()['nome'] ?? '';
        $numTel = request()->post()['num_tel'] ?? '';
        $vinculos = request()->post()['vinculos'] ?? [];

        if ($idContatoFin) {
            $this->model->editarContatoFinanceiro((int)$idContatoFin, $nome, $numTel, $vinculos);
        } else {
            if (!empty($vinculos)) {
                $this->model->adicionarContatoFinanceiro($nome, $numTel, $vinculos);
            }
        }

        return redirect(url('/') . '/contatos?aba=financeiros');
    }

    public function excluirContatoFinanceiro() {
        
        $idContatoFin = request()->post()['id_contato_fin'] ?? null;
        if ($idContatoFin) $this->model->excluirContatoFinanceiro($idContatoFin);

        $aba = request()->post()['aba'] ?? 'financeiros';
        return redirect(url('/') . '/contatos?aba=' . $aba);
    }

    /** JSON endpoint: buscar contatos para autocomplete */
    public function apiContatos() {
        
        $q = request()->query()['q'] ?? '';
        $tipo = request()->query()['tipo'] ?? '';
        $results = $this->model->buscarContatos($q, $tipo);
        return response()->json($results);
    }

    /** JSON endpoint: buscar telefones para autocomplete */
    public function apiTelefones() {
        
        $q = request()->query()['q'] ?? '';
        $results = $this->model->buscarTelefones($q);
        return response()->json($results);
    }

    /** Sincronizar contatos via Bling */
    public function sincronizarContatos() {
        
        $blingService = new \App\Integrations\Bling\BlingService();
        $ultimaSinc = $blingService->getUltimaSincContatos();
        $resContatos = $blingService->getAllContatos($ultimaSinc);
        $contatos = $resContatos['contatos'];

        $novosContatos  = $this->model->importarContatos($contatos);
        $novosTelefones = $this->model->importarTelefones($contatos);
        $novosClientes  = $this->model->importarClientes();

        // Atualiza a data do último sync
        $blingService->updateUltimaSincContatos(date('Y-m-d H:i:s'));

        $aba = request()->query()['aba'] ?? 'clientes';
        session()->flash('flash_msg', "Contatos atualizados! Novos contatos/atualizados: " . count($novosContatos) . ", Novos telefones: " . count($novosTelefones) . ", Novos clientes: " . $novosClientes);
        return redirect(url('/') . '/contatos?aba=' . $aba);
    }

    /** Sincronizar vendedores via Bling */
    public function sincronizarVendedores() {
        
        $blingService = new \App\Integrations\Bling\BlingService();
        // Vendedores são poucos, fazemos sincronização completa sempre para evitar falhas em atualizações
        $resVendedores = $blingService->getAllVendedores(null);
        $vendedores = $resVendedores['vendedores'];

        $novosRepresentantes = $this->model->importarRepresentantes($vendedores);
        $this->model->importarClientes();

        // Atualiza a data do último sync
        $blingService->updateUltimaSincContatos(date('Y-m-d H:i:s'));

        $aba = 'representantes'; // Força para ir pra aba de representantes e ver as mudanças
        session()->flash('flash_msg', "Vendedores atualizados! Vendedores verificados: " . count($novosRepresentantes));
        return redirect(url('/') . '/contatos?aba=' . $aba);
    }

    /** Sincronizar um único contato via Bling */
    public function sincronizarUnico() {
        
        $idContato = request()->post()['id_contato'] ?? request()->query()['id_contato'] ?? null;
        $aba = request()->query()['aba'] ?? request()->post()['aba'] ?? 'clientes';
        
        if (!$idContato) {
            session()->flash('flash_msg', "ID do contato não fornecido.");
            return redirect(url('/') . '/contatos?aba=' . $aba);
        }

        $blingService = new \App\Integrations\Bling\BlingService();
        $contato = $blingService->getContato((int)$idContato);

        if (!$contato) {
            session()->flash('flash_msg', "Contato não encontrado no Bling ou erro na API.");
            return redirect(url('/') . '/contatos?aba=' . $aba);
        }

        $contatosArray = [$contato];
        $novosContatos  = $this->model->importarContatos($contatosArray);
        $novosTelefones = $this->model->importarTelefones($contatosArray);
        $this->model->importarClientes(); // Sync clients relation just in case

        session()->flash('flash_msg', "Contato {$contato['nome']} ({$idContato}) atualizado com sucesso! Novos telefones: " . count($novosTelefones));
        return redirect(url('/') . '/contatos?aba=' . $aba);
    }

    // ── Importação via Planilha (CSV) ────────────────────────────────────

    public function importar() {
        

        // Auto-download XLSX libraries if missing
        $dir = __DIR__ . '/../../Libraries/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        
        $files = [
            'SimpleXLSX.php' => 'https://raw.githubusercontent.com/shuchkin/simplexlsx/master/src/SimpleXLSX.php',
            'SimpleXLSXGen.php' => 'https://raw.githubusercontent.com/shuchkin/simplexlsxgen/master/src/SimpleXLSXGen.php'
        ];
        
        foreach ($files as $name => $url) {
            if (!file_exists($dir . $name)) {
                $content = @file_get_contents($url);
                if ($content) {
                    file_put_contents($dir . $name, $content);
                }
            }
        }

        return view('pages.importar_contatos');
    }

    public function downloadTemplate() {
        
        
        if (!file_exists(__DIR__ . '/../../Libraries/SimpleXLSXGen.php')) {
            die('Biblioteca XLSXGen não instalada. Execute o setup_xlsx.php.');
        }
        require_once __DIR__ . '/../../Libraries/SimpleXLSXGen.php';
        
        $books = [
            ['NOME', 'CPF_CNPJ', 'TELEFONE', 'STATUS'],
            ['Empresa Cliente LTDA', '12345678901', '11888888888', 'Confirmado'],
            ['José Silva', '10987654321', '11999999999', 'Tentativa']
        ];
        
        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($books);
        $xlsx->downloadAs('template_importacao_contatos.xlsx');
    }

    public function processarImportacao() {
        
        $dir = __DIR__ . '/../../Libraries/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        
        if (!file_exists($dir . 'SimpleXLSX.php')) {
            $content = @file_get_contents('https://raw.githubusercontent.com/shuchkin/simplexlsx/master/src/SimpleXLSX.php');
            if ($content) file_put_contents($dir . 'SimpleXLSX.php', $content);
        }

        if (!file_exists($dir . 'SimpleXLSX.php')) {
            die('Erro ao baixar Biblioteca SimpleXLSX.');
        }
        require_once $dir . 'SimpleXLSX.php';

        if (!request()->isMethod('post') || !request()->hasFile('arquivo_xlsx')) {
            session()->flash('flash_msg', "Nenhum arquivo enviado.");
            return redirect(url('/') . '/contatos/importar');
        }

        $file = request()->file('arquivo_xlsx')->getRealPath();
        if ($xlsx = \Shuchkin\SimpleXLSX::parse($file)) {
            $allRows = $xlsx->rows();
            
            // Salva TODAS as linhas brutas na sessão (incluindo o possível cabeçalho)
            session()->put('import_raw_data', $allRows);

            // Detecta os nomes das colunas da primeira linha para exibir no mapeamento
            $primeiraLinha = $allRows[0] ?? [];
            $amostra = array_slice($allRows, 0, 6); // Primeiras 6 linhas como amostra

            // Garante que o total de colunas seja baseado na linha mais longa da amostra
            $maxCols = 0;
            foreach ($amostra as $row) {
                if (is_array($row) && count($row) > $maxCols) {
                    $maxCols = count($row);
                }
            }
            $primeiraLinha = array_pad($primeiraLinha, $maxCols, '');

            return view('pages.importar_mapeamento', [
                'amostra' => $amostra,
                'primeiraLinha' => $primeiraLinha,
                'allRows' => $allRows
            ]);
        } else {
            session()->flash('flash_msg', "Erro ao processar arquivo XLSX.");
            return redirect(url('/') . '/contatos/importar');
        }
    }

    public function processarMapeamento() {
        

        if (!request()->isMethod('post') || !session()->has('import_raw_data')) {
            return redirect(url('/') . '/contatos/importar');
        }

        $allRows = session()->get('import_raw_data');
        $ignorarCabecalho = isset(request()->post()['ignorar_cabecalho']);
        
        // Mapeamento: índice da coluna da planilha → campo do sistema
        $mapNome     = request()->post()['col_nome'] ?? '-1';
        $mapDoc      = request()->post()['col_cpf_cnpj'] ?? '-1';
        $mapTel      = request()->post()['col_telefone'] ?? '-1';
        $mapStatus   = request()->post()['col_status'] ?? '-1';

        // Remove cabeçalho se marcado
        if ($ignorarCabecalho && !empty($allRows)) {
            array_shift($allRows);
        }

        $linhasValidadas = [];
        foreach ($allRows as $data) {
            if (empty(array_filter($data))) continue;

            $linhaCrua = [
                'nome'     => ($mapNome >= 0)   ? trim($data[$mapNome] ?? '')   : '',
                'cpf_cnpj' => ($mapDoc >= 0)    ? trim($data[$mapDoc] ?? '')    : '',
                'telefone' => ($mapTel >= 0)    ? trim($data[$mapTel] ?? '')    : '',
                'status'   => ($mapStatus >= 0) ? trim($data[$mapStatus] ?? '') : ''
            ];

            $resultado = $this->model->validarLinhaImportacao($linhaCrua);
            $linhasValidadas[] = $resultado;
        }

        session()->put('import_preview', $linhasValidadas);
        session()->forget('import_raw_data');

        return view('pages.importar_preview', [
            'linhasValidadas' => $linhasValidadas
        ]);
    }

    public function confirmarImportacao() {
        

        if (!request()->isMethod('post') || !session()->has('import_preview')) {
            return redirect(url('/') . '/contatos/importar');
        }

        $linhasValidadas = session()->get('import_preview');
        
        $res = $this->model->executarImportacaoValidada($linhasValidadas);
        
        session()->forget('import_preview');
        
        // Salva o log na sessão para exibir na tela de resultado
        session()->put('import_log', [
            'sucesso' => $res['sucesso'],
            'erros' => $res['erros'],
            'log' => $res['log'],
            'data' => date('d/m/Y H:i:s')
        ]);
        
        return redirect(url('/') . '/contatos/importar/log');
    }

    public function logImportacao() {
        

        if (!session()->has('import_log')) {
            session()->flash('flash_msg', "Nenhum log de importação disponível.");
            return redirect(url('/') . '/contatos/importar');
        }

        $importLog = session()->get('import_log');
        // Não limpa da sessão para que o usuário possa recarregar a página
        return view('pages.importar_log', ['importLog' => $importLog]);
    }
}
