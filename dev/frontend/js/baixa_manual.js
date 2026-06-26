// ── BAIXA MANUAL ────────────────────────────────────────────────────────────
let parcelasBaixaAtual = [];

function getBaseUrl() {
    if (typeof BASE !== 'undefined') return BASE;
    if (typeof BASE_URL !== 'undefined') return BASE_URL;
    return '';
}

// ── Funções Auxiliares ──────────────────────────────────────────────────────────
if (typeof formatCurrency !== 'function') {
    window.formatCurrency = function(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    };
}

if (typeof formatDate !== 'function') {
    window.formatDate = function(dateStr) {
        if (!dateStr || dateStr === '0000-00-00') return '—';
        const [y, m, d] = dateStr.split(' ')[0].split('-');
        return `${d}/${m}/${y}`;
    };
}

function abrirModalBaixa(idsPedidos) {
    if (!idsPedidos) return;
    
    let idsArray = [];
    if (typeof idsPedidos === 'string') {
        idsArray = idsPedidos.split(',').map(id => id.trim());
    } else if (Array.isArray(idsPedidos)) {
        idsArray = idsPedidos.map(id => String(id));
    } else {
        idsArray = [String(idsPedidos)];
    }

    if (idsArray.length === 0) return;

    parcelasBaixaAtual = [];
    
    fetch(`${getBaseUrl()}/contas-receber/api/parcelas-por-ids?ids=${idsArray.join(',')}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                alert('Erro: ' + (res.error || 'Não foi possível carregar as parcelas.'));
                return;
            }

            const parcelasApi = res.data || [];
            
            parcelasApi.forEach(p => {
                if (parseInt(p.SITUACAO_PEDIDO) !== 2) {
                    parcelasBaixaAtual.push(p);
                }
            });

            if (parcelasBaixaAtual.length === 0) {
                alert("Todas as parcelas selecionadas já estão pagas ou não foram encontradas.");
                return;
            }

            renderParcelasModalBaixa();
            document.getElementById('modal-baixa-manual').style.display = 'flex';
        })
        .catch(err => {
            alert('Erro de conexão ao buscar parcelas.');
        });
}

function fecharModalBaixa() {
    document.getElementById('modal-baixa-manual').style.display = 'none';
}

function renderParcelasModalBaixa() {
    const container = document.getElementById('baixa-parcelas-container');
    container.classList.remove('hidden');
    
    // Agrupar parcelas
    const grupos = {};
    parcelasBaixaAtual.forEach((p, idx) => {
        const num = (p.NUM_PEDIDO && p.NUM_PEDIDO !== '—') ? p.NUM_PEDIDO : `avulso_${p.ID_PEDIDO}`;
        if (!grupos[num]) {
            grupos[num] = {
                numPedido: p.NUM_PEDIDO,
                parcelas: [],
                totalDevendo: 0
            };
        }
        const devendo = parseFloat(p.TOTAL_PEDIDO) - parseFloat(p.VALOR_PAGO || 0);
        p._devendo = devendo;
        p._idx = idx;
        grupos[num].parcelas.push(p);
        grupos[num].totalDevendo += devendo;
    });

    let html = `
        <table class="cr-table" style="width: 100%; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.05); margin-bottom: 0;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="width: 40px; padding: 10px;"></th>
                    <th style="padding: 10px; font-weight: 600; color: #475569;">Pedido</th>
                    <th class="center-col" style="padding: 10px; font-weight: 600; color: #475569;">Parcelas</th>
                    <th class="valor-col" style="padding: 10px; font-weight: 600; color: #475569;">Valor Devendo</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    const totalPedidos = Object.keys(grupos).length;
    const isAutoExpandCli = (totalPedidos === 1);

    Object.values(grupos).forEach((g, gIdx) => {
        const nomeGrupo = g.numPedido && g.numPedido !== '—' ? `Ped. ${g.numPedido}` : 'Conta Avulsa';
        const subGroupId = `baixa_grupo_${gIdx}`;
        
        const isAutoExpandPed = (g.parcelas.length === 1);
        const shouldExpand = isAutoExpandCli || isAutoExpandPed;
        
        const expandedCls = shouldExpand ? 'expanded' : '';
        const dNoneCls = shouldExpand ? '' : 'd-none';
        
        // Linha principal expansível
        html += `
            <tr class="expandable-row" style="cursor:pointer; border-bottom: 1px solid #e2e8f0;">
                <td class="expand-col" style="padding: 10px;">
                    <button class="btn-expand ${expandedCls}" data-toggle-parcelas="${subGroupId}" title="Ver parcelas">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M7 10l5 5 5-5z"/></svg>
                    </button>
                </td>
                <td style="padding: 10px;"><strong>${nomeGrupo}</strong></td>
                <td class="text-center" style="padding: 10px;"><span style="font-size: 0.75rem; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 12px; font-weight: 600;">${g.parcelas.length}</span></td>
                <td class="valor-col" style="padding: 10px; text-align: right;" onclick="event.stopPropagation();">
                    <div style="color:#ef4444; font-weight: 600;">${formatCurrency(g.totalDevendo)}</div>
                    <label style="display:inline-flex; align-items:center; gap:4px; cursor:pointer; font-size:0.75rem; color:#64748b; margin-top:2px;">
                        <input type="checkbox" id="check-grupo-${gIdx}" class="checkbox-grupo-baixa" onchange="toggleBaixaGrupo(${gIdx}, this.checked)" style="width:14px; height:14px; cursor:pointer; accent-color: #2563eb; margin:0;">
                        Baixar Tudo
                    </label>
                </td>
            </tr>
        `;
        
        // Linha detalhe com parcelas
        html += `
            <tr class="detail-row ${dNoneCls}" id="parcelas_${subGroupId}">
                <td colspan="4" style="padding: 0;">
                    <div style="background: #f8fafc; padding: 10px 15px; border-bottom: 1px solid #e2e8f0; border-left: 3px solid #3b82f6;">
        `;
        
        g.parcelas.forEach(p => {
            html += `
                        <div style="display:flex; justify-content:space-between; align-items:center; padding: 8px 0; border-bottom: 1px dashed #cbd5e1;">
                            <div style="flex:1;">
                                <strong style="font-size:0.85rem; color:#475569;">Venc: ${formatDate(p.DATA_VENCIMENTO_MIN || p.DATA_VENCIMENTO)}</strong>
                                <span style="font-size:0.75rem; color:#64748b; margin-left:8px;">Falta: ${formatCurrency(p._devendo)}</span>
                            </div>
                            <div style="display:flex; align-items:center; gap: 8px; width: 220px;">
                                <div style="position:relative; flex:1;">
                                    <span style="position:absolute; left:8px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:0.85rem;">R$</span>
                                    <input type="number" step="0.01" min="0" max="${p._devendo.toFixed(2)}" class="input-baixa-valor grupo-input-${gIdx}" id="input-baixa-${p._idx}" data-id="${p.ID_PEDIDO}" data-idx="${p._idx}" value="0.00" style="width:100%; padding:6px 6px 6px 26px; border:1px solid #cbd5e1; border-radius:4px; font-size:0.9rem; text-align:right; font-weight: 500;" oninput="atualizarTotalBaixa()">
                                </div>
                                <label style="display:inline-flex; align-items:center; justify-content:center; cursor:pointer; padding: 4px; border-radius: 4px;">
                                    <input type="checkbox" id="check-parcela-${p._idx}" class="checkbox-parcela-${gIdx}" onchange="toggleBaixaParcela(${p._idx}, ${p._devendo.toFixed(2)}, this.checked)" style="width:16px; height:16px; cursor:pointer; accent-color: #2563eb; margin:0;" title="Preencher Total">
                                </label>
                            </div>
                        </div>
            `;
        });
        
        html += `
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    container.innerHTML = html;
    atualizarTotalBaixa();
}

function toggleBaixarTudoCheckbox(isChecked) {
    document.querySelectorAll('.checkbox-grupo-baixa').forEach(chk => {
        chk.checked = isChecked;
    });
    document.querySelectorAll('.input-baixa-valor').forEach(input => {
        if (isChecked) {
            input.value = parseFloat(input.getAttribute('max')).toFixed(2);
        } else {
            input.value = '0.00';
        }
    });
    atualizarTotalBaixa();
}

function toggleBaixaGrupo(gIdx, isChecked) {
    document.querySelectorAll(`.grupo-input-${gIdx}`).forEach(input => {
        if (isChecked) {
            input.value = parseFloat(input.getAttribute('max')).toFixed(2);
        } else {
            input.value = '0.00';
        }
    });
    document.querySelectorAll(`.checkbox-parcela-${gIdx}`).forEach(chk => {
        chk.checked = isChecked;
    });
    atualizarTotalBaixa();
}

function toggleBaixaParcela(idx, maxValor, isChecked) {
    const input = document.getElementById(`input-baixa-${idx}`);
    if (input) {
        if (isChecked) {
            input.value = maxValor.toFixed(2);
        } else {
            input.value = '0.00';
        }
        atualizarTotalBaixa();
    }
}

function baixarTodasParcelas() {
    document.querySelectorAll('.input-baixa-valor').forEach(input => {
        input.value = parseFloat(input.getAttribute('max')).toFixed(2);
    });
    atualizarTotalBaixa();
}

function atualizarTotalBaixa() {
    let total = 0;
    document.querySelectorAll('.input-baixa-valor').forEach(input => {
        total += parseFloat(input.value || 0);
    });
    document.getElementById('baixa-total-display').textContent = formatCurrency(total);
}

function confirmarBaixa() {
    const inputs = document.querySelectorAll('.input-baixa-valor');
    const baixas = [];
    let hasInvalid = false;
    
    inputs.forEach(input => {
        const val = parseFloat(input.value || 0);
        const max = parseFloat(input.getAttribute('max'));
        if (val < 0 || val > max) {
            hasInvalid = true;
            input.style.borderColor = 'red';
        } else {
            input.style.borderColor = '#cbd5e1';
        }
        
        if (val > 0) {
            baixas.push({
                id: parseInt(input.getAttribute('data-id')),
                valor: val
            });
        }
    });

    if (hasInvalid) {
        alert("Alguns valores são maiores que o valor pendente ou inválidos. Corrija-os.");
        return;
    }

    if (baixas.length === 0) {
        alert("Nenhum valor a baixar.");
        return;
    }

    const btnConfirma = document.querySelector('#modal-baixa-manual .btn-modal-confirm-blue');
    const oldText = btnConfirma.innerHTML;
    btnConfirma.innerHTML = 'Baixando...';
    btnConfirma.disabled = true;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch(getBaseUrl() + '/contas-receber/baixar', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ baixas })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            fecharModalBaixa();
            window.location.reload();
        } else {
            alert('Erro: ' + (res.error || 'Erro desconhecido'));
            btnConfirma.innerHTML = oldText;
            btnConfirma.disabled = false;
        }
    })
    .catch(err => {
        alert('Erro de conexão ao salvar.');
        btnConfirma.innerHTML = oldText;
        btnConfirma.disabled = false;
    });
}
