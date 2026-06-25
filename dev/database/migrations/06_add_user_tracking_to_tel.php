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
        Schema::table('TEL', function (Blueprint $table) {
            $table->integer('ID_COLAB_CRIACAO')->nullable();
            $table->integer('ID_COLAB_ALTERACAO')->nullable();
            
            $table->foreign('ID_COLAB_CRIACAO', 'FK_TEL_CRIACAO')->references('ID_COLABORADOR')->on('COLABORADOR')->onDelete('set null');
            $table->foreign('ID_COLAB_ALTERACAO', 'FK_TEL_ALTERACAO')->references('ID_COLABORADOR')->on('COLABORADOR')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('TEL', function (Blueprint $table) {
            $table->dropForeign('FK_TEL_CRIACAO');
            $table->dropForeign('FK_TEL_ALTERACAO');
            $table->dropColumn(['ID_COLAB_CRIACAO', 'ID_COLAB_ALTERACAO']);
        });
    }
};
