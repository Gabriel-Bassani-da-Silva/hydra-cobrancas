<?php
namespace App\Services;

use App\Repositories\PedidoRepository;
use App\Services\CobrancaService;
use Illuminate\Support\Facades\DB;

class ContasReceberFiltroService {

    private PedidoRepository $pedidoRepository;
    private CobrancaService $cobrancaService;

    public function __construct(PedidoRepository $pedidoRepository, CobrancaService $cobrancaService) {
        $this->pedidoRepository = $pedidoRepository;
        $this->cobrancaService = $cobrancaService;
    }

    public function compilarDadosDaAba(string $aba, string $grupo, ?string $status): array {
        $resumoClientes = [];
        $resumoRepresentantes = [];
        $resumoContatosFinanceiros = [];
        $todosPedidos = [];
        
        if ($aba === 'clientes') {
            if ($grupo === 'padrao') {
                $resumoClientes = $this->pedidoRepository->getResumoClientes('inadimplentes');
            } elseif ($grupo === 'financeiro') {
                $resumoContatosFinanceiros = $this->pedidoRepository->getResumoContatosFinanceirosClientes();
            } elseif ($grupo === 'cheques') {
                $resumoClientes = $this->pedidoRepository->getResumoClientes('cheques');
            } elseif ($grupo === 'antecipados') {
                $resumoClientes = $this->pedidoRepository->getResumoClientes('antecipados');
            }
        } elseif ($aba === 'representantes') {
            if ($grupo === 'padrao') {
                $resumoRepresentantes = $this->pedidoRepository->getResumoRepresentantes();
            } elseif ($grupo === 'financeiro') {
                $resumoContatosFinanceiros = $this->pedidoRepository->getResumoContatosFinanceirosRepresentantes();
            }
        } elseif ($aba === 'pedras') {
            if ($grupo === 'padrao') {
                $resumoClientes = $this->pedidoRepository->getResumoClientes('pedras');
            } elseif ($grupo === 'financeiro') {
                $resumoContatosFinanceiros = $this->pedidoRepository->getResumoContatosFinanceirosClientes('pedras');
            }
        } elseif ($aba === 'pedidos') {
            $statusStr = $status ?? 'pendentes';
            if ($statusStr === 'pagos') {
                $todosPedidos = $this->pedidoRepository->getAllPedidosPagos();
            } elseif ($statusStr === 'antecipado') {
                $todosPedidos = $this->pedidoRepository->getAllPedidosPorFormaPagamento(config('hydra.bling.formas_pagamento.antecipado'));
            } elseif ($statusStr === 'cheque') {
                $todosPedidos = $this->pedidoRepository->getAllPedidosPorFormaPagamento(config('hydra.bling.formas_pagamento.cheque'));
            } elseif ($statusStr === 'todos') {
                $todosPedidos = $this->pedidoRepository->getAllPedidos();
            } elseif ($statusStr === 'todos_pendentes') {
                $todosPedidos = $this->pedidoRepository->getAllPedidosPendentesGerais();
            } else {
                $todosPedidos = $this->pedidoRepository->getAllPedidosPendentes();
            }
            $todosPedidos = $this->agruparPedidosPorNumero($todosPedidos);
        }

        $totalBannerVermelho = $this->calcularTotalBanner($aba, $grupo, $status, $resumoClientes, $resumoRepresentantes, $resumoContatosFinanceiros, $todosPedidos);
        
        $todasBaixas = [];
        $divergencias = [];
        if ($aba === 'baixas') {
            $todasBaixas = DB::table('DETALHE_PAGAMENTO as dp')
                ->join('REGISTRO_PAGAMENTO as rp', 'rp.ID_REGISTRO', '=', 'dp.ID_REGISTRO')
                ->join('PEDIDO as p', 'p.ID_PEDIDO', '=', 'dp.ID_PEDIDO')
                ->leftJoin('CONTATO_EXTERNO as c', 'c.ID_CONTATO_BLING', '=', 'p.ID_CLIENTE')
                ->select(
                    'p.ID_CLIENTE',
                    'c.NOME_CONTATO',
                    DB::raw('COUNT(dp.ID_DETALHE) as QTD_BAIXAS'),
                    DB::raw('SUM(dp.VALOR_PAGO_PEDIDO) as TOTAL_BAIXADO'),
                    DB::raw('MAX(rp.DATA_REGISTRO) as ULTIMA_BAIXA')
                )
                ->groupBy('p.ID_CLIENTE', 'c.NOME_CONTATO')
                ->orderBy('ULTIMA_BAIXA', 'desc')
                ->get();
            
            $divergencias = $this->pedidoRepository->getDivergencias();
        }

        return [
            'resumoClientes' => $resumoClientes,
            'resumoRepresentantes' => $resumoRepresentantes,
            'resumoContatosFinanceiros' => $resumoContatosFinanceiros,
            'todosPedidos' => $todosPedidos,
            'todasBaixas' => $todasBaixas,
            'divergencias' => $divergencias,
            'totalBannerVermelho' => $totalBannerVermelho,
            'totais' => $this->pedidoRepository->getTotalEmAberto(),
            'ultimaSinc' => $this->pedidoRepository->getUltimaSincronizacao(),
            'contagensAbas' => $this->pedidoRepository->getContagensAbas(),
            'cobrancasAtivas' => $this->cobrancaService->getCobrancasAtivas(),
            'exibirAte' => $this->pedidoRepository->getExibirAte(),
            'exibirAPartirDe' => $this->pedidoRepository->getExibirAPartirDe(),
        ];
    }

