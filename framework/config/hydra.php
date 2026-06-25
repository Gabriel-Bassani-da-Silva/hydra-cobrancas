<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configurações de Negócio — Cobranças Hydra
    |--------------------------------------------------------------------------
    |
    | Centraliza valores que antes estavam "mágicos" (hardcoded) espalhados
    | pelo código, como IDs de formas de pagamento do Bling e a data de corte
    | usada na carga completa (full sync). Assim, mudanças passam a ser feitas
    | em um único lugar e podem ser sobrescritas por variáveis de ambiente.
    |
    */

    'bling' => [

        /*
        | IDs das formas de pagamento no Bling que recebem tratamento especial.
        | - antecipado: pedidos antecipados (não entram na inadimplência padrão)
        | - cheque: pedidos pagos em cheque
        */
        'formas_pagamento' => [
            'antecipado' => (int) env('BLING_FORMA_PAGAMENTO_ANTECIPADO', 7179738),
            'cheque'     => (int) env('BLING_FORMA_PAGAMENTO_CHEQUE', 7179734),
        ],

        /*
        | Data inicial absoluta usada na carga completa (full sync) de contas
        | a receber quando não há última sincronização registrada.
        */
        'data_corte_full_sync' => env('BLING_DATA_CORTE_FULL_SYNC', '2025-01-01'),

        /*
        | Intervalo, em milissegundos, entre requisições à API do Bling para
        | respeitar o rate limit do ERP.
        */
        'api_delay_ms' => (int) env('BLING_API_DELAY_MS', 200),

        /*
        | Tamanho máximo (em dias) de cada bloco de paginação por data.
        */
        'max_intervalo_dias' => (int) env('BLING_MAX_INTERVALO_DIAS', 365),
    ],

];
