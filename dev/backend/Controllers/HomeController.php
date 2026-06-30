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
        try {
            $ranking = \Illuminate\Support\Facades\DB::table('vw_ranking_total')
                ->orderByDesc('PONTOS_TOTAIS')
                ->orderByDesc('TOTAL_RECEBIDO')
                ->get();

            $rankingDiario = \Illuminate\Support\Facades\DB::table('vw_ranking_diario')
                ->orderByDesc('TOTAL_RECEBIDO')
                ->get();

            // Data início diário
            $config = \Illuminate\Support\Facades\DB::table('CONFIGURACOES_RANKING')->first();
            $dataFiltro = $config ? $config->DATA_INICIO_DIARIO : date('Y-m-d');
        } catch (\Exception $e) {
            $ranking = collect();
            $rankingDiario = collect();
            $dataFiltro = date('Y-m-d');
        }

        return view('dashboard', [
            'ranking' => $ranking,
            'rankingDiario' => $rankingDiario,
            'dataFiltro' => $dataFiltro
        ]);
    }
}