    private function agruparPedidosPorNumero(array $todosPedidos): array {
        $pedidosAgrupados = [];
        foreach ($todosPedidos as $ped) {
            $num = trim($ped['NUM_PEDIDO'] ?? '');
            $key = (!empty($num) && $num !== '—') ? $num : 'single_' . $ped['ID_PEDIDO'];
            
            if (!isset($pedidosAgrupados[$key])) {
                $pedidosAgrupados[$key] = [
                    'NUM_PEDIDO' => $num,
                    'NOME_CLIENTE' => $ped['NOME_CLIENTE'],
                    'NOME_REPRESENTANTE' => $ped['NOME_REPRESENTANTE'],
                    'TOTAL_PEDIDO' => 0,
                    'VALOR_PAGO' => 0,
                    'DATA_VENCIMENTO_MIN' => $ped['DATA_VENCIMENTO'],
                    'DATA_VENCIMENTO_MAX' => $ped['DATA_VENCIMENTO'],
                    'SITUACAO_PEDIDO' => $ped['SITUACAO_PEDIDO'],
                    'PARCELAS' => [],
                    'ID_PEDIDO' => $ped['ID_PEDIDO']
                ];
            }
            
            $pedidosAgrupados[$key]['TOTAL_PEDIDO'] += $ped['TOTAL_PEDIDO'];
            $pedidosAgrupados[$key]['VALOR_PAGO'] += $ped['VALOR_PAGO'];
            $pedidosAgrupados[$key]['PARCELAS'][] = $ped;
            
            if ($ped['DATA_VENCIMENTO'] < $pedidosAgrupados[$key]['DATA_VENCIMENTO_MIN']) {
                $pedidosAgrupados[$key]['DATA_VENCIMENTO_MIN'] = $ped['DATA_VENCIMENTO'];
            }
            if ($ped['DATA_VENCIMENTO'] > $pedidosAgrupados[$key]['DATA_VENCIMENTO_MAX']) {
                $pedidosAgrupados[$key]['DATA_VENCIMENTO_MAX'] = $ped['DATA_VENCIMENTO'];
            }
        }
        
        usort($pedidosAgrupados, function($a, $b) {
            return strcmp($a['DATA_VENCIMENTO_MIN'], $b['DATA_VENCIMENTO_MIN']);
        });
        return $pedidosAgrupados;
    }

    private function calcularTotalBanner(string $aba, string $grupo, ?string $status, array $resumoClientes, array $resumoRepresentantes, array $resumoContatosFinanceiros, array $todosPedidos): float {
        $totalBannerVermelho = 0;
        if ($aba === 'clientes' || $aba === 'representantes' || $aba === 'pedras') {
            $dadosAtivos = [];
            if ($grupo === 'padrao' || $grupo === 'cheques' || $grupo === 'antecipados') {
                $dadosAtivos = ($aba === 'representantes') ? $resumoRepresentantes : $resumoClientes;
            } elseif ($grupo === 'financeiro') {
                $dadosAtivos = $resumoContatosFinanceiros;
            }
            
            foreach ($dadosAtivos as $item) {
                $totalBannerVermelho += (float)($item['TOTAL_VALOR'] ?? 0);
            }
        } elseif ($aba === 'pedidos') {
            $isPagos = ($status ?? 'pendentes') === 'pagos';
            foreach ($todosPedidos as $item) {
                if ($isPagos) {
                    $totalBannerVermelho += (float)($item['TOTAL_PEDIDO'] ?? 0);
                } else {
                    $totalBannerVermelho += ((float)($item['TOTAL_PEDIDO'] ?? 0) - (float)($item['VALOR_PAGO'] ?? 0));
                }
            }
        }
        return (float) $totalBannerVermelho;
    }
}
