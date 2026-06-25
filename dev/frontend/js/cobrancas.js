/* ────────────────────────────────────────────────────────
   COBRANÇAS — JavaScript
   Espelha o visual do Contas a Receber mas mantém a lógica
   de puxar/assumir cobranças.
──────────────────────────────────────────────────────── */

const BASE = document.querySelector('meta[name="base-url"]').getAttribute('content');

// Lê os dados iniciais do atributo data-initial-state do wrapper
const INITIAL_DATA = JSON.parse(document.querySelector('.cr-wrapper').getAttribute('data-initial-state'));

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tbody-padrao'))    renderPadrao();
    if (document.getElementById('tbody-financeiro')) renderFinanceiro();

    setupModalCobranca();
    setupModalDetalhes();
});

/* ── Helpers de formatação ──────────────────────────────────── */
function fmtMoney(v) {
    if (!v) return 'R$ 0,00';
    return parseFloat(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function fmtDate(d) {
    if (!d || d === '0000-00-00') return 'N/A';
    const [y, m, dd] = d.split('-');
    return `${dd}/${m}/${y}`;
}

/* ── Badge de status da cobrança ─────────────────────────────── */
function badgeStatus(item, tipo) {
    const ativas = INITIAL_DATA.cobrancasAtivas[tipo] || {};
    
    let id;
    if (tipo === 'financeiros') id = item.ID_CF;
    else if (tipo === 'representantes') id = item.ID_CONTATO_BLING;
    else id = item.ID_CONTATO_BLING;

    const cob = ativas[id] || ativas[String(id)];
    if (cob) {
        return `<span class="badge-em-cobranca" title="Cobrança Ativa">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    ${cob.NOME_COLABORADOR}
                </span>`;
    }

    if (tipo === 'representantes' || tipo === 'financeiros') {
        let cobradores = [];
        
        if (item.IDS_CLIENTES) {
            const clientesAtivos = (INITIAL_DATA.cobrancasAtivas || {})['clientes'] || {};
            const idsClientes = item.IDS_CLIENTES.split(',').map(i => i.trim()).filter(i => i);
            idsClientes.forEach(cid => {
                const cCob = clientesAtivos[cid] || clientesAtivos[String(cid)];
                if (cCob) {
                    cobradores.push(cCob.NOME_COLABORADOR.split(' ')[0] + " (Cl.)");
                }
            });
        }
        
        if (tipo === 'financeiros' && item.IDS_REPRESENTANTES) {
            const repsAtivos = (INITIAL_DATA.cobrancasAtivas || {})['representantes'] || {};
            const idsReps = item.IDS_REPRESENTANTES.split(',').map(i => i.trim()).filter(i => i);
            idsReps.forEach(rid => {
                const rCob = repsAtivos[rid] || repsAtivos[String(rid)];
                if (rCob) {
                    cobradores.push(rCob.NOME_COLABORADOR.split(' ')[0] + " (Rep.)");
                }
            });
        }

        if (cobradores.length > 0) {
            cobradores = [...new Set(cobradores)];
            return `<span class="badge-em-cobranca" style="background-color:#fdf4ff; color:#a21caf; border: 1px solid #f0abfc;" title="Vinculados em andamento">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Vinculado c/ ${cobradores.join(', ')}
                    </span>`;
        }
    }

    return `<span class="badge-disponivel">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11"><polyline points="20 6 9 17 4 12"/></svg>
                Disponível
            </span>`;
}

/* ── Botão Cobrar ─────────────────────────────────────────────── */
function btnCobrar(item, tipo, nome = '') {
    if (nome && nome.toLowerCase().includes('sac por')) return '';
    const ativas = INITIAL_DATA.cobrancasAtivas[tipo] || {};
    
    let id;
    if (tipo === 'financeiros') id = item.ID_CF;
    else if (tipo === 'representantes') id = item.ID_CONTATO_BLING;
    else id = item.ID_CONTATO_BLING;

    let allCharged = true;
    let hasItems = false;
    
    if (tipo === 'representantes' || tipo === 'financeiros') {
        if (item.IDS_CLIENTES) {
            hasItems = true;
            const clientesAtivos = (INITIAL_DATA.cobrancasAtivas || {})['clientes'] || {};
            const idsClientes = item.IDS_CLIENTES.split(',').map(i => i.trim()).filter(i => i);
            if (!idsClientes.every(cid => clientesAtivos[cid] || clientesAtivos[String(cid)])) {
                allCharged = false;
            }
        }
        if (tipo === 'financeiros' && item.IDS_REPRESENTANTES) {
            hasItems = true;
            const repsAtivos = (INITIAL_DATA.cobrancasAtivas || {})['representantes'] || {};
            const idsReps = item.IDS_REPRESENTANTES.split(',').map(i => i.trim()).filter(i => i);
            if (!idsReps.every(rid => repsAtivos[rid] || repsAtivos[String(rid)])) {
                allCharged = false;
            }
        }
    } else {
        // Se for cliente direto, hasItems é false e ele segue a lógica normal de ativas[id]
    }

    // Se for cliente direto e já estiver em cobrança, ou se for agrupamento e TUDO estiver em cobrança
    if ((!hasItems && ativas[id]) || (hasItems && allCharged)) {
        const tplDis = document.getElementById('tpl-btn-cobrar-disabled');
        return tplDis ? tplDis.innerHTML : '';
    }

    let textoBtn = ativas[id] ? 'Cobrar Restantes' : 'Cobrar';

    const tplEl = document.getElementById('tpl-btn-cobrar-cob');
    if (tplEl) {
        let tpl = tplEl.innerHTML;
        tpl = tpl.replace('{{id}}', id).replace('{{tipo}}', tipo).replace('{{textoBtn}}', textoBtn);
        return tpl;
    }
    return '';
}

/* ── Botão de expand (idêntico ao contas_receber) ────────────── */
function btnExpand(tipo, id) {
    return `<button class="btn-expand" onclick="toggleDetalhes('${tipo}', ${id}, this)" title="Ver detalhes">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>`;
}

/* ══════════════════════════════════════════════════════════════
   RENDERIZAÇÃO DAS TABELAS
══════════════════════════════════════════════════════════════ */
function renderPadrao() {
    const { aba, clientes, representantes } = INITIAL_DATA;
    const isRep = (aba === 'representantes');
    const data  = isRep ? representantes : clientes;
    const tipo  = isRep ? 'representantes' : 'clientes';
    const tbody = document.getElementById('tbody-padrao');

    let html = '';

    Object.values(data).forEach(item => {
        // PedidoModel retorna NOME_CONTATO para clientes, NOME_REPRESENTANTE para representantes
        // O ID é sempre ID_CONTATO_BLING nos dois casos
        const nome = isRep ? (item.NOME_REPRESENTANTE || 'Desconhecido') : (item.NOME_CONTATO || 'Desconhecido');
        const id   = item.ID_CONTATO_BLING;

        const clientesHtml = isRep
            ? `<td style="font-size:0.82rem; color:#64748b;">${item.NOMES_CLIENTES || '—'}</td>`
            : '';

        const temTelefone = parseInt(item.TEM_TELEFONE) === 1;
        const telConfirmado = parseInt(item.TELEFONE_CONFIRMADO) === 1;
        let telBadge = temTelefone 
            ? (telConfirmado 
                ? '<span style="background-color:#dcfce7; color:#166534; padding:2px 6px; border-radius:12px; font-size:0.75rem; font-weight:600; display:inline-flex; align-items:center;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>Confirmado</span>' 
                : '<span style="background-color:#fef08a; color:#854d0e; padding:2px 6px; border-radius:12px; font-size:0.75rem; font-weight:600; display:inline-flex; align-items:center;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>Requer tentativa</span>') 
            : '<span style="color:#94a3b8;font-weight:bold;">-</span>';

        html += `
            <tr class="expandable-row" data-id="${id}">
                <td class="expand-col">${btnExpand(tipo, id)}</td>
                <td class="nome-col" style="font-weight:600;">${nome}</td>
                ${clientesHtml}
                <td class="center-col">${item.QTD_CONTAS ?? 0}</td>
                <td class="valor-col">${fmtMoney(item.TOTAL_VALOR)}</td>
                <td class="date-col">${fmtDate(item.VENCIMENTO_MAIS_ANTIGO)}</td>
                <td>${badgeStatus(item, tipo)}</td>
                <td class="center-col">${telBadge}</td>
                <td class="cr-col-acoes">${btnCobrar(item, tipo, nome)}</td>
            </tr>
        `;
    });

    if (!html) {
        const cols = isRep ? 8 : 7;
        html = `<tr><td colspan="${cols}" class="text-center" style="padding:32px; color:#94a3b8;">Nenhum registro encontrado.</td></tr>`;
    }

    tbody.innerHTML = html;
}

function renderFinanceiro() {
    const { financeiros } = INITIAL_DATA;
    const tbody = document.getElementById('tbody-financeiro');
    let html = '';

    Object.values(financeiros).forEach(item => {
        // PedidoModel retorna ID_CF e NOME_CF para contatos financeiros
        const nome = item.NOME_CF || item.NOME_CONTATO || 'Desconhecido';
        const id   = item.ID_CF || item.ID_CONTATO;

        const temTelefone = parseInt(item.TEM_TELEFONE) === 1;
        const telConfirmado = parseInt(item.TELEFONE_CONFIRMADO) === 1;
        let telBadge = temTelefone 
            ? (telConfirmado 
                ? '<span style="background-color:#dcfce7; color:#166534; padding:2px 6px; border-radius:12px; font-size:0.75rem; font-weight:600; display:inline-flex; align-items:center;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>Confirmado</span>' 
                : '<span style="background-color:#fef08a; color:#854d0e; padding:2px 6px; border-radius:12px; font-size:0.75rem; font-weight:600; display:inline-flex; align-items:center;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>Requer tentativa</span>') 
            : '<span style="color:#94a3b8;font-weight:bold;">-</span>';

        html += `
            <tr class="expandable-row" data-id="${id}">
                <td class="expand-col">${btnExpand('financeiros', id)}</td>
                <td class="nome-col" style="font-weight:600;">${nome}</td>
                <td class="center-col">${item.QTD_CONTAS ?? 0}</td>
                <td class="valor-col">${fmtMoney(item.TOTAL_VALOR)}</td>
                <td class="date-col">${fmtDate(item.VENCIMENTO_MAIS_ANTIGO)}</td>
                <td>${badgeStatus(item, 'financeiros')}</td>
                <td style="font-size:0.88rem; text-align:center;">
                    <div style="margin-bottom:2px;">${item.NUM_TEL || ''}</div>
                    ${telBadge}
                </td>
                <td class="cr-col-acoes">${btnCobrar(item, 'financeiros', nome)}</td>
            </tr>
        `;
    });

    if (!html) {
        html = `<tr><td colspan="8" class="text-center" style="padding:32px; color:#94a3b8;">Nenhum contato financeiro encontrado.</td></tr>`;
    }

    tbody.innerHTML = html;
}

/* ══════════════════════════════════════════════════════════════
   MODAL DE DETALHES (espelho do contas_receber)
══════════════════════════════════════════════════════════════ */
function setupModalDetalhes() {
    const modal = document.getElementById('modal-detalhes');
    if (!modal) return;
    document.getElementById('modal-detalhes-close').addEventListener('click', () => modal.style.display = 'none');
    document.getElementById('modal-detalhes-overlay').addEventListener('click', () => modal.style.display = 'none');
}

function toggleDetalhes(tipo, id, btnEl) {
    // Animação da seta
    document.querySelectorAll('.btn-expand.expanded').forEach(b => {
        if (b !== btnEl) b.classList.remove('expanded');
    });
    btnEl.classList.toggle('expanded');

    const row    = btnEl.closest('tr');
    const nome   = row.querySelector('.nome-col').textContent.trim();
    const modal  = document.getElementById('modal-detalhes');
    const title  = document.getElementById('modal-detalhes-title');
    const body   = document.getElementById('modal-detalhes-body');

    title.textContent = nome;
    body.innerHTML    = `<div class="detail-content loading" style="text-align:center;padding:40px;">Carregando...</div>`;
    modal.style.display = 'flex';

    fetch(`${BASE}/contas-receber/api/lista?tipo=${tipo}&id=${id}`)
        .then(r => r.json())
        .then(resp => {
            const contas = resp.data || [];

            if (resp.error) {
                body.innerHTML = `<p style="text-align:center;padding:24px;color:#ef4444;">${resp.error}</p>`;
                return;
            }
            if (contas.length === 0) {
                body.innerHTML = `<p style="text-align:center;padding:24px;color:#94a3b8;">Nenhuma conta encontrada para este registro.</p>`;
                return;
            }

            if (resp.html) {
                body.innerHTML = resp.html;
            } else {
                body.innerHTML = `<p style="text-align:center;padding:24px;color:#94a3b8;">Nenhuma conta encontrada para este registro.</p>`;
            }
        })
        .catch(err => {
            console.error('toggleDetalhes error:', err);
            body.innerHTML = `<p style="text-align:center;padding:24px;color:#ef4444;">Erro ao carregar detalhes.</p>`;
        });
}

/* ══════════════════════════════════════════════════════════════
   MODAL DE SELEÇÃO / PUXAR COBRANÇA
══════════════════════════════════════════════════════════════ */
let cobrancaPendente = { id: null, tipo: null };

function setupModalCobranca() {
    const modal = document.getElementById('modal-cobranca-clientes');
    if (!modal) return;

    document.getElementById('modal-cobranca-close').addEventListener('click', () => modal.style.display = 'none');
    document.getElementById('modal-cobranca-overlay').addEventListener('click', () => modal.style.display = 'none');

    document.getElementById('check-all-clientes').addEventListener('change', e => {
        document.querySelectorAll('.check-cliente-cob').forEach(cb => cb.checked = e.target.checked);
    });

    document.getElementById('btn-confirmar-cobranca').addEventListener('click', confirmarCobranca);

    // Delegação — funciona para SVG interno também
    document.body.addEventListener('click', e => {
        const btn = e.target.closest('.btn-puxar');
        if (!btn) return;
        const id   = btn.getAttribute('data-id');
        const tipo = btn.getAttribute('data-tipo');
        iniciarCobranca(id, tipo);
    });
}

function iniciarCobranca(id, tipo) {
    if (tipo === 'clientes') {
        if (!confirm('Deseja assumir a cobrança deste cliente?')) return;
        enviarCobranca(tipo, id, [parseInt(id)]);
        return;
    }

    cobrancaPendente = { id, tipo };
    const tipoLabel = tipo === 'representantes' ? 'Representante' : 'Contato Financeiro';
    document.getElementById('modal-cobranca-tipo-texto').innerText = tipoLabel;
    document.getElementById('modal-cobranca-clientes').style.display = 'flex';
    carregarClientes(id, tipo);
}

function carregarClientes(id, tipo) {
    const container = document.getElementById('clientes-list-container');
    const loading   = document.getElementById('modal-cobranca-loading');
    container.innerHTML = '';
    loading.style.display = 'block';

    fetch(`${BASE}/cobrancas/api-clientes-agrupamento?tipo=${tipo}&id=${id}`)
        .then(r => r.json())
        .then(data => {
            loading.style.display = 'none';
            if (!data.success || !data.html) {
                container.innerHTML = `<p style="color:#ef4444;font-size:0.88rem;text-align:center;padding:16px;">${data.error || 'Nenhum cliente inadimplente encontrado.'}</p>`;
                return;
            }
            container.innerHTML = data.html;
            document.getElementById('check-all-clientes').checked = true;
        })
        .catch(() => {
            loading.style.display = 'none';
            container.innerHTML = `<p style="color:#ef4444;text-align:center;padding:16px;">Erro ao carregar clientes.</p>`;
        });
}

function confirmarCobranca() {
    const selecionados = [...document.querySelectorAll('.check-cliente-cob:checked')].map(c => parseInt(c.value));
    if (!selecionados.length) { alert('Selecione pelo menos um cliente.'); return; }

    const btn      = document.getElementById('btn-confirmar-cobranca');
    btn.disabled   = true;
    btn.innerText  = 'Processando...';

    enviarCobranca(cobrancaPendente.tipo, cobrancaPendente.id, selecionados)
        .finally(() => {
            btn.disabled  = false;
            btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><polyline points="20 6 9 17 4 12"/></svg> Confirmar e Assumir`;
            document.getElementById('modal-cobranca-clientes').style.display = 'none';
        });
}

function enviarCobranca(tipo, id, clientes) {
    return fetch(`${BASE}/cobrancas/puxar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tipo, id, clientes })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { alert('Cobrança assumida com sucesso!'); location.reload(); }
        else alert('Erro: ' + (data.error || 'Falha ao assumir cobrança.'));
    })
    .catch(() => alert('Erro de comunicação com o servidor.'));
}
