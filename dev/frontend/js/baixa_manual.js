// ── BAIXA MANUAL ────────────────────────────────────────────────────────────
let parcelasBaixaAtual = [];
// 🌟 TRAVA DE SEGURANÇA GLOBAL: impede cliques e requisições simultâneas
let requisicaoEmAndamento = false;

function getBaseUrl() {
    if (typeof BASE !== 'undefined') return BASE;
    if (typeof BASE_URL !== 'undefined') return BASE_URL;
    return '';
}

// ── Funções Auxiliares ──────────────────────────────────────────────────────────
if (typeof formatCurrency !== 'function') {
    window.formatCurrency = function (value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    };
}

if (typeof formatDate !== 'function') {
    window.formatDate = function (dateStr) {
        if (!dateStr || dateStr === '0000-00-00') return '—';
        const [y, m, d] = dateStr.split(' ')[0].split('-');
        return `${d}/${m}/${y}`;
    };
}

function abrirModalBaixa(idsPedidos) {
    if (!idsPedidos) return;

    // 🌟 SE JÁ EXISTE UMA REQUISIÇÃO EM ANDAMENTO, BLOQUEIA O CLIQUE IMEDIATAMENTE
    if (requisicaoEmAndamento) {
        console.warn('Bloqueio ativo: Evitando clique duplicado.');
        return;
    }

    let idsArray = [];
    if (typeof idsPedidos === 'string') {
        idsArray = idsPedidos.split(',').map(id => id.trim());
    } else if (Array.isArray(idsPedidos)) {
        idsArray = idsPedidos.map(id => String(id));
    } else {
        idsArray = [String(idsPedidos)];
    }

    if (idsArray.length === 0) return;

    // Ativa a trava de segurança antes de iniciar o fetch
    requisicaoEmAndamento = true;

    // Limpa o estado e a tela anterior
    parcelasBaixaAtual = [];
    const container = document.getElementById('baixa-parcelas-container');
    if (container) {
        container.innerHTML = '<div style="text-align:center; padding:15px; color:#64748b;">A carregar parcelas...</div>';
    }

    fetch(`${getBaseUrl()}/contas-receber/api/parcelas-por-ids?ids=${idsArray.join(',')}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                alert('Erro: ' + (res.error || 'Não foi possível carregar as parcelas.'));
                return;
            }

            const parcelasApi = res.data || [];
            const idsUnicosProcessados = new Set();

            parcelasApi.forEach(p => {
                const idUnico = p.ID_PARCELA || p.ID || p.ID_PEDIDO;
                if (parseInt(p.SITUACAO_PEDIDO) !== 2 && !idsUnicosProcessados.has(idUnico)) {
                    idsUnicosProcessados.add(idUnico);
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
            console.error(err);
            alert('Erro de conexão ao buscar parcelas.');
        })
        .finally(() => {
            // 🌟 SÓ LIBERA PARA UM NOVO CLIQUE QUANDO A REQUISIÇÃO TERMINAR COMPLETAMENTE
            requisicaoEmAndamento = false;
        });
}

function fecharModalBaixa() {
    document.getElementById('modal-baixa-manual').style.display = 'none';
    const container = document.getElementById('baixa-parcelas-container');
    if (container) container.innerHTML = '';
    // Garante que o estado de clique está limpo ao fechar
    requisicaoEmAndamento = false;
}

function renderParcelasModalBaixa() {
    const container = document.getElementById('baixa-parcelas-container');
    if (!container) return;

    container.innerHTML = '';
    container.classList.remove('hidden');

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
        <table class="cr-table baixa-parcelas-table">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>Pedido</th>
                    <th class="col-parcelas">Parcelas</th>
                    <th class="col-valor">Valor Devendo</th>
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
        const dNoneStyle = shouldExpand ? '' : 'display: none;';

        html += `
            <tr class="baixa-row-grupo expandable-row" onclick="toggleLinhaPedido('${subGroupId}', this)">
                <td class="expand-col">
                    <button class="baixa-btn-expand ${expandedCls}" title="Ver parcelas">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                    </button>
                </td>
                <td><strong>${nomeGrupo}</strong></td>
                <td class="text-center"><span class="baixa-badge-parcelas">${g.parcelas.length}</span></td>
                <td class="td-valor-grupo" onclick="event.stopPropagation();">
                    <div class="baixa-valor-devendo">${formatCurrency(g.totalDevendo)}</div>
                    <label class="baixa-check-label">
                        <input type="checkbox" id="check-grupo-${gIdx}" class="checkbox-grupo-baixa baixa-check-grupo" onchange="toggleBaixaGrupo(${gIdx}, this.checked)">
                        Baixar Tudo
                    </label>
                </td>
            </tr>
        `;

        html += `
            <tr class="baixa-row-detalhe" id="parcelas_${subGroupId}" style="${dNoneStyle}">
                <td colspan="4">
                    <div class="baixa-detalhe-inner">
        `;

        g.parcelas.forEach(p => {
            const idIdentificadorParcela = p.ID_PARCELA || p.ID || p.ID_PEDIDO;

            html += `
                        <div class="baixa-parcela-item">
                            <div class="baixa-parcela-info">
                                <strong class="baixa-parcela-venc">Venc: ${formatDate(p.DATA_VENCIMENTO_MIN || p.DATA_VENCIMENTO)}</strong>
                                <span class="baixa-parcela-falta">Falta: ${formatCurrency(p._devendo)}</span>
                            </div>
                            <div class="baixa-parcela-actions">
                                <div class="baixa-input-wrapper">
                                    <span class="baixa-input-prefix">R$</span>
                                    <input type="number" step="0.01" min="0"
                                        data-max="${p._devendo.toFixed(2)}"
                                        class="input-baixa-valor grupo-input-${gIdx}"
                                        id="input-baixa-${p._idx}"
                                        data-id="${idIdentificadorParcela}"
                                        data-idx="${p._idx}"
                                        value="0.00"
                                        oninput="atualizarTotalBaixa()">
                                </div>
                                <label class="baixa-check-parcela-label">
                                    <input type="checkbox"
                                        id="check-parcela-${p._idx}"
                                        class="checkbox-parcela-${gIdx}"
                                        onchange="toggleBaixaParcela(${p._idx}, ${p._devendo.toFixed(2)}, this.checked)"
                                        title="Preencher Total">
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

    html += `</tbody></table>`;
    container.innerHTML = html;
    atualizarTotalBaixa();
}


function toggleLinhaPedido(subGroupId, trElement) {
    const detailRow = document.getElementById(`parcelas_${subGroupId}`);
    const btnExpand = trElement.querySelector('.baixa-btn-expand');
    if (detailRow.style.display === 'none') {
        detailRow.style.display = '';
        if (btnExpand) btnExpand.classList.add('expanded');
    } else {
        detailRow.style.display = 'none';
        if (btnExpand) btnExpand.classList.remove('expanded');
    }
}

function toggleBaixarTudoCheckbox(isChecked) {
    document.querySelectorAll('.checkbox-grupo-baixa').forEach(chk => {
        chk.checked = isChecked;
    });
    document.querySelectorAll('.input-baixa-valor').forEach(input => {
        input.value = isChecked ? parseFloat(input.getAttribute('data-max')).toFixed(2) : '0.00';
    });
    atualizarTotalBaixa();
}

function toggleBaixaGrupo(gIdx, isChecked) {
    document.querySelectorAll(`.grupo-input-${gIdx}`).forEach(input => {
        input.value = isChecked ? parseFloat(input.getAttribute('data-max')).toFixed(2) : '0.00';
    });
    document.querySelectorAll(`.checkbox-parcela-${gIdx}`).forEach(chk => {
        chk.checked = isChecked;
    });
    atualizarTotalBaixa();
}

function toggleBaixaParcela(idx, maxValor, isChecked) {
    const input = document.getElementById(`input-baixa-${idx}`);
    if (input) {
        input.value = isChecked ? maxValor.toFixed(2) : '0.00';
        atualizarTotalBaixa();
    }
}

function baixarTodasParcelas() {
    document.querySelectorAll('.input-baixa-valor').forEach(input => {
        input.value = parseFloat(input.getAttribute('data-max')).toFixed(2);
    });
    atualizarTotalBaixa();
}

function atualizarTotalBaixa() {
    let total = 0;
    document.querySelectorAll('.input-baixa-valor').forEach(input => {
        total += parseFloat(input.value || 0);
    });

    const display = document.getElementById('baixa-total-display') || document.getElementById('modal-baixa-total-label');
    if (display) display.textContent = formatCurrency(total);
}

function confirmarBaixa() {
    const inputs = document.querySelectorAll('.input-baixa-valor');
    const baixas = [];
    let hasInvalid = false;
    let hasOverpaid = false;

    inputs.forEach(input => {
        const val = parseFloat(input.value || 0);
        const max = parseFloat(input.getAttribute('data-max') || 0);

        if (val < 0) {
            hasInvalid = true;
            input.style.borderColor = 'red';
        } else if (val > max) {
            hasOverpaid = true;
            input.style.borderColor = 'red';
        } else {
            input.style.borderColor = '#cbd5e1';
        }

        if (val > 0 && val <= max) {
            baixas.push({
                id: parseInt(input.getAttribute('data-id')),
                valor: val
            });
        }
    });

    if (hasInvalid) {
        alert("Valor inválido. Não é possível inserir valores negativos.");
        return;
    }

    if (hasOverpaid) {
        alert("Valor inválido. O valor informado é maior do que o saldo devedor da parcela.");
        return;
    }

    if (baixas.length === 0) {
        alert("Nenhum valor válido a baixar.");
        return;
    }

    const btnConfirma = document.querySelector('#modal-baixa-manual .btn-modal-confirm-blue');
    let oldText = '';
    if (btnConfirma) {
        oldText = btnConfirma.innerHTML;
        btnConfirma.innerHTML = 'Baixando...';
        btnConfirma.disabled = true;
    }

    const selectColab = document.getElementById('modal-colaborador-select');
    const colaboradorId = selectColab ? selectColab.value : null;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch(getBaseUrl() + '/contas-receber/baixar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ baixas, colaborador_id: colaboradorId })
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                fecharModalBaixa();
                window.location.reload();
            } else {
                alert('Erro: ' + (res.error || 'Erro desconhecido'));
                if (btnConfirma) {
                    btnConfirma.innerHTML = oldText;
                    btnConfirma.disabled = false;
                }
            }
        })
        .catch(err => {
            alert('Erro de conexão ao salvar.');
            if (btnConfirma) {
                btnConfirma.innerHTML = oldText;
                btnConfirma.disabled = false;
            }
        });
}