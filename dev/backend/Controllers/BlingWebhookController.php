<?php
namespace App\Controllers;

use Illuminate\Support\Facades\Log;

class BlingWebhookController extends Controller {

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

        // Recuperar o tipo de evento que gerou o webhook (depende de como o Bling estrutura o header/payload)
        // No Bling V3, webhooks de contas a receber costumam ser 'conta.receber.alterada'
        // Mas a identificação exata deve ser avaliada de acordo com a documentação do Webhook.
        $evento = $payload['tipo'] ?? 'desconhecido';

        Log::info("Webhook recebido do Bling", [
            'evento' => $evento,
            'data_count' => count($data)
        ]);

        foreach ($data as $item) {
            $idBling = $item['id'] ?? null;

            if (!$idBling) {
                continue;
            }

            // TODO: Aqui será implementada a lógica de buscar o ID atualizado na API
            // (ou utilizar os próprios dados do payload se vierem completos)
            // e chamar $repository->importarPedidos([$item], $blingService, 'upsert');
            //
            // Exemplo de rascunho futuro:
            // $detalhe = $blingService->getContaReceber($idBling);
            // se ($detalhe) importa para o banco...
        }

        // O Bling exige que a resposta seja sempre 200 OK para confirmar o recebimento
        return response()->json(['status' => 'success'], 200);
    }
}
