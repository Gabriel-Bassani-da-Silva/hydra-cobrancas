<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingConfigController extends Controller
{
    public function edit()
    {
        $config = DB::table('CONFIGURACOES_RANKING')->first();
        if (!$config) {
            DB::table('CONFIGURACOES_RANKING')->insert([
                'DATA_INICIO_DIARIO' => date('Y-m-d'),
                'PONTOS_PEDIDO_PAGO' => 10
            ]);
            $config = DB::table('CONFIGURACOES_RANKING')->first();
        }

        return view('pages.regras_ranking', ['config' => $config]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'data_inicio_diario' => 'required|date',
            'pontos_pedido_pago' => 'required|integer|min:0'
        ]);

        DB::table('CONFIGURACOES_RANKING')->update([
            'DATA_INICIO_DIARIO' => $request->input('data_inicio_diario'),
            'PONTOS_PEDIDO_PAGO' => $request->input('pontos_pedido_pago')
        ]);

        return redirect()->route('regras-ranking.edit')->with('success_message', 'Regras do ranking atualizadas com sucesso!');
    }
}
