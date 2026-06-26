<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Cobranca;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $ranking = \Illuminate\Support\Facades\DB::table('REGISTRO_PAGAMENTO as rp')
            ->join('COLABORADOR as c', 'rp.ID_COLABORADOR', '=', 'c.ID_COLABORADOR')
            ->select('c.NOME_COLABORADOR', \Illuminate\Support\Facades\DB::raw('SUM(rp.VALOR_REGISTRO) as TOTAL_RECEBIDO'), \Illuminate\Support\Facades\DB::raw('COUNT(rp.ID_REGISTRO) as QTD_BAIXAS'))
            ->groupBy('c.ID_COLABORADOR', 'c.NOME_COLABORADOR')
            ->orderByDesc('TOTAL_RECEBIDO')
            ->get();

        $dataFiltro = $request->input('data_vencimento', date('Y-m-d'));

        $rankingDiario = \Illuminate\Support\Facades\DB::table('REGISTRO_PAGAMENTO as rp')
            ->join('COLABORADOR as c', 'rp.ID_COLABORADOR', '=', 'c.ID_COLABORADOR')
            ->join('DETALHE_PAGAMENTO as dp', 'dp.ID_REGISTRO', '=', 'rp.ID_REGISTRO')
            ->join('PEDIDO as p', 'p.ID_PEDIDO', '=', 'dp.ID_PEDIDO')
            ->where('p.DATA_VENCIMENTO', '>=', $dataFiltro)
            ->select('c.NOME_COLABORADOR', \Illuminate\Support\Facades\DB::raw('SUM(dp.VALOR_PAGO_PEDIDO) as TOTAL_RECEBIDO'), \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT rp.ID_REGISTRO) as QTD_BAIXAS'))
            ->groupBy('c.ID_COLABORADOR', 'c.NOME_COLABORADOR')
            ->orderByDesc('TOTAL_RECEBIDO')
            ->get();

        return view('dashboard', [
            'ranking' => $ranking,
            'rankingDiario' => $rankingDiario,
            'dataFiltro' => $dataFiltro
        ]);
    }
}
