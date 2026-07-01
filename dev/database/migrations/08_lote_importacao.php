<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void {
        // Cria tabela de lotes de importação
        Schema::create('LOTE_IMPORTACAO', function (Blueprint $table) {
            $table->integer('ID_LOTE', true);
            $table->dateTime('DATA_CRIACAO')->useCurrent();
            $table->string('NOME_ARQUIVO', 255)->nullable();
            $table->integer('QTD_REGISTROS')->default(0);
            $table->unsignedBigInteger('ID_USUARIO')->nullable();
        });

        // Adiciona coluna ID_LOTE em REGISTRO_PAGAMENTO
        Schema::table('REGISTRO_PAGAMENTO', function (Blueprint $table) {
            $table->integer('ID_LOTE')->nullable()->after('DATA_REGISTRO');
            $table->foreign('ID_LOTE', 'FK_REG_PAG_LOTE')
                  ->references('ID_LOTE')
                  ->on('LOTE_IMPORTACAO')
                  ->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::table('REGISTRO_PAGAMENTO', function (Blueprint $table) {
            $table->dropForeign('FK_REG_PAG_LOTE');
            $table->dropColumn('ID_LOTE');
        });
        Schema::dropIfExists('LOTE_IMPORTACAO');
    }
};
