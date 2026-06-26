<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE VIEW `vw_clientes_inadimplentes` AS 
            SELECT 
                `p`.`ID_CLIENTE` AS `ID_CONTATO_BLING`,
                `ce`.`NOME_CONTATO` AS `NOME_CONTATO`,
                COUNT(`p`.`ID_PEDIDO`) AS `QTD_CONTAS`,
                SUM(`p`.`TOTAL_PEDIDO` - `p`.`VALOR_PAGO_BLING`) AS `TOTAL_VALOR`,
                MIN(`p`.`DATA_VENCIMENTO`) AS `VENCIMENTO_MAIS_ANTIGO` 
            FROM `PEDIDO` `p` 
            JOIN `CLIENTE` `c` ON `c`.`ID_CONTATO_BLING` = `p`.`ID_CLIENTE`
            JOIN `CONTATO_EXTERNO` `ce` ON `ce`.`ID_CONTATO_BLING` = `p`.`ID_CLIENTE` 
            WHERE `p`.`SITUACAO_PEDIDO` IN (1, 3) 
            AND `p`.`DATA_VENCIMENTO` < CURDATE() 
            AND `p`.`EXIBIR` = 1 
            AND `c`.`EXIBIR` = 1 
            GROUP BY `p`.`ID_CLIENTE`, `ce`.`NOME_CONTATO`;
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW `vw_cobranca_clientes` AS 
            SELECT 
                `vcc`.`ID_COBRANCA` AS `ID_COBRANCA`,
                `vcc`.`ID_CONTATO_BLING` AS `ID_CONTATO_BLING`,
                `ce`.`NOME_CONTATO` AS `NOME_CONTATO`,
                `ce`.`NUMERO_DOCUMENTO` AS `NUMERO_DOCUMENTO` 
            FROM `VINCULO_COBRANCA_CLIENTE` `vcc` 
            JOIN `CONTATO_EXTERNO` `ce` ON `ce`.`ID_CONTATO_BLING` = `vcc`.`ID_CONTATO_BLING`;
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW `vw_cobranca_pedidos_detalhes` AS 
            SELECT 
                `vcp`.`ID_COBRANCA` AS `ID_COBRANCA`,
                `p`.`ID_PEDIDO` AS `ID_PEDIDO`,
                `p`.`NUM_PEDIDO` AS `NUM_PEDIDO`,
                `p`.`TOTAL_PEDIDO` AS `TOTAL_PEDIDO`,
                `p`.`VALOR_PAGO_BLING` AS `VALOR_PAGO_BLING`,
                `p`.`DATA_VENCIMENTO` AS `DATA_VENCIMENTO`,
                `p`.`SITUACAO_PEDIDO` AS `SITUACAO_PEDIDO`,
                `ce`.`NOME_CONTATO` AS `NOME_CLIENTE`,
                `ce`.`NUMERO_DOCUMENTO` AS `CPF_CNPJ`,
                `c`.`ID_CONTATO_BLING` AS `ID_CLIENTE` 
            FROM `VINCULO_COBRANCA_PEDIDO` `vcp` 
            JOIN `PEDIDO` `p` ON `p`.`ID_PEDIDO` = `vcp`.`ID_PEDIDO`
            LEFT JOIN `CLIENTE` `c` ON `c`.`ID_CONTATO_BLING` = `p`.`ID_CLIENTE`
            LEFT JOIN `CONTATO_EXTERNO` `ce` ON `ce`.`ID_CONTATO_BLING` = `c`.`ID_CONTATO_BLING`;
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW `vw_cobrancas_ativas` AS 
            SELECT 
                'clientes' AS `TIPO`,
                `vcc`.`ID_CONTATO_BLING` AS `ID`,
                `c`.`ID_COBRANCA` AS `ID_COBRANCA`,
                `col`.`NOME_COLABORADOR` AS `NOME_COLABORADOR`,
                `c`.`ID_COLABORADOR` AS `ID_COLABORADOR` 
            FROM `COBRANCA` `c` 
            JOIN `VINCULO_COBRANCA_CLIENTE` `vcc` ON `vcc`.`ID_COBRANCA` = `c`.`ID_COBRANCA`
            JOIN `COLABORADOR` `col` ON `col`.`ID_COLABORADOR` = `c`.`ID_COLABORADOR` 
            WHERE `c`.`DATA_FIM` IS NULL 
            UNION ALL 
            SELECT 
                'financeiros' AS `TIPO`,
                `c`.`ID_CONTATO` AS `ID`,
                `c`.`ID_COBRANCA` AS `ID_COBRANCA`,
                `col`.`NOME_COLABORADOR` AS `NOME_COLABORADOR`,
                `c`.`ID_COLABORADOR` AS `ID_COLABORADOR` 
            FROM `COBRANCA` `c` 
            JOIN `COLABORADOR` `col` ON `col`.`ID_COLABORADOR` = `c`.`ID_COLABORADOR` 
            WHERE `c`.`DATA_FIM` IS NULL 
            AND `c`.`ID_CONTATO` IS NOT NULL 
            UNION ALL 
            SELECT 
                'representantes' AS `TIPO`,
                `c`.`ID_REPRESENTANTE` AS `ID`,
                `c`.`ID_COBRANCA` AS `ID_COBRANCA`,
                `col`.`NOME_COLABORADOR` AS `NOME_COLABORADOR`,
                `c`.`ID_COLABORADOR` AS `ID_COLABORADOR` 
            FROM `COBRANCA` `c` 
            JOIN `COLABORADOR` `col` ON `col`.`ID_COLABORADOR` = `c`.`ID_COLABORADOR` 
            WHERE `c`.`DATA_FIM` IS NULL 
            AND `c`.`ID_REPRESENTANTE` IS NOT NULL;
        ");

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

        DB::unprepared("
            CREATE OR REPLACE VIEW `vw_minhas_cobrancas_resumo` AS 
            SELECT 
                `c`.`ID_COBRANCA` AS `ID_COBRANCA`,
                `c`.`DATA_INICIO` AS `DATA_INICIO`,
                `c`.`STATUS_ATENDIMENTO` AS `STATUS_ATENDIMENTO`,
                `c`.`DATA_FIM` AS `DATA_FIM`,
                `c`.`ID_CONTATO` AS `ID_CONTATO`,
                `c`.`ID_REPRESENTANTE` AS `ID_REPRESENTANTE`,
                `c`.`ID_COLABORADOR` AS `ID_COLABORADOR`,
                `cf`.`NOME_CONTATO` AS `NOME_FINANCEIRO`,
                `ce_rep`.`NOME_CONTATO` AS `NOME_REPRESENTANTE`,
                (
                    SELECT SUM(`p`.`TOTAL_PEDIDO` - `p`.`VALOR_PAGO_BLING`) 
                    FROM `VINCULO_COBRANCA_PEDIDO` `vcp` 
                    JOIN `PEDIDO` `p` ON `p`.`ID_PEDIDO` = `vcp`.`ID_PEDIDO` 
                    WHERE `vcp`.`ID_COBRANCA` = `c`.`ID_COBRANCA`
                ) AS `TOTAL_DIVIDA`,
                (
                    SELECT COUNT(0) 
                    FROM `VINCULO_COBRANCA_PEDIDO` 
                    WHERE `ID_COBRANCA` = `c`.`ID_COBRANCA`
                ) AS `QTD_PEDIDOS`,
                (
                    SELECT COUNT(0) 
                    FROM `VINCULO_CONTATO_REPRESENTANTE` 
                    WHERE `ID_CONTATO` = `c`.`ID_CONTATO`
                ) > 0 AS `IS_CF_REPRESENTANTE` 
            FROM `COBRANCA` `c` 
            LEFT JOIN `CONTATO_FINANCEIRO` `cf` ON `cf`.`ID_CONTATO` = `c`.`ID_CONTATO`
            LEFT JOIN `REPRESENTANTE` `r` ON `r`.`ID_CONTATO_BLING` = `c`.`ID_REPRESENTANTE`
            LEFT JOIN `CONTATO_EXTERNO` `ce_rep` ON `ce_rep`.`ID_CONTATO_BLING` = `r`.`ID_CONTATO_BLING`;
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW `vw_pedido_efetivo` AS 
            SELECT 
                `p`.`ID_PEDIDO` AS `ID_PEDIDO`,
                `p`.`NUM_PEDIDO` AS `NUM_PEDIDO`,
                `p`.`TOTAL_PEDIDO` AS `TOTAL_PEDIDO`,
                `p`.`DATA_VENCIMENTO` AS `DATA_VENCIMENTO`,
                `p`.`VALOR_PAGO_BLING` AS `VALOR_PAGO_BLING`,
                `p`.`SITUACAO_PEDIDO` AS `SITUACAO_PEDIDO`,
                `p`.`ID_REPRESENTANTE` AS `ID_REPRESENTANTE`,
                `p`.`ID_CLIENTE` AS `ID_CLIENTE`,
                `p`.`ID_FORMA_PAGAMENTO` AS `ID_FORMA_PAGAMENTO`,
                `p`.`EXIBIR` AS `EXIBIR`,
                COALESCE(`dp`.`PAGO_LOCAL`, 0) AS `PAGO_LOCAL`,
                GREATEST(`p`.`VALOR_PAGO_BLING`, COALESCE(`dp`.`PAGO_LOCAL`, 0)) AS `VALOR_PAGO_EFETIVO`,
                CASE 
                    WHEN `p`.`SITUACAO_PEDIDO` IN (4, 5) THEN `p`.`SITUACAO_PEDIDO` 
                    WHEN GREATEST(`p`.`VALOR_PAGO_BLING`, COALESCE(`dp`.`PAGO_LOCAL`, 0)) >= `p`.`TOTAL_PEDIDO` THEN 2 
                    WHEN GREATEST(`p`.`VALOR_PAGO_BLING`, COALESCE(`dp`.`PAGO_LOCAL`, 0)) > 0 THEN 3 
                    ELSE 1 
                END AS `SITUACAO_EFETIVA` 
            FROM `PEDIDO` `p` 
            LEFT JOIN (
                SELECT 
                    `ID_PEDIDO`,
                    SUM(`VALOR_PAGO_PEDIDO`) AS `PAGO_LOCAL` 
                FROM `DETALHE_PAGAMENTO` 
                GROUP BY `ID_PEDIDO`
            ) `dp` ON `dp`.`ID_PEDIDO` = `p`.`ID_PEDIDO`;
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW `vw_representantes_inadimplentes` AS 
            SELECT 
                `r`.`ID_CONTATO_BLING` AS `ID_CONTATO_BLING`,
                `ce`.`NOME_CONTATO` AS `NOME_REPRESENTANTE`,
                COUNT(`p`.`ID_PEDIDO`) AS `QTD_CONTAS`,
                SUM(`p`.`TOTAL_PEDIDO` - `p`.`VALOR_PAGO_BLING`) AS `TOTAL_VALOR`,
                MIN(`p`.`DATA_VENCIMENTO`) AS `VENCIMENTO_MAIS_ANTIGO`,
                COUNT(DISTINCT `p`.`ID_CLIENTE`) AS `QTD_CLIENTES` 
            FROM `REPRESENTANTE` `r` 
            JOIN `CONTATO_EXTERNO` `ce` ON `ce`.`ID_CONTATO_BLING` = `r`.`ID_CONTATO_BLING`
            JOIN `PEDIDO` `p` ON `p`.`ID_REPRESENTANTE` = `r`.`ID_CONTATO_BLING` 
            WHERE `p`.`SITUACAO_PEDIDO` IN (1, 3) 
            AND `p`.`DATA_VENCIMENTO` < CURDATE() 
            AND `p`.`EXIBIR` = 1 
            AND `r`.`EXIBIR` = 1 
            GROUP BY `r`.`ID_CONTATO_BLING`, `ce`.`NOME_CONTATO`;
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW `vw_resumo_contas_cliente` AS 
            SELECT 
                `p`.`ID_CLIENTE` AS `ID_CONTATO_BLING`,
                `ce`.`NOME_CONTATO` AS `NOME_CONTATO`,
                COUNT(`p`.`ID_PEDIDO`) AS `QTD_CONTAS`,
                SUM(`p`.`TOTAL_PEDIDO` - `p`.`VALOR_PAGO_BLING`) AS `TOTAL_VALOR`,
                MIN(`p`.`DATA_VENCIMENTO`) AS `VENCIMENTO_MAIS_ANTIGO` 
            FROM `PEDIDO` `p` 
            JOIN `CLIENTE` `c` ON `c`.`ID_CONTATO_BLING` = `p`.`ID_CLIENTE`
            JOIN `CONTATO_EXTERNO` `ce` ON `ce`.`ID_CONTATO_BLING` = `p`.`ID_CLIENTE` 
            WHERE `p`.`SITUACAO_PEDIDO` IN (1, 3) 
            AND `p`.`EXIBIR` = 1 
            AND `c`.`EXIBIR` = 1 
            GROUP BY `p`.`ID_CLIENTE`, `ce`.`NOME_CONTATO`;
        ");

        DB::unprepared("
            CREATE OR REPLACE VIEW `vw_telefones_detalhados` AS 
            SELECT 
                `ct`.`ID_CONTATO_BLING` AS `ID_CONTATO_BLING`,
                `t`.`ID_TEL` AS `ID_TEL`,
                `t`.`NUM_TEL` AS `NUM_TEL`,
                `t`.`CONFIRMADO` AS `CONFIRMADO`,
                `t`.`ORIGEM` AS `ORIGEM` 
            FROM `CONTATO_TEL` `ct` 
            JOIN `TEL` `t` ON `t`.`ID_TEL` = `ct`.`ID_TEL`;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP VIEW IF EXISTS `vw_telefones_detalhados`");
        DB::unprepared("DROP VIEW IF EXISTS `vw_resumo_contas_cliente`");
        DB::unprepared("DROP VIEW IF EXISTS `vw_representantes_inadimplentes`");
        DB::unprepared("DROP VIEW IF EXISTS `vw_pedido_efetivo`");
        DB::unprepared("DROP VIEW IF EXISTS `vw_minhas_cobrancas_resumo`");
        DB::unprepared("DROP VIEW IF EXISTS `vw_divergencias_pagamento`");
        DB::unprepared("DROP VIEW IF EXISTS `vw_cobrancas_ativas`");
        DB::unprepared("DROP VIEW IF EXISTS `vw_cobranca_pedidos_detalhes`");
        DB::unprepared("DROP VIEW IF EXISTS `vw_cobranca_clientes`");
        DB::unprepared("DROP VIEW IF EXISTS `vw_clientes_inadimplentes`");
    }
};
