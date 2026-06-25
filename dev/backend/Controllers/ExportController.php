<?php
namespace App\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExportController extends Controller {

    /**
     * Exporta todas as tabelas principais para um arquivo JSON.
     */
    public function exportarTudo() {
        $tabelas = [
            'CLIENTE', 
            'PEDIDO', 
            'REPRESENTANTE', 
            'COBRANCA', 
            'CONTATO_FINANCEIRO', 
            'TEL', 
            'DETALHE_PAGAMENTO',
            'CONTATO_EXTERNO',
            'TENTATIVA_COBRANCA',
            'REGISTRO_PAGAMENTO'
        ];
        
        $export = [];

        foreach ($tabelas as $tabela) {
            try {
                $export[$tabela] = DB::table($tabela)->get();
            } catch (\Exception $e) {
                // Se a tabela não existir ou der erro, ignora e segue o jogo
                $export[$tabela] = [];
            }
        }

        return response()->json($export, 200, [
            'Content-Disposition' => 'attachment; filename="backup_completo_hydra_' . date('Y-m-d_H-i') . '.json"'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Exporta uma tabela específica para JSON.
     */
    public function exportarTabela($nomeTabela) {
        // Validação básica para evitar SQL Injection via nome de tabela
        $nomeTabelaUpper = strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '', $nomeTabela));

        try {
            $data = DB::table($nomeTabelaUpper)->get();
        } catch (\Exception $e) {
            return response()->json(['error' => 'A tabela informada não existe ou ocorreu um erro na leitura.'], 404);
        }

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="export_' . strtolower($nomeTabelaUpper) . '_' . date('Y-m-d_H-i') . '.json"'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
