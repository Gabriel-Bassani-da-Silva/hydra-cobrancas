<?php
namespace App\Controllers;

use App\Models\Cobranca;
use Illuminate\Http\Request;

class PerfilController extends Controller {
    public function index() {
        $idColaborador = auth()->user()->ID_COLABORADOR ?? 0;

        // Buscar cobranças ativas com Eloquent
        $cobrancas = Cobranca::with([
            'contatoFinanceiro.representantes', 
            'representante.contatoExterno', 
            'clientes.contatoExterno',
            'pedidos'
        ])
        ->where('ID_COLABORADOR', $idColaborador)
        ->whereNull('DATA_FIM')
        ->orderBy('DATA_INICIO', 'desc')
        ->get();

        // Mapear para o formato que a view espera
        $todasCobrancas = $cobrancas->map(function ($c) {
            $isCfRepresentante = false;
            if ($c->contatoFinanceiro) {
                $isCfRepresentante = $c->contatoFinanceiro->representantes->isNotEmpty();
            }

            $totalDivida = 0;
            $qtdPedidos = 0;

            foreach ($c->pedidos as $p) {
                $pagoLocal = $p->detalhesPagamento->sum('VALOR_PAGO_PEDIDO');
                $pagoEfetivo = max((float)$p->VALOR_PAGO_BLING, (float)$pagoLocal);
                
                if ($pagoEfetivo < (float)$p->TOTAL_PEDIDO) {
                    $totalDivida += ((float)$p->TOTAL_PEDIDO - $pagoEfetivo);
                    $qtdPedidos++;
                }
            }

            $clientes = $c->clientes->map(function ($cliente) {
                return [
                    'ID_CONTATO_BLING' => $cliente->ID_CONTATO_BLING,
                    'NOME_CONTATO' => $cliente->contatoExterno->NOME_CONTATO ?? '',
                    'NUMERO_DOCUMENTO' => $cliente->contatoExterno->NUMERO_DOCUMENTO ?? ''
                ];
            })->toArray();

            return [
                'ID_COBRANCA' => $c->ID_COBRANCA,
                'DATA_INICIO' => $c->DATA_INICIO,
                'STATUS_ATENDIMENTO' => $c->STATUS_ATENDIMENTO,
                'DATA_FIM' => $c->DATA_FIM,
                'ID_CONTATO' => $c->ID_CONTATO,
                'ID_REPRESENTANTE' => $c->ID_REPRESENTANTE,
                'ID_COLABORADOR' => $c->ID_COLABORADOR,
                'NOME_FINANCEIRO' => $c->contatoFinanceiro->NOME_CONTATO ?? null,
                'NOME_REPRESENTANTE' => $c->representante->contatoExterno->NOME_CONTATO ?? null,
                'TOTAL_DIVIDA' => $totalDivida,
                'QTD_PEDIDOS' => $qtdPedidos,
                'IS_CF_REPRESENTANTE' => $isCfRepresentante,
                'CLIENTES' => $clientes
            ];
        })->filter(function ($c) {
            return $c['QTD_PEDIDOS'] > 0;
        })->values();

        $aba = request()->query('aba', 'clientes');
        $grupo = request()->query('grupo', 'padrao');

        $cobrancasClientes = [];
        $cobrancasRepresentantes = [];
        $cobrancasFinanceirosClientes = [];
        $cobrancasFinanceirosRepresentantes = [];

        foreach ($todasCobrancas as $cob) {
            if ($cob['ID_CONTATO']) {
                if (!empty($cob['IS_CF_REPRESENTANTE'])) {
                    $cobrancasFinanceirosRepresentantes[] = $cob;
                } else {
                    $cobrancasFinanceirosClientes[] = $cob;
                }
            } elseif ($cob['ID_REPRESENTANTE']) {
                $cobrancasRepresentantes[] = $cob;
            } else {
                $cobrancasClientes[] = $cob;
            }
        }

        // Definir qual lista será exibida na view
        $minhasCobrancas = [];
        $cobrancasFinanceiros = []; // Para a contagem da sub-aba!

        if ($aba === 'representantes') {
            $cobrancasFinanceiros = $cobrancasFinanceirosRepresentantes;
            if ($grupo === 'financeiro') {
                $minhasCobrancas = $cobrancasFinanceirosRepresentantes;
            } else {
                $minhasCobrancas = $cobrancasRepresentantes;
            }
        } else {
            $cobrancasFinanceiros = $cobrancasFinanceirosClientes;
            if ($grupo === 'financeiro') {
                $minhasCobrancas = $cobrancasFinanceirosClientes;
            } else {
                $minhasCobrancas = $cobrancasClientes;
            }
        }

        $minhasBaixas = [];
        $countBaixas = (int) \Illuminate\Support\Facades\DB::table('DETALHE_PAGAMENTO as dp')
            ->join('REGISTRO_PAGAMENTO as rp', 'rp.ID_REGISTRO', '=', 'dp.ID_REGISTRO')
            ->join('PEDIDO as p', 'p.ID_PEDIDO', '=', 'dp.ID_PEDIDO')
            ->where('rp.ID_COLABORADOR', $idColaborador)
            ->distinct()
            ->count('p.ID_CLIENTE');

        if ($aba === 'baixas') {
            $minhasBaixas = \Illuminate\Support\Facades\DB::table('DETALHE_PAGAMENTO as dp')
                ->join('REGISTRO_PAGAMENTO as rp', 'rp.ID_REGISTRO', '=', 'dp.ID_REGISTRO')
                ->join('PEDIDO as p', 'p.ID_PEDIDO', '=', 'dp.ID_PEDIDO')
                ->leftJoin('CONTATO_EXTERNO as c', 'c.ID_CONTATO_BLING', '=', 'p.ID_CLIENTE')
                ->where('rp.ID_COLABORADOR', $idColaborador)
                ->select(
                    'p.ID_CLIENTE',
                    'c.NOME_CONTATO',
                    \Illuminate\Support\Facades\DB::raw('COUNT(dp.ID_DETALHE) as QTD_BAIXAS'),
                    \Illuminate\Support\Facades\DB::raw('SUM(dp.VALOR_PAGO_PEDIDO) as TOTAL_BAIXADO'),
                    \Illuminate\Support\Facades\DB::raw('MAX(rp.DATA_REGISTRO) as ULTIMA_BAIXA')
                )
                ->groupBy('p.ID_CLIENTE', 'c.NOME_CONTATO')
                ->orderBy('ULTIMA_BAIXA', 'desc')
                ->get();
        }

        return view('pages.perfil', [
            'aba' => $aba,
            'grupo' => $grupo,
            'minhasCobrancas' => $minhasCobrancas,
            'cobrancasFinanceiros' => $cobrancasFinanceiros,
            'cobrancasClientes' => $cobrancasClientes,
            'cobrancasRepresentantes' => $cobrancasRepresentantes,
            'minhasBaixas' => $minhasBaixas,
            'countBaixas' => $countBaixas
        ]);
    }

