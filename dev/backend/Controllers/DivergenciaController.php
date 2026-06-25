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
}
