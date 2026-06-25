<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabelas Base (Sem FKs)
        Schema::create('bling_config', function (Blueprint $table) {
            $table->integer('ID', true);
            $table->string('CLIENT_ID')->nullable();
            $table->string('CLIENT_SECRET')->nullable();
            $table->string('REDIRECT_URI')->nullable();
            $table->text('ACCESS_TOKEN')->nullable();
            $table->string('REFRESH_TOKEN')->nullable();
            $table->integer('EXPIRES_AT')->nullable();
            $table->dateTime('ULTIMA_SINC_CONTAS')->nullable();
            $table->date('EXIBIR_ATE')->nullable();
            $table->date('EXIBIR_A_PARTIR_DE')->nullable();
            // Observação: as colunas acima já são criadas aqui na migration,
            // tornando desnecessários os ALTER TABLE de runtime que existiam
            // anteriormente no PedidoRepository.
        });

        Schema::create('colaborador', function (Blueprint $table) {
            $table->integer('ID_COLABORADOR', true);
            $table->string('NOME_COLABORADOR', 45)->unique('UQ_NOME_COLABORADOR');
            $table->string('SENHA');
        });

        Schema::create('configuracao', function (Blueprint $table) {
            $table->integer('ID_CONFIGURACAO', true);
            $table->date('ULTIMA_DATA_UPDATE')->useCurrent();
            $table->date('ULTIMA_DATA_INSERT')->useCurrent();
        });

        Schema::create('contato_externo', function (Blueprint $table) {
            $table->bigInteger('ID_CONTATO_BLING')->primary();
            $table->string('NOME_CONTATO', 120);
            $table->string('NUMERO_DOCUMENTO', 20)->nullable();
        });

        Schema::create('forma_pagamento', function (Blueprint $table) {
            $table->integer('ID_FORMA_PAGAMENTO', true);
            $table->tinyInteger('COBRANCA_PADRAO')->default(1);
        });

        Schema::create('tel', function (Blueprint $table) {
            $table->integer('ID_TEL', true);
            $table->string('NUM_TEL', 45)->unique('UQ_NUM_TEL');
            $table->tinyInteger('CONFIRMADO')->default(0);
            $table->enum('ORIGEM', ['bling', 'manual'])->default('bling');
        });

        // 2. Tabelas Nível 1 (Dependem apenas da Base)
        Schema::create('cliente', function (Blueprint $table) {
            $table->bigInteger('ID_CONTATO_BLING')->primary();
            $table->tinyInteger('EXIBIR')->default(1);
            $table->tinyInteger('PEDRAS')->default(0);

            $table->foreign('ID_CONTATO_BLING', 'FK_CLIENTE_CONTATO_EXTERNO')->references('ID_CONTATO_BLING')->on('contato_externo');
        });

        Schema::create('representante', function (Blueprint $table) {
            $table->bigInteger('ID_CONTATO_BLING')->primary();
            $table->bigInteger('ID_VENDEDOR')->unique('UQ_ID_VENDEDOR');
            $table->string('NOME_GRUPO_WHATSAPP', 45)->nullable();
            $table->tinyInteger('EXIBIR')->default(1);

            $table->foreign('ID_CONTATO_BLING', 'FK_REPRESENTANTE_CONTATO_EXT')->references('ID_CONTATO_BLING')->on('contato_externo');
        });

        Schema::create('contato_financeiro', function (Blueprint $table) {
            $table->integer('ID_CONTATO', true);
            $table->string('NOME_CONTATO', 45);
            $table->integer('ID_TEL');

            $table->foreign('ID_TEL', 'FK_CONTATO_FINANCEIRO_TEL')->references('ID_TEL')->on('tel');
        });

        Schema::create('contato_tel', function (Blueprint $table) {
            $table->bigInteger('ID_CONTATO_BLING');
            $table->integer('ID_TEL');

            $table->primary(['ID_CONTATO_BLING', 'ID_TEL']);
            $table->foreign('ID_CONTATO_BLING', 'FK_CONTATO_TEL_CONTATO')->references('ID_CONTATO_BLING')->on('contato_externo');
            $table->foreign('ID_TEL', 'FK_CONTATO_TEL_TEL')->references('ID_TEL')->on('tel');
        });

        // 3. Tabelas Nível 2
        Schema::create('pedido', function (Blueprint $table) {
            $table->bigInteger('ID_PEDIDO')->primary();
            $table->string('NUM_PEDIDO', 50);
            $table->decimal('TOTAL_PEDIDO', 10, 2);
            $table->date('DATA_VENCIMENTO');
            $table->decimal('VALOR_PAGO_BLING', 10, 2)->default(0.00);
            $table->tinyInteger('SITUACAO_PEDIDO')->default(1);
            $table->bigInteger('ID_REPRESENTANTE')->nullable();
            $table->bigInteger('ID_CLIENTE');
            $table->integer('ID_FORMA_PAGAMENTO')->default(1);
            $table->tinyInteger('EXIBIR')->default(1);

            $table->foreign('ID_CLIENTE', 'FK_PEDIDO_CLIENTE')->references('ID_CONTATO_BLING')->on('cliente');
            $table->foreign('ID_FORMA_PAGAMENTO', 'FK_PEDIDO_FORMA_PAG')->references('ID_FORMA_PAGAMENTO')->on('forma_pagamento');
            $table->foreign('ID_REPRESENTANTE', 'FK_PEDIDO_REPRESENTANTE')->references('ID_CONTATO_BLING')->on('representante');
        });

        Schema::create('cobranca', function (Blueprint $table) {
            $table->integer('ID_COBRANCA', true);
            $table->date('DATA_INICIO')->useCurrent();
            $table->date('DATA_FIM')->nullable();
            $table->string('STATUS_ATENDIMENTO', 45)->nullable();
            $table->integer('ID_COLABORADOR');
            $table->integer('ID_CONTATO')->nullable();
            $table->bigInteger('ID_REPRESENTANTE')->nullable();

            $table->foreign('ID_CONTATO', 'FK_COBRANCA_CONTATO_FINANCEIRO')->references('ID_CONTATO')->on('contato_financeiro');
            $table->foreign('ID_REPRESENTANTE', 'FK_COBRANCA_REPRESENTANTE')->references('ID_CONTATO_BLING')->on('representante');
        });

        // 4. Tabelas Nível 3
        Schema::create('tentativa_cobranca', function (Blueprint $table) {
            $table->integer('ID_TENTATIVA', true);
            $table->string('METODO', 45);
            $table->date('DATA_TENTATIVA')->useCurrent();
            $table->integer('ID_COBRANCA');
            $table->integer('ID_CONTATO')->nullable();
            $table->integer('ID_TEL');

            $table->foreign('ID_COBRANCA', 'FK_TENTATIVA_COBRANCA_COBRANCA')->references('ID_COBRANCA')->on('cobranca');
            $table->foreign('ID_CONTATO', 'FK_TENTATIVA_COBRANCA_CONTATO_FINANCEIRO')->references('ID_CONTATO')->on('contato_financeiro');
            $table->foreign('ID_TEL', 'FK_TENTATIVA_COBRANCA_TEL')->references('ID_TEL')->on('tel');
        });

        Schema::create('registro_pagamento', function (Blueprint $table) {
            $table->integer('ID_REGISTRO', true);
            $table->decimal('VALOR_REGISTRO', 10, 2);
            $table->date('DATA_REGISTRO')->useCurrent();
            $table->integer('ID_TENTATIVA')->nullable();
            $table->integer('ID_COLABORADOR');

            $table->foreign('ID_COLABORADOR', 'FK_REGISTRO_PAG_COLAB')->references('ID_COLABORADOR')->on('colaborador');
            $table->foreign('ID_TENTATIVA', 'FK_REGISTRO_PAG_TENTATIVA')->references('ID_TENTATIVA')->on('tentativa_cobranca');
        });

        Schema::create('vinculo_cobranca_cliente', function (Blueprint $table) {
            $table->bigInteger('ID_COBRANCA');
            $table->bigInteger('ID_CONTATO_BLING');

            $table->primary(['ID_COBRANCA', 'ID_CONTATO_BLING']);
            $table->foreign('ID_CONTATO_BLING', 'FK_VINCULO_COBR_CLI_CLIENTE')->references('ID_CONTATO_BLING')->on('cliente');
        });

        Schema::create('vinculo_cobranca_pedido', function (Blueprint $table) {
            $table->bigInteger('ID_COBRANCA');
            $table->bigInteger('ID_PEDIDO');

            $table->primary(['ID_COBRANCA', 'ID_PEDIDO']);
            $table->foreign('ID_PEDIDO', 'FK_VINCULO_COBR_PED_PEDIDO')->references('ID_PEDIDO')->on('pedido');
        });

        Schema::create('vinculo_contato_cliente', function (Blueprint $table) {
            $table->integer('ID_CONTATO');
            $table->bigInteger('ID_CLIENTE');

            $table->primary(['ID_CONTATO', 'ID_CLIENTE']);
            $table->foreign('ID_CLIENTE', 'FK_VINCULO_CONT_CLI_CLIENTE')->references('ID_CONTATO_BLING')->on('cliente');
            $table->foreign('ID_CONTATO', 'FK_VINCULO_CONT_CLI_CONTATO')->references('ID_CONTATO')->on('contato_financeiro');
        });

        Schema::create('vinculo_contato_representante', function (Blueprint $table) {
            $table->integer('ID_CONTATO');
            $table->bigInteger('ID_REPRESENTANTE');

            $table->primary(['ID_CONTATO', 'ID_REPRESENTANTE']);
            $table->foreign('ID_CONTATO', 'FK_VINCULO_CONT_REP_CONTATO')->references('ID_CONTATO')->on('contato_financeiro');
            $table->foreign('ID_REPRESENTANTE', 'FK_VINCULO_CONT_REP_REPRESENTANTE')->references('ID_CONTATO_BLING')->on('representante');
        });

        // 5. Tabelas Nível 4
        Schema::create('detalhe_pagamento', function (Blueprint $table) {
            $table->integer('ID_DETALHE', true);
            $table->decimal('VALOR_PAGO_PEDIDO', 10, 2);
            $table->date('DATA_DETALHE')->useCurrent();
            $table->bigInteger('ID_PEDIDO');
            $table->integer('ID_REGISTRO')->nullable();

            $table->foreign('ID_PEDIDO', 'FK_DETALHE_PAG_PEDIDO')->references('ID_PEDIDO')->on('pedido');
            $table->foreign('ID_REGISTRO', 'FK_DETALHE_PAG_REGISTRO')->references('ID_REGISTRO')->on('registro_pagamento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalhe_pagamento');
        Schema::dropIfExists('vinculo_contato_representante');
        Schema::dropIfExists('vinculo_contato_cliente');
        Schema::dropIfExists('vinculo_cobranca_pedido');
        Schema::dropIfExists('vinculo_cobranca_cliente');
        Schema::dropIfExists('registro_pagamento');
        Schema::dropIfExists('tentativa_cobranca');
        Schema::dropIfExists('cobranca');
        Schema::dropIfExists('pedido');
        Schema::dropIfExists('contato_tel');
        Schema::dropIfExists('contato_financeiro');
        Schema::dropIfExists('representante');
        Schema::dropIfExists('cliente');
        Schema::dropIfExists('tel');
        Schema::dropIfExists('forma_pagamento');
        Schema::dropIfExists('contato_externo');
        Schema::dropIfExists('configuracao');
        Schema::dropIfExists('colaborador');
        Schema::dropIfExists('bling_config');
    }
};