    public function apiPedidos() {
        $idCobranca = request()->query('id');
        if (!$idCobranca) {
            return response()->json(['error' => 'ID da cobrança não informado'], 400);
        }

        try {
            $cobranca = Cobranca::with('pedidos.cliente.contatoExterno')->find($idCobranca);

            if (!$cobranca) {
                return response()->json(['error' => 'Cobrança não encontrada'], 404);
            }

            // Mapeia para o padrão esperado (ordenado por VENCIMENTO ASC)
            $pedidos = $cobranca->pedidos->sortBy('DATA_VENCIMENTO');
            
            $contas = $pedidos->map(function($p) {
                return [
                    'ID_CONTA_RECEBER' => $p->ID_PEDIDO,
                    'VALOR' => $p->TOTAL_PEDIDO,
                    'VENCIMENTO' => $p->DATA_VENCIMENTO,
                    'NUMERO_DOCUMENTO' => $p->NUM_PEDIDO,
                    'VALOR_PAGO' => $p->VALOR_PAGO_BLING,
                    'SITUACAO' => $p->SITUACAO_PEDIDO,
                    'NOME_CLIENTE' => $p->cliente?->contatoExterno?->NOME_CONTATO ?? '',
                    'CPF_CNPJ' => $p->cliente?->contatoExterno?->NUMERO_DOCUMENTO ?? '',
                    'ID_CLIENTE' => $p->ID_CLIENTE,
                ];
            })->values()->toArray();

            $tipo = request()->query('tipo', 'clientes');
            $data = $contas;

            // Retorna a view do Laravel em formato string (renderizado)
            $html = view('components.modal_detalhes_contas', ['data' => $contas, 'tipo' => $tipo])->render();

            return response()->json(['success' => true, 'html' => $html]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function apiBaixasColaborador() {
        $idCliente = request()->query('id');
        if (!$idCliente) {
            return response()->json(['error' => 'ID não informado']);
        }

        $idColaborador = auth()->user()->ID_COLABORADOR ?? 0;
        
        $baixas = \Illuminate\Support\Facades\DB::table('DETALHE_PAGAMENTO as dp')
            ->join('REGISTRO_PAGAMENTO as rp', 'rp.ID_REGISTRO', '=', 'dp.ID_REGISTRO')
            ->join('PEDIDO as p', 'p.ID_PEDIDO', '=', 'dp.ID_PEDIDO')
            ->leftJoin('COLABORADOR as col', 'col.ID_COLABORADOR', '=', 'rp.ID_COLABORADOR')
            ->where('rp.ID_COLABORADOR', $idColaborador)
            ->where('p.ID_CLIENTE', $idCliente)
            ->select(
                'dp.ID_DETALHE', 
                'dp.VALOR_PAGO_PEDIDO', 
                'rp.DATA_REGISTRO', 
                'p.NUM_PEDIDO', 
                'p.ID_PEDIDO',
                'col.NOME_COLABORADOR'
            )
            ->orderBy('rp.DATA_REGISTRO', 'desc')
            ->get();

        $html = view('components.modal_baixas_cliente', ['baixas' => $baixas])->render();
        return response()->json(['html' => $html]);
    }
}
