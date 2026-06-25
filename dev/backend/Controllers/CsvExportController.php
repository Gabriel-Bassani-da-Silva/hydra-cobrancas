<?php
namespace App\Controllers;

use Illuminate\Support\Facades\Response;
use App\Repositories\ContatoRepository;
use App\Repositories\PedidoRepository;

class CsvExportController extends Controller {

    public function exportarContatos() {
        // Validação simples de token
        $token = request()->query('token');
        $expectedToken = env('EXPORT_CSV_TOKEN', '123456');
        
        if ($token !== $expectedToken) {
            return response('Acesso negado. Token inválido.', 403);
        }

        $contatoRepo = new ContatoRepository();
        $pedidoRepo = new PedidoRepository();

        // Buscar todos os clientes e representantes (sem filtros)
        $clientes = $contatoRepo->getClientesComTelefones();
        $reps = $contatoRepo->getRepresentantesComTelefones();

        // Buscar inadimplentes para marcar na planilha
        $inadimplentesClientes = $pedidoRepo->getResumoClientes('inadimplentes');
        $idsInadimplentes = array_filter(array_column($inadimplentesClientes, 'ID_CONTATO_BLING'));

        $todosContatos = [];

        // Mesclar Clientes
        foreach ($clientes as $c) {
            $id = $c['ID_CONTATO_BLING'];
            $todosContatos[$id] = [
                'ID_BLING' => $id,
                'NOME' => $c['NOME_CONTATO'],
                'DOCUMENTO' => $c['NUMERO_DOCUMENTO'] ?? '',
                'TIPO' => 'Cliente',
                'INADIMPLENTE' => in_array($id, $idsInadimplentes) ? 'Sim' : 'Não',
                'TELEFONES' => $c['telefones_arr'] ?? []
            ];
        }

        // Mesclar Representantes (pode sobrepor o tipo se for ambos)
        foreach ($reps as $r) {
            $id = $r['ID_CONTATO_BLING'];
            if (isset($todosContatos[$id])) {
                $todosContatos[$id]['TIPO'] = 'Cliente e Representante';
            } else {
                $todosContatos[$id] = [
                    'ID_BLING' => $id,
                    'NOME' => $r['NOME_CONTATO'],
                    'DOCUMENTO' => $r['NUMERO_DOCUMENTO'] ?? '',
                    'TIPO' => 'Representante',
                    'INADIMPLENTE' => 'Não', // Representantes puros não entram no resumo de inadimplentes de pedidos
                    'TELEFONES' => $r['telefones_arr'] ?? []
                ];
            }
        }

        // Buscar contatos financeiros para vincular
        $todosContatosFinanceiros = $contatoRepo->getAllContatosFinanceiros();
        $vinculosPorContato = [];
        foreach ($todosContatosFinanceiros as $cf) {
            // cf_vinculos_arr vem no formato [['id_bling' => '...', 'nome' => '...'], ...]
            foreach ($cf['cf_vinculos_arr'] as $vinc) {
                $idContatoBling = $vinc['id_bling'];
                if (!isset($vinculosPorContato[$idContatoBling])) {
                    $vinculosPorContato[$idContatoBling] = [];
                }
                $vinculosPorContato[$idContatoBling][] = $cf['NOME_CF'] . ' (' . $cf['NUM_TEL'] . ')';
            }
        }

        // Descobrir qual o máximo de telefones de um único contato para gerar cabeçalhos dinâmicos
        $maxTels = 0;
        foreach ($todosContatos as &$ct) {
            $qtd = count($ct['TELEFONES']);
            if ($qtd > $maxTels) {
                $maxTels = $qtd;
            }
        }

        // Gerar cabeçalho do CSV
        $headers = ['ID Bling', 'Nome do Contato', 'CPF/CNPJ', 'Tipo', 'Inadimplente', 'Contatos Financeiros'];
        for ($i = 1; $i <= $maxTels; $i++) {
            $headers[] = "Telefone $i (Número)";
            $headers[] = "Telefone $i (Origem)";
            $headers[] = "Telefone $i (Status)";
        }

        // Função utilitária para formatar o documento
        $formatDoc = function($doc) {
            $doc = preg_replace('/[^0-9]/', '', $doc);
            if (strlen($doc) === 11) {
                return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
            } elseif (strlen($doc) === 14) {
                return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
            }
            return $doc;
        };

        // Função utilitária para formatar telefone
        $formatPhone = function($num) {
            $num = preg_replace('/[^0-9]/', '', $num);
            if (strlen($num) == 11) {
                return preg_replace('/(\d{2})(\d{1})(\d{4})(\d{4})/', '($1) $2 $3-$4', $num);
            } elseif (strlen($num) == 10) {
                return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $num);
            }
            return $num;
        };

        // Output data via stream para não estourar memória e manter a resposta rápida
        $callback = function() use ($todosContatos, $headers, $vinculosPorContato, $maxTels, $formatDoc, $formatPhone) {
            $file = fopen('php://output', 'w');
            
            // Adicionar BOM para Excel abrir o UTF-8 corretamente
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            fputcsv($file, $headers, ',');

            foreach ($todosContatos as $idBling => $data) {
                $row = [
                    $data['ID_BLING'],
                    $data['NOME'],
                    $formatDoc($data['DOCUMENTO']),
                    $data['TIPO'],
                    $data['INADIMPLENTE'],
                    isset($vinculosPorContato[$idBling]) ? implode(' | ', $vinculosPorContato[$idBling]) : ''
                ];

                $tels = array_values($data['TELEFONES']); // reindexar para 0,1,2...

                for ($i = 0; $i < $maxTels; $i++) {
                    if (isset($tels[$i])) {
                        $t = $tels[$i];
                        $status = $t['confirmado'] ? 'Confirmado' : 'Tentativa';
                        $origem = $t['origem'] === 'bling' ? 'Bling' : 'Manual';
                        
                        $row[] = $formatPhone($t['num']);
                        $row[] = $origem;
                        $row[] = $status;
                    } else {
                        // Preencher vazio se não tiver este telefone
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                    }
                }

                fputcsv($file, $row, ',');
            }

            fclose($file);
        };

        return Response::stream($callback, 200, [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=export_contatos.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);
    }
}
