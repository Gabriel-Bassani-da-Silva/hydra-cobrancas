<?php
namespace App\Services;

class ModalDetalhesService {

    public function agruparParaFinanceirosOuRepresentantes(array $data): array {
        $gruposCli = [];
        foreach ($data as $c) {
            $nomeCli = !empty($c['NOME_CLIENTE']) ? $c['NOME_CLIENTE'] : (!empty($c['NOME_CONTATO']) ? $c['NOME_CONTATO'] : 'Não Informado');
            $idCliKey = !empty($c['ID_CLIENTE']) ? $c['ID_CLIENTE'] : $nomeCli;
            
            if (!isset($gruposCli[$idCliKey])) {
                $gruposCli[$idCliKey] = [
                    'nomeCli' => $nomeCli,
                    'doc' => $c['CPF_CNPJ'] ?? '',
                    'total' => 0,
                    'ultimoVencimento' => $c['VENCIMENTO'] ?? '',
                    'ids' => [],
                    'pedidos' => []
                ];
            }
            
            $keyPed = !empty($c['NUMERO_DOCUMENTO']) && $c['NUMERO_DOCUMENTO'] !== '—' ? $c['NUMERO_DOCUMENTO'] : 'SEM_NUM_' . $c['ID_CONTA_RECEBER'];
            
            if (!isset($gruposCli[$idCliKey]['pedidos'][$keyPed])) {
                $gruposCli[$idCliKey]['pedidos'][$keyPed] = [
                    'numPedido' => $c['NUMERO_DOCUMENTO'],
                    'parcelas' => [],
                    'total' => 0,
                    'ultimoVencimento' => $c['VENCIMENTO'] ?? '',
                    'ids' => []
                ];
            }
            
            $gruposCli[$idCliKey]['pedidos'][$keyPed]['parcelas'][] = $c;
            
            $valLiq = ((float)($c['VALOR'] ?? 0)) - ((float)($c['VALOR_PAGO'] ?? 0));
            $gruposCli[$idCliKey]['pedidos'][$keyPed]['total'] += $valLiq;
            $gruposCli[$idCliKey]['pedidos'][$keyPed]['ids'][] = $c['ID_CONTA_RECEBER'];
            
            if (($c['VENCIMENTO'] ?? '') > $gruposCli[$idCliKey]['pedidos'][$keyPed]['ultimoVencimento']) {
                $gruposCli[$idCliKey]['pedidos'][$keyPed]['ultimoVencimento'] = $c['VENCIMENTO'] ?? '';
            }
            
            $gruposCli[$idCliKey]['total'] += $valLiq;
            $gruposCli[$idCliKey]['ids'][] = $c['ID_CONTA_RECEBER'];
            
            if (($c['VENCIMENTO'] ?? '') > $gruposCli[$idCliKey]['ultimoVencimento']) {
                $gruposCli[$idCliKey]['ultimoVencimento'] = $c['VENCIMENTO'] ?? '';
            }
        }

        return $gruposCli;
    }

    public function agruparParaClientes(array $data): array {
        $gruposPed = [];
        foreach ($data as $c) {
            $key = !empty($c['NUMERO_DOCUMENTO']) && $c['NUMERO_DOCUMENTO'] !== '—' ? $c['NUMERO_DOCUMENTO'] : 'SEM_NUM_' . $c['ID_CONTA_RECEBER'];
            
            if (!isset($gruposPed[$key])) {
                $gruposPed[$key] = [
                    'numPedido' => $c['NUMERO_DOCUMENTO'],
                    'nomeCli' => !empty($c['NOME_CLIENTE']) ? $c['NOME_CLIENTE'] : (!empty($c['NOME_CONTATO']) ? $c['NOME_CONTATO'] : 'Não Informado'),
                    'docCli' => $c['CPF_CNPJ'] ?? '',
                    'parcelas' => [],
                    'total' => 0,
                    'ultimoVencimento' => $c['VENCIMENTO'] ?? '',
                    'ids' => []
                ];
            }
            
            $gruposPed[$key]['parcelas'][] = $c;
            
            $valLiq = ((float)($c['VALOR'] ?? 0)) - ((float)($c['VALOR_PAGO'] ?? 0));
            $gruposPed[$key]['total'] += $valLiq;
            $gruposPed[$key]['ids'][] = $c['ID_CONTA_RECEBER'];
            
            if (($c['VENCIMENTO'] ?? '') > $gruposPed[$key]['ultimoVencimento']) {
                $gruposPed[$key]['ultimoVencimento'] = $c['VENCIMENTO'] ?? '';
            }
        }

        return $gruposPed;
    }
}
