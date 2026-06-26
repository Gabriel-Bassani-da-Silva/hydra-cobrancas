<?php

namespace App\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\DetalhePagamento;

class BaixaController extends Controller
{
    public function editar()
    {
        $idDetalhe = request()->input('id_detalhe');
        $novoValor = request()->input('valor');

        if (!$idDetalhe || $novoValor === null || $novoValor < 0) {
            return response()->json(['success' => false, 'error' => 'Dados inválidos.']);
        }

        try {
            DB::beginTransaction();

            $detalhe = DetalhePagamento::find($idDetalhe);
            if (!$detalhe) {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'Baixa não encontrada.']);
            }

            $detalhe->VALOR_PAGO_PEDIDO = $novoValor;
            $detalhe->save();

            // Recalcula o total do registro
            $total = DetalhePagamento::where('ID_REGISTRO', $detalhe->ID_REGISTRO)->sum('VALOR_PAGO_PEDIDO');
            DB::table('REGISTRO_PAGAMENTO')
                ->where('ID_REGISTRO', $detalhe->ID_REGISTRO)
                ->update(['VALOR_REGISTRO' => $total]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Erro ao editar baixa: ' . $e->getMessage()]);
        }
    }

    public function estornar()
    {
        $idDetalhe = request()->input('id_detalhe');

        if (!$idDetalhe) {
            return response()->json(['success' => false, 'error' => 'ID não informado.']);
        }

        try {
            DB::beginTransaction();

            $detalhe = DetalhePagamento::find($idDetalhe);
            if (!$detalhe) {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'Baixa não encontrada.']);
            }

            $idRegistro = $detalhe->ID_REGISTRO;
            $detalhe->delete();

            // Verifica se o registro ficou sem detalhes
            $count = DetalhePagamento::where('ID_REGISTRO', $idRegistro)->count();
            if ($count == 0) {
                DB::table('REGISTRO_PAGAMENTO')->where('ID_REGISTRO', $idRegistro)->delete();
            } else {
                $total = DetalhePagamento::where('ID_REGISTRO', $idRegistro)->sum('VALOR_PAGO_PEDIDO');
                DB::table('REGISTRO_PAGAMENTO')
                    ->where('ID_REGISTRO', $idRegistro)
                    ->update(['VALOR_REGISTRO' => $total]);
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Erro ao estornar baixa: ' . $e->getMessage()]);
        }
    }
}
