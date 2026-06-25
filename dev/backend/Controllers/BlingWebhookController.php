<?php
namespace App\Controllers;

use Illuminate\Support\Facades\Log;
use App\Integrations\Bling\BlingService;
use App\Repositories\PedidoRepository;
use App\Repositories\ContatoRepository;

class BlingWebhookController extends Controller {

    private $blingService;
    private $pedidoRepository;
    private $contatoRepository;

    public function __construct(
        BlingService $blingService,
        PedidoRepository $pedidoRepository,
        ContatoRepository $contatoRepository
    ) {
        $this->blingService = $blingService;
        $this->pedidoRepository = $pedidoRepository;
        $this->contatoRepository = $contatoRepository;
    }

    /**
     * Rota responsável por receber e despachar os webhooks do Bling.
     * Deve ser cadastrada no Bling como: POST /api/webhooks/bling
     */
    public function handle() {
        // O Bling envia o payload em JSON no corpo da requisição
        $payload = request()->json();

        // Se o Bling enviar um array de dados, normalmente vem dentro de 'data'
        $data = $payload['data'] ?? [];

        if (empty($data)) {
            return response()->json(['status' => 'ignored', 'message' => 'Payload vazio'], 200);
        }

        // Recuperar o tipo de evento que gerou o webhook
        $evento = $payload['tipo'] ?? 'desconhecido';

        Log::info("Webhook recebido do Bling", [
            'evento' => $evento,
            'data_count' => count($data)
        ]);

        foreach ($data as $item) {
            $idBling = (int)($item['id'] ?? 0);
            if (!$idBling) {
                continue;
            }

            try {
                if (strpos($evento, 'conta.receber') !== false) {
                    $conta = $this->blingService->getContaReceber($idBling);
                    if ($conta) {
                        $this->pedidoRepository->importarPedidos([$conta], $this->blingService, 'upsert');
                    }
                } elseif (strpos($evento, 'contato') !== false) {
                    $contato = $this->blingService->getContato($idBling);
                    if ($contato) {
                        $this->contatoRepository->importarContatos([$contato]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Erro ao processar item do webhook", [
                    'id_bling' => $idBling,
                    'evento' => $evento,
                    'erro' => $e->getMessage()
                ]);
            }
        }

        // O Bling exige que a resposta seja sempre 200 OK para confirmar o recebimento
        return response()->json(['status' => 'success'], 200);
    }
}
