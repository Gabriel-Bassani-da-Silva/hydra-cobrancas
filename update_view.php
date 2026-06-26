<?php

require __DIR__ . '/framework/vendor/autoload.php';
$app = require_once __DIR__ . '/framework/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

DB::unprepared("
    CREATE OR REPLACE VIEW `vw_divergencias_pagamento` AS 
    SELECT 
        `p`.`ID_PEDIDO` AS `ID_PEDIDO`,
        `p`.`NUM_PEDIDO` AS `NUM_PEDIDO`,
        `p`.`TOTAL_PEDIDO` AS `TOTAL_PEDIDO`,
        `p`.`VALOR_PAGO_BLING` AS `VALOR_PAGO_BLING`,
        COALESCE(`dp`.`PAGO_LOCAL`, 0) AS `VALOR_PAGO_LOCAL`,
        `c_ext`.`NOME_CONTATO` AS `NOME_CLIENTE`,
        `rp`.`DATA_REGISTRO` AS `DATA_REGISTRO`,
        `colab`.`NOME_COLABORADOR` AS `NOME_COLABORADOR` 
    FROM `PEDIDO` `p` 
    JOIN (
        SELECT 
            `ID_PEDIDO`,
            SUM(`VALOR_PAGO_PEDIDO`) AS `PAGO_LOCAL`,
            MAX(`ID_REGISTRO`) AS `ULTIMO_REGISTRO` 
        FROM `DETALHE_PAGAMENTO` 
        GROUP BY `ID_PEDIDO`
    ) `dp` ON `dp`.`ID_PEDIDO` = `p`.`ID_PEDIDO`
    LEFT JOIN `CONTATO_EXTERNO` `c_ext` ON `c_ext`.`ID_CONTATO_BLING` = `p`.`ID_CLIENTE`
    LEFT JOIN `REGISTRO_PAGAMENTO` `rp` ON `rp`.`ID_REGISTRO` = `dp`.`ULTIMO_REGISTRO`
    LEFT JOIN `COLABORADOR` `colab` ON `colab`.`ID_COLABORADOR` = `rp`.`ID_COLABORADOR` 
    WHERE `p`.`VALOR_PAGO_BLING` <> `dp`.`PAGO_LOCAL` 
    AND `p`.`VALOR_PAGO_BLING` < `p`.`TOTAL_PEDIDO`;
");

echo "View vw_divergencias_pagamento atualizada com sucesso!\n";
