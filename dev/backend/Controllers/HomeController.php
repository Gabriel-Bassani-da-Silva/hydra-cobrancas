<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Cobranca;

class HomeController extends Controller
{
    public function index()
    {
        $ranking = \Illuminate\Support\Facades\DB::table('REGISTRO_PAGAMENTO as rp')
            ->join('COLABORADOR as c', 'rp.ID_COLABORADOR', '=', 'c.ID_COLABORADOR')
            ->select('c.NOME_COLABORADOR', \Illuminate\Support\Facades\DB::raw('SUM(rp.VALOR_REGISTRO) as TOTAL_RECEBIDO'), \Illuminate\Support\Facades\DB::raw('COUNT(rp.ID_REGISTRO) as QTD_BAIXAS'))
            ->groupBy('c.ID_COLABORADOR', 'c.NOME_COLABORADOR')
            ->orderByDesc('TOTAL_RECEBIDO')
            ->get();

        return view('dashboard', ['ranking' => $ranking]);
    }
}
