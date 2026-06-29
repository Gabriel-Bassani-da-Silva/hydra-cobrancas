// ── BAIXA MANUAL (VARIÁVEIS E FUNÇÕES AUXILIARES) ───────────────────────────
let parcelasBaixaAtual = [];

function getBaseUrl() {
    if (typeof BASE !== 'undefined') return BASE;
    if (typeof BASE_URL !== 'undefined') return BASE_URL;
    return document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
}

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

// ── FUNÇÕES DO MODAL DE BAIXA MANUAL ─────────────────────────────────────────
window.abrirModalBaixa = function (idsPedidos) {
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

    const modal = document.getElementById('modal-baixa-manual');
    if (modal) modal.style.display = 'flex';

    fetch(`${getBaseUrl()}/contas-receber/api/parcelas-por-pedidos?ids=${idsArray.join(',')}`)
        .then(r => r.json())
        .then(res => {
            if (res.error) {
                alert(res.error);
                return;
            }
            parcelasBaixaAtual = res.parcelas || [];
            renderParcelasModalBaixa();
        })
        .catch(err => {
            console.error(err);
            alert('Erro ao buscar parcelas para baixa.');
        });
}

function fecharModalBaixa() {
    const modal = document.getElementById('modal-baixa-manual');
    if (modal) modal.style.display = 'none';
}

