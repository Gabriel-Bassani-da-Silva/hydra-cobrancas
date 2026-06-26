<?php
namespace App\Controllers;

use App\Repositories\PedidoRepository;



class DivergenciaController extends Controller {
    public function index() {
        $pedidoModel = new PedidoRepository();
        $divergencias = $pedidoModel->getDivergencias();

        return view('pages.divergencia_bling', [
            'divergencias' => $divergencias
        ]);
    }

    public function corrigirBaixa() {
        $idPedido = (int)request()->input('id_pedido');
        $novoValorStr = request()->input('novo_valor');
        
        if ($idPedido <= 0 || $novoValorStr === null || $novoValorStr === '') {
            return response()->json(['success' => false, 'error' => 'Dados inválidos.']);
        }
        
        $novoValor = (float)str_replace(',', '.', $novoValorStr);
        if ($novoValor < 0) $novoValor = 0;
        
        $pedidoModel = new PedidoRepository();
        
        // Obter valor local atual
        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(VALOR_PAGO_PEDIDO), 0) FROM DETALHE_PAGAMENTO WHERE ID_PEDIDO = ?");
        $stmt->execute([$idPedido]);
        $atualPagoLocal = (float)$stmt->fetchColumn();
        
        $diferenca = $novoValor - $atualPagoLocal;
        
        if (abs($diferenca) > 0.001) {
            $idColaborador = auth()->user()->ID_COLABORADOR ?? 0;
            $sucesso = $pedidoModel->registrarBaixaManual([
                ['id' => $idPedido, 'valor' => $diferenca]
            ], $idColaborador);
            
            if ($sucesso) {
                return response()->json(['success' => $sucesso]);
            } else {
                return response()->json(['success' => false, 'error' => 'Falha ao registrar correção no banco de dados.']);
            }
        }
        
        return response()->json(['success' => true, 'msg' => 'Nenhuma alteração necessária.']);
    }

    public function estornarBaixa()
    {
        $idPedido = request()->input('id_pedido');

        if (!$idPedido) {
            return response()->json(['success' => false, 'error' => 'ID não informado.']);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $detalhes = \App\Models\DetalhePagamento::where('ID_PEDIDO', $idPedido)->get();
            $registrosAfetados = $detalhes->pluck('ID_REGISTRO')->unique();

            \App\Models\DetalhePagamento::where('ID_PEDIDO', $idPedido)->delete();

            foreach ($registrosAfetados as $idRegistro) {
                $count = \App\Models\DetalhePagamento::where('ID_REGISTRO', $idRegistro)->count();
                if ($count == 0) {
                    \Illuminate\Support\Facades\DB::table('REGISTRO_PAGAMENTO')->where('ID_REGISTRO', $idRegistro)->delete();
                } else {
                    $total = \App\Models\DetalhePagamento::where('ID_REGISTRO', $idRegistro)->sum('VALOR_PAGO_PEDIDO');
                    \Illuminate\Support\Facades\DB::table('REGISTRO_PAGAMENTO')
                        ->where('ID_REGISTRO', $idRegistro)
                        ->update(['VALOR_REGISTRO' => $total]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Erro ao estornar baixa: ' . $e->getMessage()]);
        }
    }
}
