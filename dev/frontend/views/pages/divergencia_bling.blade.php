@extends('layouts.app')

@section('title', 'Divergência Bling')
@section('body_class', 'divergencia-page')

@section('content')
<?php
$title = "Divergência Bling";
$body_class = "divergencia-page";
$show_header = true;

// Helper para formatar valores monetários
$formatMoney = function($value) {
    return 'R$ ' . number_format($value ?? 0, 2, ',', '.');
};

?>
<div class="cr-wrapper">
    <div class="cr-header-actions">
        <div class="header-title-section">
            <h2>Divergência Bling</h2>
            <p>Lista de pedidos com baixa automática que requerem conferência manual.</p>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="cr-table" id="table-divergencias">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Qtd. Pedidos</th>
                        <th class="valor-col" style="color: #64748b;">Pago Local (Hydra)</th>
                        <th class="valor-col" style="color: #059669;">Pago API (Bling)</th>
                        <th class="valor-col">Divergência</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalDivergencia = 0;
                    if (empty($divergencias)): 
                    ?>
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 2rem; color: #64748b;">Nenhuma divergência encontrada. Tudo sincronizado!</td>
                        </tr>
                    <?php 
                    else: 
                        $gruposCli = [];
                        foreach ($divergencias as $div) {
                            $nomeCli = !empty($div['NOME_CLIENTE']) ? $div['NOME_CLIENTE'] : 'Não Informado';
                            $idCliKey = !empty($div['ID_CLIENTE']) ? $div['ID_CLIENTE'] : $nomeCli;
                            
                            if (!isset($gruposCli[$idCliKey])) {
                                $gruposCli[$idCliKey] = [
                                    'id' => $idCliKey,
                                    'nomeCli' => $nomeCli,
                                    'total_diferenca' => 0,
                                    'total_local' => 0,
                                    'total_bling' => 0,
                                    'qtd_pedidos' => 0,
                                    'pedidos_unicos' => []
                                ];
                            }
                            
                            $keyPed = !empty($div['NUM_PEDIDO']) && $div['NUM_PEDIDO'] !== '—' ? $div['NUM_PEDIDO'] : 'SEM_NUM_' . $div['ID_PEDIDO'];
                            
                            if (!isset($gruposCli[$idCliKey]['pedidos_unicos'][$keyPed])) {
                                $gruposCli[$idCliKey]['pedidos_unicos'][$keyPed] = true;
                                $gruposCli[$idCliKey]['qtd_pedidos']++;
                            }

                            $local = (float)$div['VALOR_PAGO_LOCAL'];
                            $bling = (float)$div['VALOR_PAGO_BLING'];
                            $diferenca = abs($local - $bling);

                            $totalDivergencia += $diferenca;
                            $gruposCli[$idCliKey]['total_diferenca'] += $diferenca;
                            $gruposCli[$idCliKey]['total_local'] += $local;
                            $gruposCli[$idCliKey]['total_bling'] += $bling;
                        }

                        foreach ($gruposCli as $idCliKey => $grupo):
                    ?>
                    <tr onclick="abrirModalDivergencias('<?= $grupo['id'] ?>')" style="cursor:pointer;" class="hover-row">
                        <td><?= htmlspecialchars($grupo['nomeCli']) ?></td>
                        <td><?= $grupo['qtd_pedidos'] ?></td>
                        <td class="valor-col"><?= $formatMoney($grupo['total_local']) ?></td>
                        <td class="valor-col"><?= $formatMoney($grupo['total_bling']) ?></td>
                        <td class="valor-col" style="color: #ef4444; font-weight: 600;"><?= $formatMoney($grupo['total_diferenca']) ?></td>
                    </tr>
                    <?php
                        endforeach; 
                    endif; 
                    ?>
                </tbody>
                <?php if (!empty($divergencias)): ?>
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: bold; border-top: 2px solid #e2e8f0;">
                        <td colspan="4" style="text-align: right; padding-right: 1rem; color: #475569;">Somatório Total das Diferenças:</td>
                        <td class="valor-col" style="color: #ef4444; font-weight: 700; font-size: 1.1em;"><?= $formatMoney($totalDivergencia) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

@include('components.modal_baixa_manual')