function renderParcelasModalBaixa() {
    const container = document.getElementById('baixa-parcelas-container');
    if (!container) return;
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

    Object.values(grupos).forEach((g, gIdx) => {
        const nomeGrupo = g.numPedido && g.numPedido !== '—' ? `Ped. ${g.numPedido}` : 'Conta Avulsa';
        const subGroupId = `baixa_grupo_${gIdx}`;

        html += `
            <tr class="expandable-row" style="cursor:pointer; border-bottom: 1px solid #e2e8f0;">
                <td class="expand-col" style="padding: 10px;">
                    <button class="btn-expand" data-toggle-parcelas="${subGroupId}" style="background:none; border:none; cursor:pointer;" title="Ver parcelas">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                    </button>
                </td>
                <td><strong>${nomeGrupo}</strong></td>
                <td style="text-align: center;"><span style="font-size: 0.75rem; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 12px; font-weight: 600;">${g.parcelas.length}</span></td>
                <td style="text-align: right;">
                    <div style="color:#ef4444; font-weight: 600;">${formatCurrency(g.totalDevendo)}</div>
                    <label for="check-grupo-${gIdx}" style="display:inline-flex; align-items:center; gap:4px; cursor:pointer; font-size:0.75rem; color:#64748b; margin-top:2px;">
                        <input type="checkbox" id="check-grupo-${gIdx}" class="checkbox-grupo-baixa" onchange="toggleBaixaGrupo(${gIdx}, this.checked)" style="width:14px; height:14px; cursor:pointer; accent-color: #2563eb; margin:0;">
                        Baixar Tudo
                    </label>
                </td>
            </tr>
        `;

        html += `
            <tr class="detail-row d-none" id="parcelas_${subGroupId}">
                <td colspan="4" style="padding: 0;">
                    <div style="background: #f8fafc; padding: 10px 15px; border-bottom: 1px solid #e2e8f0; border-left: 3px solid #3b82f6;">
        `;

        g.parcelas.forEach(p => {
            const idIdentificadorParcela = p.ID_PARCELA || p.ID || p.ID_PEDIDO;
            html += `
                        <div style="display:flex; justify-content:space-between; align-items:center; padding: 8px 0; border-bottom: 1px dashed #cbd5e1;">
                            <div style="flex:1;">
                                <strong style="font-size:0.85rem; color:#475569;">Venc: ${formatDate(p.DATA_VENCIMENTO_MIN || p.DATA_VENCIMENTO)}</strong>
                                <span style="font-size:0.75rem; color:#64748b; margin-left:8px;">Falta: ${formatCurrency(p._devendo)}</span>
                            </div>
                            <div style="display:flex; align-items:center; gap: 8px; width: 220px;">
                                <div style="position:relative; flex:1;">
                                    <span style="position:absolute; left:8px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:0.85rem;">R$</span>
                                    <input type="number" step="0.01" min="0" data-max="${p._devendo.toFixed(2)}" class="input-baixa-valor grupo-input-${gIdx}" id="input-baixa-${p._idx}" data-id="${idIdentificadorParcela}" data-idx="${p._idx}" value="0.00" style="width:100%; padding:6px 6px 6px 26px; border:1px solid #cbd5e1; border-radius:4px; font-size:0.9rem; text-align:right; font-weight: 500;" oninput="atualizarTotalBaixa()">
                                </div>
                                <label for="check-parcela-${p._idx}" style="display:inline-flex; align-items:center; justify-content:center; cursor:pointer; padding: 4px; border-radius: 4px;">
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

    html += `</tbody></table>`;
    container.innerHTML = html;
    atualizarTotalBaixa();
}

function toggleBaixaGrupo(gIdx, isChecked) {
    const inputs = document.querySelectorAll(`.grupo-input-${gIdx}`);
    const checkboxes = document.querySelectorAll(`.checkbox-parcela-${gIdx}`);

    inputs.forEach(input => {
        const max = input.getAttribute('data-max');
        input.value = isChecked ? parseFloat(max).toFixed(2) : "0.00";
    });

    checkboxes.forEach(cb => {
        cb.checked = isChecked;
    });

    atualizarTotalBaixa();
}

function toggleBaixaParcela(idx, valorMax, isChecked) {
    const input = document.getElementById(`input-baixa-${idx}`);
    if (input) {
        input.value = isChecked ? parseFloat(valorMax).toFixed(2) : "0.00";
    }
    atualizarTotalBaixa();
}

function atualizarTotalBaixa() {
    let total = 0;
    document.querySelectorAll('.input-baixa-valor').forEach(input => {
        const val = parseFloat(input.value);
        if (!isNaN(val) && val > 0) total += val;
    });

    const labelTotal = document.getElementById('modal-baixa-total-label');
    if (labelTotal) labelTotal.innerHTML = formatCurrency(total);
}

function confirmarBaixaManual() {
    let baixas = [];
    let erroValidacao = false;

    document.querySelectorAll('.input-baixa-valor').forEach(input => {
        const val = parseFloat(input.value);
        const max = parseFloat(input.getAttribute('data-max') || 0);
        const id = input.getAttribute('data-id');

        if (!isNaN(val) && val > 0) {
            if (val > max) {
                erroValidacao = true;
            }
            baixas.push({ id_parcela: id, valor: val });
        }
    });

    if (erroValidacao) {
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

// ── LÓGICA DE INTERAÇÃO COM O DOM (TABELAS, PÁGINA E BUSCA) ───────────────────
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('cr-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const val = this.value.toLowerCase();
            const rows = document.querySelectorAll('#table-minhas-cobrancas tbody tr.clickable-row');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(val)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Configuração unificada para Fechar o Modal de Detalhes do Perfil
    const modalDetalhes = document.getElementById('modal-detalhes');
    const btnCloseModal = document.getElementById('modal-detalhes-close');
    const overlayModal = document.querySelector('.cr-modal-overlay');

    if (modalDetalhes) {
        const closeModal = () => {
            modalDetalhes.style.display = 'none';
            const body = document.getElementById('modal-detalhes-body');
            if (body) body.innerHTML = '';
            document.querySelectorAll('.btn-expand.expanded').forEach(b => b.classList.remove('expanded'));
        };

        if (btnCloseModal) btnCloseModal.addEventListener('click', closeModal);
        if (overlayModal) overlayModal.addEventListener('click', closeModal);
    }

    // Dropdown hover effect
    const hoverEls = document.querySelectorAll('.dropdown-hover');
    hoverEls.forEach(el => {
        el.addEventListener('mouseenter', () => {
            const content = el.querySelector('.dropdown-content');
            if (content) content.style.display = 'block';
        });
        el.addEventListener('mouseleave', () => {
            const content = el.querySelector('.dropdown-content');
            if (content) content.style.display = 'none';
        });
    });

    // Atualizar Cobrança
    const btnAtualizar = document.querySelectorAll('.btn-atualizar-cob');
    btnAtualizar.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const idCobranca = e.currentTarget.getAttribute('data-id');
            e.currentTarget.disabled = true;
            e.currentTarget.innerHTML = 'Atualizando...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch(`${getBaseUrl()}/cobranca/atualizar-pedidos`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ id_cobranca: idCobranca })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Pedidos da cobrança atualizados com sucesso!');
                        window.location.reload();
                    } else {
                        alert('Erro ao atualizar: ' + (data.error || 'Desconhecido'));
                        e.currentTarget.disabled = false;
                        e.currentTarget.innerHTML = 'Atualizar Pedidos';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Erro de comunicação.');
                    e.currentTarget.disabled = false;
                    e.currentTarget.innerHTML = 'Atualizar Pedidos';
                });
        });
    });

    // Desistir Cobrança
    const btnDesistir = document.querySelectorAll('.btn-desistir-cob');
    btnDesistir.forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('Tem certeza que deseja desistir desta cobrança?')) return;

            const idCobranca = e.currentTarget.getAttribute('data-id');
            e.currentTarget.disabled = true;
            e.currentTarget.innerHTML = 'Cancelando...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch(`${getBaseUrl()}/cobranca/desistir`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ id_cobranca: idCobranca })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Cobrança cancelada com sucesso!');
                        window.location.reload();
                    } else {
                        alert('Erro ao cancelar: ' + (data.error || 'Desconhecido'));
                        e.currentTarget.disabled = false;
                        e.currentTarget.innerHTML = 'Desistir';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Erro de comunicação.');
                    e.currentTarget.disabled = false;
                    e.currentTarget.innerHTML = 'Desistir';
                });
        });
    });

    // Expandir linha da tabela de cobranças
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('.btn-atualizar-cob') || e.target.closest('.btn-desistir-cob') || e.target.closest('.cr-col-acoes') || e.target.closest('.dropdown-content') || e.target.closest('.checkbox-grupo-baixa') || e.target.closest('.input-baixa-valor') || e.target.closest('.checkbox-grupo-baixa') || e.target.closest('label')) {
                return;
            }
            e.stopPropagation();
            const idCobranca = this.getAttribute('data-id');
            const nomeCobranca = this.getAttribute('data-nome');
            const tipoCobranca = this.getAttribute('data-tipo');
            const btnExpand = this.querySelector('.btn-expand');
            toggleDetalhesPerfil(btnExpand, idCobranca, nomeCobranca, tipoCobranca);
        });
    });

    // Delegação global para botões de abrir/fechar sub-parcelas dentro do modal genérico
    document.addEventListener('click', function (e) {
        let btnParcelas = e.target.closest('[data-toggle-parcelas]');

        if (!btnParcelas) {
            const row = e.target.closest('tr.expandable-row');
            if (row) {
                btnParcelas = row.querySelector('[data-toggle-parcelas]');
            }
        }

        if (btnParcelas) {
            if (e.target.closest('a.btn-action-icon') || e.target.closest('a.btn-action-icon-sm') || e.target.closest('input') || e.target.closest('label')) return;
            e.preventDefault();
            e.stopPropagation();

            const targetId = btnParcelas.getAttribute('data-toggle-parcelas');
            const detailRow = document.getElementById('parcelas_' + targetId);
            const iconBtn = btnParcelas.closest('.expandable-row')?.querySelector('.btn-expand');

            if (detailRow) {
                if (detailRow.classList.contains('d-none') || detailRow.style.display === 'none') {
                    detailRow.classList.remove('d-none');
                    detailRow.style.display = '';
                    if (iconBtn) iconBtn.style.transform = 'rotate(90deg)';
                } else {
                    detailRow.classList.add('d-none');
                    detailRow.style.display = 'none';
                    if (iconBtn) iconBtn.style.transform = '';
                }
            }
        }
    });
});

function toggleAcoesMenu(btn, event) {
    event.stopPropagation();
    document.querySelectorAll('.action-menu-dropdown').forEach(menu => {
        if (menu !== btn.nextElementSibling) {
            menu.style.display = 'none';
        }
    });
    const dropdown = btn.nextElementSibling;
    dropdown.style.display = dropdown.style.display === 'none' || dropdown.style.display === '' ? 'block' : 'none';
}

document.addEventListener('click', () => {
    document.querySelectorAll('.action-menu-dropdown').forEach(menu => {
        menu.style.display = 'none';
    });
});

window.toggleDetalhesPerfil = function (btnEl, id, nome, tipo) {
    if (!btnEl) return;

    document.querySelectorAll('.btn-expand.expanded').forEach(b => {
        if (b !== btnEl) b.classList.remove('expanded');
    });
    btnEl.classList.toggle('expanded');

    const modal = document.getElementById('modal-detalhes');
    const title = document.getElementById('modal-detalhes-title');
    const body = document.getElementById('modal-detalhes-body');

    title.textContent = "Pedidos de: " + nome;
    body.innerHTML = `<div class="detail-content loading" style="text-align:center;padding:40px;">Carregando...</div>`;
    modal.style.display = 'flex';

    fetch(`${getBaseUrl()}/perfil/api/pedidos?id=${id}&tipo=${tipo}`)
        .then(r => r.json())
        .then(resp => {
            if (resp.error) {
                body.innerHTML = `<p style="text-align:center;padding:24px;color:#ef4444;">${resp.error}</p>`;
                return;
            }
            if (resp.html) {
                body.innerHTML = resp.html;
            } else {
                body.innerHTML = `<p style="text-align:center;padding:24px;color:#94a3b8;">Nenhum detalhe encontrado.</p>`;
            }
        })
        .catch(err => {
            console.error('toggleDetalhes error:', err);
            body.innerHTML = `<p style="text-align:center;padding:24px;color:#ef4444;">Erro ao carregar detalhes.</p>`;
        });
}

// ── FUNÇÕES PARA EDIÇÃO E ESTORNO DE BAIXAS LOCAIS ───────────────────────────
function editarBaixa(idDetalhe) {
    document.getElementById('valor-display-' + idDetalhe).style.display = 'none';
    document.getElementById('input-baixa-' + idDetalhe).style.display = 'inline-block';
    document.getElementById('input-baixa-' + idDetalhe).focus();

    document.getElementById('btn-edit-' + idDetalhe).style.display = 'none';
    document.getElementById('btn-save-' + idDetalhe).style.display = 'inline-block';
    document.getElementById('btn-cancel-' + idDetalhe).style.display = 'inline-block';
}

function cancelarEdicaoBaixa(idDetalhe) {
    document.getElementById('valor-display-' + idDetalhe).style.display = 'inline-block';
    document.getElementById('input-baixa-' + idDetalhe).style.display = 'none';

    document.getElementById('btn-edit-' + idDetalhe).style.display = 'inline-block';
    document.getElementById('btn-save-' + idDetalhe).style.display = 'none';
    document.getElementById('btn-cancel-' + idDetalhe).style.display = 'none';
}

function salvarBaixa(idDetalhe) {
    const input = document.getElementById('input-baixa-' + idDetalhe);
    const novoValor = parseFloat(input.value);

    if (isNaN(novoValor) || novoValor < 0) {
        alert("Valor inválido.");
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch(getBaseUrl() + '/baixas/editar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            id_detalhe: idDetalhe,
            valor: novoValor
        })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Erro: ' + (data.error || 'Falha ao editar baixa.'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro ao editar baixa.');
        });
}

function estornarBaixa(idDetalhe) {
    if (!confirm("Deseja estornar (apagar) este registro de baixa local?")) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch(getBaseUrl() + '/baixas/estornar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            id_detalhe: idDetalhe
        })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Erro: ' + (data.error || 'Falha ao estornar baixa.'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro ao estornar baixa.');
        });
}

let carregandoBaixas = false;

function abrirModalBaixas(idCliente) {
    if (carregandoBaixas) return;

    const modal = document.getElementById('modal-detalhes');
    if (!modal) return;

    const title = document.getElementById('modal-detalhes-title');
    const body = document.getElementById('modal-detalhes-body');

    carregandoBaixas = true;
    title.innerText = 'Minhas Baixas do Cliente';
    body.innerHTML = '<div class="text-center" style="padding: 20px;">Carregando baixas...</div>';
    modal.style.display = 'flex';

    fetch(`${getBaseUrl()}/perfil/api-baixas-colaborador?id=${idCliente}`)
        .then(res => res.json())
        .then(data => {
            if (data.html) {
                body.innerHTML = data.html;
            } else {
                body.innerHTML = '<div class="text-center" style="padding: 20px; color: red;">' + (data.error || 'Erro ao carregar') + '</div>';
            }
        })
        .catch(err => {
            console.error(err);
            body.innerHTML = '<div class="text-center" style="padding: 20px; color: red;">Erro na requisição.</div>';
        })
        .finally(() => {
            carregandoBaixas = false;
        });
}