<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Cobranca;
use App\Models\Pedido;
use Exception;

class CobrancaService {

    public function getCobrancasAtivas(): array {
        $ativas = [
            'clientes' => [],
            'financeiros' => [],
            'representantes' => []
        ];

        // This view is very fast, let's just query it using DB facade.
        $rows = DB::table('vw_cobrancas_ativas')->get();
        
        foreach ($rows as $row) {
            $tipo = $row->TIPO;
            $ativas[$tipo][$row->ID] = [
                'ID_COBRANCA' => $row->ID_COBRANCA,
                'NOME_COLABORADOR' => $row->NOME_COLABORADOR,
                'ID_COLABORADOR' => $row->ID_COLABORADOR
            ];
        }

        return $ativas;
    }

    public function puxarCobranca(int $idColaborador, string $tipo, string $idAgrupamento, array $idsClientesSelecionados) {
        return DB::transaction(function () use ($idColaborador, $tipo, $idAgrupamento, $idsClientesSelecionados) {
            $idContato = null;
            $idRepresentante = null;

            if ($tipo === 'financeiros') {
                $idContato = (int)$idAgrupamento;
            } elseif ($tipo === 'representantes') {
                $idRepresentante = (int)$idAgrupamento;
            } else {
                $idsClientesSelecionados = [(int)$idAgrupamento];
            }

            // 1. Criar registro
            $cobranca = Cobranca::create([
                'DATA_INICIO' => DB::raw('CURDATE()'),
                'STATUS_ATENDIMENTO' => 'Iniciado',
                'ID_COLABORADOR' => $idColaborador,
                'ID_CONTATO' => $idContato,
                'ID_REPRESENTANTE' => $idRepresentante
            ]);

            $idCobranca = $cobranca->ID_COBRANCA;

            // 2. Inserir clientes vinculados
            if (!empty($idsClientesSelecionados)) {
                $cobranca->clientes()->syncWithoutDetaching($idsClientesSelecionados);
            }

            // 3. Puxar todos os pedidos e salvar
            $this->atualizarPedidosCobranca($idCobranca);

            return $idCobranca;
        });
    }

    public function atualizarPedidosCobranca(int $idCobranca) {
        $cobranca = Cobranca::find($idCobranca);
        if (!$cobranca) return;

        // Limpa os antigos
        $cobranca->pedidos()->detach();

        // Pega clientes
        $clientesIds = $cobranca->clientes()->pluck('CLIENTE.ID_CONTATO_BLING')->toArray();
        if (empty($clientesIds)) return;

        // Pega pedidos elegíveis considerando pagamento efetivo (Bling ou Local)
        $pedidosIds = DB::table('PEDIDO as p')
            ->leftJoin(DB::raw('(SELECT ID_PEDIDO, SUM(VALOR_PAGO_PEDIDO) AS PAGO_LOCAL FROM DETALHE_PAGAMENTO GROUP BY ID_PEDIDO) as dp'), 'dp.ID_PEDIDO', '=', 'p.ID_PEDIDO')
            ->whereIn('p.ID_CLIENTE', $clientesIds)
            ->whereIn('p.SITUACAO_PEDIDO', [1, 3])
            ->where('p.DATA_VENCIMENTO', '<', DB::raw('CURDATE()'))
            ->where('p.EXIBIR', 1)
            ->where(DB::raw('GREATEST(p.VALOR_PAGO_BLING, COALESCE(dp.PAGO_LOCAL, 0))'), '<', DB::raw('p.TOTAL_PEDIDO'))
            ->pluck('p.ID_PEDIDO')
            ->toArray();

        if (!empty($pedidosIds)) {
            $cobranca->pedidos()->syncWithoutDetaching($pedidosIds);
        }
    }

    public function cancelarCobranca(int $idCobranca, int $idColaborador): bool {
        $cobranca = Cobranca::where('ID_COBRANCA', $idCobranca)
            ->where('ID_COLABORADOR', $idColaborador)
            ->whereNull('DATA_FIM')
            ->first();

        if ($cobranca) {
            $cobranca->DATA_FIM = DB::raw('NOW()');
            $cobranca->STATUS_ATENDIMENTO = 'Cancelada';
            return $cobranca->save();
        }
        return false;
    }
}