<!-- Modal Corrigir Baixa -->
<div id="modal-corrigir-baixa" class="cr-modal" style="display: none;">
    <div class="cr-modal-overlay"></div>
    <div class="cr-modal-container modal-container-md" style="max-width: 400px; padding: 20px;">
        <div class="cr-modal-header">
            <h3 class="cr-modal-title">Corrigir Baixa Local</h3>
            <button class="cr-modal-close" onclick="fecharModalCorrigirBaixa()">&times;</button>
        </div>
        <div class="cr-modal-body">
            <p>Digite o <strong>novo valor total</strong> que deveria constar como pago localmente para este pedido.</p>
            <input type="hidden" id="corrigir-id-pedido" value="">
            <div style="margin-top: 15px;">
                <label for="corrigir-novo-valor" style="font-weight:600; display:block; margin-bottom:5px;">Novo Valor (R$)</label>
                <input type="number" id="corrigir-novo-valor" step="0.01" min="0" class="cr-input" style="width:100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px;">
            </div>
            
            <div class="modal-actions-between" style="margin-top: 20px; justify-content: flex-end;">
                <div class="modal-actions">
                    <button class="btn-modal-cancel" onclick="fecharModalCorrigirBaixa()">
                        Cancelar
                    </button>
                    <button class="btn-modal-confirm-blue" onclick="confirmarCorrecaoBaixa()">
                        <x-icons.check-heavy width="16" height="16" />
                        Salvar Correção
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = "{{ url('/') }}";
    
    function abrirModalCorrigir(idPedido, atualPago) {
        document.getElementById('corrigir-id-pedido').value = idPedido;
        document.getElementById('corrigir-novo-valor').value = atualPago;
        document.getElementById('modal-corrigir-baixa').style.display = 'flex';
    }

    function fecharModalCorrigirBaixa() {
        document.getElementById('modal-corrigir-baixa').style.display = 'none';
    }

    function confirmarCorrecaoBaixa() {
        const idPedido = document.getElementById('corrigir-id-pedido').value;
        const novoValor = document.getElementById('corrigir-novo-valor').value;
        
        if (!idPedido || novoValor === '') {
            alert('Por favor, informe o novo valor.');
            return;
        }

        fetch(BASE_URL + '/divergencias/corrigir-baixa', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_pedido: idPedido, novo_valor: novoValor })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                window.location.reload();
            } else {
                alert(res.error || 'Erro ao corrigir a baixa.');
            }
        })
        .catch(err => {
            alert('Erro de comunicação.');
            console.error(err);
        });
    }

    function estornarBaixaPedido(idPedido) {
        if (!confirm('Deseja realmente estornar todas as baixas locais deste pedido? O valor voltará a ser o do Bling.')) {
            return;
        }

        fetch('{{ url("/divergencias/estornar") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_pedido: idPedido })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                window.location.reload();
            } else {
                alert(res.error || 'Erro ao estornar a baixa.');
            }
        })
        .catch(err => {
            alert('Erro de comunicação.');
            console.error(err);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btnClose = document.getElementById('modal-detalhes-close');
        if (btnClose) {
            btnClose.addEventListener('click', () => {
                document.getElementById('modal-detalhes').style.display = 'none';
            });
        }
    });

    function abrirModalDivergencias(idCliente) {
        const modal = document.getElementById('modal-detalhes');
        if (!modal) return;
        
        const title = document.getElementById('modal-detalhes-title');
        const body = document.getElementById('modal-detalhes-body');

        title.innerText = 'Divergências do Cliente';
        body.innerHTML = '<div class="text-center" style="padding: 20px;">Carregando divergências...</div>';
        
        modal.style.display = 'flex';
        
        fetch(`${BASE_URL}/divergencias/api-divergencias-cliente?id=${idCliente}`)
            .then(res => res.json())
            .then(data => {
                if(data.html) {
                    body.innerHTML = data.html;
                } else {
                    body.innerHTML = '<div class="text-center" style="padding: 20px; color: red;">' + (data.error || 'Erro ao carregar') + '</div>';
                }
            })
            .catch(err => {
                body.innerHTML = '<div class="text-center" style="padding: 20px; color: red;">Erro na requisição.</div>';
            });
    }
</script>
@include('components.modal_detalhes_contas')
<script src="{{ asset('js/baixa_manual.js') }}?v=<?= time() ?>"></script>

@endsection
