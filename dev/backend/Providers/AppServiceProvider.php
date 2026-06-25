<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $pedidoModel = new \App\Repositories\PedidoRepository();
            $totais = $pedidoModel->getTotalEmAberto();
            $ultimaSinc = $pedidoModel->getUltimaSincronizacao();
            
            $blingService = new \App\Integrations\Bling\BlingService();
            $blingConnected = $blingService->isConnected();

            $view->with('totais', $totais)
                 ->with('ultimaSinc', $ultimaSinc)
                 ->with('blingConnected', $blingConnected);
        });
    }
}
