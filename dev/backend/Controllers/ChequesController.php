<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChequesController extends Controller
{
    public function index()
    {
        $idFormaCheque = config('hydra.bling.formas_pagamento.cheque', 7179734);

        // Busca todos os pedidos que são cheque e tem alguma baixa (DETALHE_PAGAMENTO)
        $cheques = DB::table('PEDIDO as p')
            ->join('DETALHE_PAGAMENTO as dp', 'dp.ID_PEDIDO', '=', 'p.ID_PEDIDO')
            ->join('REGISTRO_PAGAMENTO as rp', 'rp.ID_REGISTRO', '=', 'dp.ID_REGISTRO')
            ->leftJoin('CONTATO_EXTERNO as c', 'c.ID_CONTATO_BLING', '=', 'p.ID_CLIENTE')
            ->leftJoin('COLABORADOR as col', 'col.ID_COLABORADOR', '=', 'rp.ID_COLABORADOR')
            ->where('p.ID_FORMA_PAGAMENTO', $idFormaCheque)
            ->select(
                'p.ID_PEDIDO',
                'p.NUM_PEDIDO',
                'p.TOTAL_PEDIDO',
                'p.DATA_VENCIMENTO',
                'p.STATUS_CHEQUE',
                'c.NOME_CONTATO as CLIENTE',
                'col.NOME_COLABORADOR',
                'rp.DATA_REGISTRO',
                'dp.VALOR_PAGO_PEDIDO',
                'dp.ID_DETALHE'
            )
            ->orderBy('rp.DATA_REGISTRO', 'desc')
            ->get();

        $pendentes = $cheques->where('STATUS_CHEQUE', 'pendente');
        $compensados = $cheques->where('STATUS_CHEQUE', 'compensado');

        return view('pages.cheques.index', [
            'pendentes' => $pendentes,
            'compensados' => $compensados
        ]);
    }

    public function compensar(Request $request, $idPedido)
    {
        try {
            DB::table('PEDIDO')
                ->where('ID_PEDIDO', $idPedido)
                ->update(['STATUS_CHEQUE' => 'compensado']);
            
            return redirect('/contas-receber/cheques')->with('success_message', 'Cheque compensado com sucesso! O valor agora faz parte do Total Recebido geral.');
        } catch (\Exception $e) {
            return redirect('/contas-receber/cheques')->with('error_message', 'Erro ao compensar cheque: ' . $e->getMessage());
        }
    }

    public function devolver(Request $request, $idDetalhe)
    {
        try {
            DB::beginTransaction();

            // Busca o detalhe e o registro
            $detalhe = DB::table('DETALHE_PAGAMENTO')->where('ID_DETALHE', $idDetalhe)->first();
            if (!$detalhe) throw new \Exception("Baixa não encontrada.");

            $idRegistro = $detalhe->ID_REGISTRO;

            // Exclui o detalhe (a baixa do pedido)
            DB::table('DETALHE_PAGAMENTO')->where('ID_DETALHE', $idDetalhe)->delete();

            // Verifica se o registro ficou sem detalhes, se sim, exclui o registro
            $outrosDetalhes = DB::table('DETALHE_PAGAMENTO')->where('ID_REGISTRO', $idRegistro)->count();
            if ($outrosDetalhes === 0) {
                DB::table('REGISTRO_PAGAMENTO')->where('ID_REGISTRO', $idRegistro)->delete();
            }

            DB::commit();
            return redirect('/contas-receber/cheques')->with('success_message', 'Cheque devolvido/Não pago. A baixa foi excluída e o pedido retornou para cobrança.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect('/contas-receber/cheques')->with('error_message', 'Erro ao devolver cheque: ' . $e->getMessage());
        }
    }

    public function converterParaCheque(Request $request, $idPedido)
    {
        try {
            $ids = explode(',', $idPedido);
            $idFormaCheque = config('hydra.bling.formas_pagamento.cheque', 7179734);
            DB::table('PEDIDO')
                ->whereIn('ID_PEDIDO', $ids)
                ->update([
                    'ID_FORMA_PAGAMENTO' => $idFormaCheque,
                    'STATUS_CHEQUE' => 'pendente'
                ]);

            return response()->json(['success' => true, 'message' => 'Pedido(s) convertido(s) para Cheque com sucesso.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao converter: ' . $e->getMessage()], 500);
        }
    }
}
