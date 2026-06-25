<?php
namespace App\Controllers;


use App\Integrations\Bling\BlingService;

class BlingController extends Controller {
    /**
     * Exibe a página de configuração do Bling
     */
    public function index() {
        return view('pages.config_bling');
    }

    /**
     * Salva as credenciais do Bling na tabela BLING_CONFIG
     */
    public function saveConfig() {


        if (request()->isMethod('post')) {
            $clientId = trim(request()->post()['client_id'] ?? '');
            $clientSecret = trim(request()->post()['client_secret'] ?? '');
            $redirectUri = trim(request()->post()['redirect_uri'] ?? '');

            if (!empty($clientId) && !empty($clientSecret) && !empty($redirectUri)) {
                $blingService = new BlingService();
                $success = $blingService->saveConfig($clientId, $clientSecret, $redirectUri);

                if ($success) {
                    session()->flash('success_message', "Configurações do Bling salvas com sucesso!");
                    return redirect()->route('bling-page');
                }
            }
        }

        session()->flash('error_message', "Erro ao salvar as configurações. Preencha todos os campos.");
        return redirect()->route('bling-page');
    }

    /**
     * Redireciona o usuário para a página de autorização do Bling
     */
    public function auth() {


        $blingService = new BlingService();
        if (!$blingService->isConfigured()) {
            session()->flash('error_message', "Configure as credenciais do Bling antes de conectar.");
            return redirect()->route('bling-page');
        }

        $authUrl = $blingService->getAuthorizationUrl();
        return redirect($authUrl);
    }

    /**
     * Callback do Bling
     */
    public function callback() {


        $code = request()->query()['code'] ?? null;
        if ($code) {
            $blingService = new BlingService();
            $success = $blingService->handleCallback($code);
            if ($success) {
                session()->flash('success_message', "Conexão com o Bling estabelecida!");
                return redirect()->route('bling-page');
            }
        }

        session()->flash('error_message', "Falha ao conectar com o Bling.");
        return redirect()->route('bling-page');
    }

    /**
     * Callback Manual do Bling (usuário cola o código)
     */
    public function manualCallback() {


        if (request()->isMethod('post')) {
            $code = trim(request()->post()['code'] ?? '');
            if (!empty($code)) {
                $blingService = new BlingService();
                $success = $blingService->handleCallback($code);
                if ($success) {
                    session()->flash('success_message', "Conexão com o Bling estabelecida via código manual!");
                    return redirect()->route('bling-page');
                }
            }
        }

        session()->flash('error_message', "Falha ao conectar. Verifique se o código está correto e não expirou.");
        return redirect()->route('bling-page');
    }

    public function saveExibirAte() {


        $pedidoModel = new \App\Repositories\PedidoRepository();

        if (request()->isMethod('post')) {
            $actionType = request()->post()['action_type'] ?? '';

            if ($actionType === 'limpar_partir') {
                $pedidoModel->setExibirAPartirDe(null);
            } elseif ($actionType === 'limpar_ate') {
                $pedidoModel->setExibirAte(null);
            } elseif ($actionType === 'salvar_partir') {
                $dataPartir = trim(request()->post()['exibir_a_partir_de'] ?? '');
                if (!empty($dataPartir)) {
                    $pedidoModel->setExibirAPartirDe($dataPartir);
                } else {
                    $pedidoModel->setExibirAPartirDe(null);
                }
            } elseif ($actionType === 'salvar_ate') {
                $dataAte = trim(request()->post()['exibir_ate'] ?? '');
                if (!empty($dataAte)) {
                    $pedidoModel->setExibirAte($dataAte);
                } else {
                    $pedidoModel->setExibirAte(null);
                }
            }
        }

        // Retorna para a mesma página
        $referer = request()->headers->get('referer', url('/'));
        return redirect($referer);
    }
}
