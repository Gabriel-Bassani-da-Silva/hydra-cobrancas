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
        // Aqui teremos a lógica migrada do HomeController.php antigo.
        // Temporariamente, vou retornar a view dashboard para manter a tela inicial funcionando.
        return view('dashboard');
    }
}
