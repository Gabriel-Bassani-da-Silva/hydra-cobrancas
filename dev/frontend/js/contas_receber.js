/**
 * contas_receber.js — Vanilla JS para a página de Contas a Receber
 * Gerencia busca, expansão de detalhes, sincronização e renderização do DOM.
 */

const BASE = document.querySelector('meta[name="base-url"]')?.content
           || window.location.pathname.replace(/\/contas-receber.*/, '')
           || '/hydraRemake';

// Lê os dados iniciais do atributo data-initial-state do wrapper
const INITIAL_DATA = JSON.parse(document.querySelector('.cr-wrapper').getAttribute('data-initial-state'));

// ── Formatação ────────────────────────────────────────────────────────────
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value || 0);
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '—';
    const [y, m, d] = dateStr.split(' ')[0].split('-'); // handle datetime as well
    return `${d}/${m}/${y}`;
}

function getSituacaoLabel(situacao) {
    switch (parseInt(situacao)) {
        case 1: return '<span class="status-badge status-aberto">Em Aberto</span>';
        case 2: return '<span class="status-badge status-pago">Pago</span>';
        case 3: return '<span class="status-badge status-parcial">Parcial</span>';
        default: return `<span class="status-badge">${situacao}</span>`;
    }
}

function getModernBadge(situacao) {
    switch (parseInt(situacao)) {
        case 1: return '<span class="modern-badge badge-warning"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Em Aberto</span>';
        case 2: return '<span class="modern-badge badge-success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Pago</span>';
        case 3: return '<span class="modern-badge badge-info"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg> Parcial</span>';
        default: return `<span class="modern-badge">${situacao}</span>`;
    }
}

function getSmallBadge(situacao) {
    switch (parseInt(situacao)) {
        case 1: return '<span class="status-badge status-aberto text-xs">Em Aberto</span>';
        case 2: return '<span class="status-badge status-pago text-xs">Pago</span>';
        case 3: return '<span class="status-badge status-parcial text-xs">Parcial</span>';
        default: return `<span class="status-badge text-xs">${situacao}</span>`;
    }
}

// ── Renderização Inicial e Ordenação ──────────────────────────────────────
let currentDataToRender = [];
let filtroCobranca = 'sem'; // 'sem' = apenas sem cobrança ativa | 'todos'

function getActiveDataArray() {
    const { aba, grupo, clientes, representantes, financeiros, pedidos } = INITIAL_DATA;
    let data;
    if ((aba === 'clientes' || aba === 'representantes') && (grupo === 'padrao' || grupo === 'cheques' || grupo === 'antecipados')) {
        data = aba === 'clientes' ? clientes : representantes;
    } else if ((aba === 'clientes' || aba === 'representantes') && grupo === 'financeiro') {
        data = financeiros;
    } else if (aba === 'pedidos') {
        data = pedidos;
    } else {
        data = [];
    }
    return Array.isArray(data) ? data : Object.values(data);
}

function renderCurrentView(data) {
    const { aba, grupo, isPagos } = INITIAL_DATA;
    if ((aba === 'clientes' || aba === 'representantes') && (grupo === 'padrao' || grupo === 'cheques' || grupo === 'antecipados')) {
        renderPadrao(aba, data);
    } else if ((aba === 'clientes' || aba === 'representantes') && grupo === 'financeiro') {
        renderFinanceiro(data);
    } else if (aba === 'pedidos') {
        renderPedidos(data, isPagos);
    }
}

let currentSortColumn = '';
let currentSortDirection = 'asc';

function applySort(data, sortBy, direction) {
    if (!sortBy || !data || data.length === 0) return data;
    
    const { aba, isPagos } = INITIAL_DATA;
    const isPedidos = aba === 'pedidos';
    
    return [...data].sort((a, b) => {
        let valA, valB;

        if (sortBy === 'az') {
            valA = (isPedidos ? a.NOME_CLIENTE : (a.NOME_CONTATO || a.NOME_REPRESENTANTE || a.NOME_CF || '')).toLowerCase();
            valB = (isPedidos ? b.NOME_CLIENTE : (b.NOME_CONTATO || b.NOME_REPRESENTANTE || b.NOME_CF || '')).toLowerCase();
        } else if (sortBy === 'qtd') {
            valA = isPedidos ? (a.PARCELAS ? a.PARCELAS.length : 0) : parseInt(a.QTD_CONTAS || 0);
            valB = isPedidos ? (b.PARCELAS ? b.PARCELAS.length : 0) : parseInt(b.QTD_CONTAS || 0);
        } else if (sortBy === 'valor') {
            valA = isPedidos ? (isPagos ? a.TOTAL_PEDIDO : (a.TOTAL_PEDIDO - a.VALOR_PAGO)) : parseFloat(a.TOTAL_VALOR || 0);
            valB = isPedidos ? (isPagos ? b.TOTAL_PEDIDO : (b.TOTAL_PEDIDO - b.VALOR_PAGO)) : parseFloat(b.TOTAL_VALOR || 0);
        } else if (sortBy === 'venc') {
            valA = isPedidos ? a.DATA_VENCIMENTO_MIN : a.VENCIMENTO_MAIS_ANTIGO;
            valB = isPedidos ? b.DATA_VENCIMENTO_MIN : b.VENCIMENTO_MAIS_ANTIGO;
            if (!valA || valA === '0000-00-00') valA = '9999-99-99'; // Tratar nulos
            if (!valB || valB === '0000-00-00') valB = '9999-99-99';
        } else if (sortBy === 'tel') {
            // Pesos: 3 = Requer tentativa (Topo no DESC), 2 = Confirmado (Meio), 1 = Sem telefone (Topo no ASC)
            const getTelVal = item => parseInt(item.TEM_TELEFONE || 0) === 1 ? (parseInt(item.TELEFONE_CONFIRMADO || 0) === 1 ? 2 : 3) : 1;
            valA = getTelVal(a);
            valB = getTelVal(b);
        } else if (sortBy === 'situacao') {
            valA = parseInt(a.SITUACAO_PEDIDO || 0);
            valB = parseInt(b.SITUACAO_PEDIDO || 0);
            if (valA === valB) {
                // Secondary sort by date
                let dA = isPedidos ? a.DATA_VENCIMENTO_MIN : a.VENCIMENTO_MAIS_ANTIGO;
                let dB = isPedidos ? b.DATA_VENCIMENTO_MIN : b.VENCIMENTO_MAIS_ANTIGO;
                if (!dA || dA === '0000-00-00') dA = '9999-99-99';
                if (!dB || dB === '0000-00-00') dB = '9999-99-99';
                if (dA < dB) return direction === 'asc' ? -1 : 1;
                if (dA > dB) return direction === 'asc' ? 1 : -1;
                return 0;
            }
        }
        
        if (valA < valB) return direction === 'asc' ? -1 : 1;
        if (valA > valB) return direction === 'asc' ? 1 : -1;
        return 0;
    });
}

function getFilteredData(data) {
    if (!data) return [];
    let filtered = [...data];

    // Filtro de cobrança: oculta os que já têm alguém cobrando
    const { aba, grupo, cobrancasAtivas } = INITIAL_DATA;
    const isPedidos = aba === 'pedidos';

    if (!isPedidos && filtroCobranca === 'sem' && cobrancasAtivas) {
        const tipoAtivo = grupo === 'financeiro' ? 'financeiros' : aba;
        const ativas = cobrancasAtivas[tipoAtivo] || {};

        filtered = filtered.filter(item => {
            // Determina o ID correto por tipo
            let id;
            if (tipoAtivo === 'financeiros') id = item.ID_CF;
            else if (tipoAtivo === 'representantes') id = item.ID_CONTATO_BLING;
            else id = item.ID_CONTATO_BLING;

            let allCharged = true;
            let hasItems = false;

            if (tipoAtivo === 'representantes' || tipoAtivo === 'financeiros') {
                if (item.IDS_CLIENTES) {
                    hasItems = true;
                    const idsClientes = item.IDS_CLIENTES.split(',').map(i => i.trim()).filter(i => i);
                    const clientesAtivos = cobrancasAtivas['clientes'] || {};
                    if (!idsClientes.every(cid => clientesAtivos[cid] || clientesAtivos[String(cid)])) {
                        allCharged = false;
                    }
                }
                if (tipoAtivo === 'financeiros' && item.IDS_REPRESENTANTES) {
                    hasItems = true;
                    const idsReps = item.IDS_REPRESENTANTES.split(',').map(i => i.trim()).filter(i => i);
                    const repsAtivos = cobrancasAtivas['representantes'] || {};
                    if (!idsReps.every(rid => repsAtivos[rid] || repsAtivos[String(rid)])) {
                        allCharged = false;
                    }
                }
            }

            // Oculta APENAS SE: (não tem sub-itens e ele próprio está cobrado) OU (tem sub-itens e todos estão cobrados)
            if ((!hasItems && (ativas[id] || ativas[String(id)])) || (hasItems && allCharged)) {
                return false;
            }

            return true;
        });
    }
    return filtered;
}

function updateSortIcons() {
    const iconNeutral = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-left:4px;"><path d="M7 15l5 5 5-5"/><path d="M7 9l5-5 5 5"/></svg>`;
    const iconAsc = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-left:4px;"><polyline points="18 15 12 9 6 15"></polyline></svg>`;
    const iconDesc = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-left:4px;"><polyline points="6 9 12 15 18 9"></polyline></svg>`;

    document.querySelectorAll('th.sortable .sort-icon').forEach(icon => {
        const th = icon.closest('th');
        const sortBy = th.getAttribute('data-sort');
        
        if (currentSortColumn === sortBy && currentSortDirection !== 'none') {
            icon.innerHTML = currentSortDirection === 'asc' ? iconAsc : iconDesc;
        } else {
            icon.innerHTML = iconNeutral;
        }
    });
}

function renderAndSort() {
    let dataToRender = getFilteredData(getActiveDataArray());
    if (currentSortColumn && currentSortDirection !== 'none') {
        dataToRender = applySort(dataToRender, currentSortColumn, currentSortDirection);
    }
    currentDataToRender = dataToRender;
    renderCurrentView(dataToRender);
    
    const searchInput = document.getElementById('cr-search');
    if (searchInput && searchInput.value.trim() !== '') {
        searchInput.dispatchEvent(new Event('input'));
    }
    
    if (filtroCobranca === 'sem') {
        document.querySelectorAll('.col-status-cobranca').forEach(el => el.classList.add('d-none'));
        document.querySelectorAll('.col-telefone').forEach(el => el.classList.remove('d-none'));
    } else {
        document.querySelectorAll('.col-status-cobranca').forEach(el => el.classList.remove('d-none'));
        document.querySelectorAll('.col-telefone').forEach(el => el.classList.add('d-none'));
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof INITIAL_DATA === 'undefined') return;

    // Fechar Modal
    const modalDetalhes = document.getElementById('modal-detalhes');
    const btnCloseModal = document.getElementById('modal-detalhes-close');
    const overlayModal = document.querySelector('.cr-modal-overlay');

    if (modalDetalhes && btnCloseModal && overlayModal) {
        const closeModal = () => {
            modalDetalhes.style.display = 'none';
            document.getElementById('modal-detalhes-body').innerHTML = '';
        };
        btnCloseModal.addEventListener('click', closeModal);
        overlayModal.addEventListener('click', closeModal);
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalDetalhes.style.display === 'flex') {
                closeModal();
            }
        });
    }

    renderAndSort();
    updateSortIcons();



    // Adiciona evento de clique nos cabeçalhos ordenáveis
    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const sortBy = th.getAttribute('data-sort');
            
            if (currentSortColumn === sortBy) {
                // Ciclo: asc -> desc -> none
                if (currentSortDirection === 'asc') {
                    currentSortDirection = 'desc';
                } else if (currentSortDirection === 'desc') {
                    currentSortDirection = 'none';
                    currentSortColumn = '';
                } else {
                    currentSortDirection = 'asc';
                }
            } else {
                // Nova coluna, define direção padrão como 'asc'
                currentSortColumn = sortBy;
                currentSortDirection = 'asc';
            }

            updateSortIcons();
            renderAndSort();
        });
    });
});

function getSyncButtonHtml(url, title, isSmall = false) {
    const className = isSmall ? 'btn-action-icon-sm' : 'btn-action-icon';
    const svgSize = isSmall ? '14' : '16';
    return `
        <a href="${url}" title="${title}" class="${className}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="${svgSize}" height="${svgSize}"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
        </a>
    `;
}

let menuCounter = 0;

function toggleAcoesMenu(btn, event) {
    event.stopPropagation();
    
    document.querySelectorAll('.dropdown-content').forEach(menu => {
        if (menu !== btn.nextElementSibling) {
            menu.style.display = 'none';
        }
    });

    const menu = btn.nextElementSibling;
    if (menu) {
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.btn-acoes-menu') && !event.target.closest('.dropdown-content')) {
        document.querySelectorAll('.dropdown-content').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

function getAcoesMenuHtml(syncUrl, syncTitle, cobrancaBtnHtml = '', idsBaixa = '') {
    menuCounter++;
    
    let cobrancaItemHtml = '';
    if (cobrancaBtnHtml && cobrancaBtnHtml.trim() !== '') {
        cobrancaItemHtml = `
            <li style="display:flex; justify-content:flex-start;">
                ${cobrancaBtnHtml}
            </li>
        `;
    }

    let baixaBtnHtml = '';
    if (idsBaixa) {
        baixaBtnHtml = `<li style="display:flex; justify-content:flex-start;"><button onclick="abrirModalBaixa('${idsBaixa}')" class="action-item" style="display:flex; align-items:center; gap:8px; padding:8px 16px; color:#334155; text-decoration:none; transition:background 0.2s; width:100%; border:none; background:transparent; cursor:pointer; text-align:left;" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> Dar Baixa Local</button></li>`;
    }

    return `
    <div style="position:relative; display:inline-block; text-align:left;">
        <button class="btn-acoes-menu" onclick="toggleAcoesMenu(this, event)" style="background:none; border:none; cursor:pointer; color:#64748b; padding:4px;" title="Ações">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <div class="dropdown-content" style="display:none; position:absolute; right:0; top:30px; background:#fff; border:1px solid #e2e8f0; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,0.1); z-index:99; width:max-content; overflow:hidden;">
            <ul style="list-style:none; padding:4px 0; margin:0; font-size:13px; text-align:left;">
                <li>
                    <a href="${syncUrl}" class="action-item" style="display:flex; align-items:center; gap:8px; padding:8px 16px; color:#334155; text-decoration:none; transition:background 0.2s;" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                        ${syncTitle}
                    </a>
                </li>
                ${cobrancaItemHtml}
                ${baixaBtnHtml}
            </ul>
        </div>
    </div>
    `;
}

function getExpandButtonHtml(dataType, dataId, extraClasses = '') {
    return `
        <button class="btn-expand ${extraClasses}" data-toggle-detalhes data-tipo="${dataType}" data-id="${dataId}" title="Ver detalhes">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M7 10l5 5 5-5z"/></svg>
        </button>
    `;
}

function renderPadrao(aba, data) {
    const tbody = document.getElementById('tbody-padrao');
    if (!tbody) return;

    if (!data || data.length === 0) {
        const cols = aba === 'clientes' ? 7 : 8;
        tbody.innerHTML = `<tr><td colspan="${cols}" class="empty-msg">Nenhum pedido pendente.</td></tr>`;
        return;
    }

    let html = '';
    data.forEach(item => {
        let nome = item.NOME_CONTATO || item.NOME_REPRESENTANTE || '—';
        const idBling = item.ID_CONTATO_BLING;
        const idsPedidos = item.IDS_PEDIDOS;
        
        let qtdContasHtml = item.QTD_CONTAS || 0;
        let btnExpandHtml = getExpandButtonHtml(aba, idBling);
        
        const isUmPedido = parseInt(item.QTD_CONTAS) === 1;
        const isUnicaParcela = parseInt(item.QTD_PARCELAS) === 1;

        if (isUmPedido && item.NUMEROS_PEDIDOS && item.NUMEROS_PEDIDOS !== '—') {
            const numeroUnico = item.NUMEROS_PEDIDOS.split(',')[0];
            nome += ` <small style="color: #777; font-size: 12px; margin-left: 6px;">(Ped. ${numeroUnico})</small>`;
            if (isUnicaParcela) btnExpandHtml = '';
        }
        
        let colsHtml = `
            <td class="expand-col">${btnExpandHtml}</td>
            <td class="nome-col">${nome}</td>
        `;

        if (aba === 'representantes') {
            colsHtml += `<td class="text-center">${item.QTD_CLIENTES || 0}</td>`;
        }

        const temTelefone = parseInt(item.TEM_TELEFONE) === 1;
        const telConfirmado = parseInt(item.TELEFONE_CONFIRMADO) === 1;
        let telBadge = temTelefone 
            ? (telConfirmado 
                ? '<span class="modern-badge badge-success" title="Telefone Confirmado" style="font-size:0.7rem;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:2px;"><polyline points="20 6 9 17 4 12"></polyline></svg>Confirmado</span>' 
                : '<span class="modern-badge badge-warning" title="Telefone Pendente" style="font-size:0.7rem;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>Requer tentativa</span>') 
            : '<span style="color:#94a3b8;font-weight:bold;">-</span>';

        colsHtml += `
            <td class="text-center">${qtdContasHtml}</td>
            <td class="valor-col">${formatCurrency(item.TOTAL_VALOR)}</td>
            <td class="date-col">${formatDate(item.VENCIMENTO_MAIS_ANTIGO)}</td>
            <td class="col-status-cobranca">${getCobrancaBadge(item, aba)}</td>
            <td class="text-center col-telefone">${telBadge}</td>
            <td class="cr-col-acoes" style="text-align:right;">
                ${getAcoesMenuHtml(`${BASE}/contas-receber/sincronizar-unico?aba=${aba}&id=${idsPedidos}`, 'Sincronizar todas as contas', getCobrancaBtn(item, aba, nome), idsPedidos)}
            </td>
        `;

        html += `<tr class="expandable-row" style="cursor:pointer;">${colsHtml}</tr>`;
    });

    tbody.innerHTML = html;
}

function renderFinanceiro(data) {
    const tbody = document.getElementById('tbody-financeiro');
    if (!tbody) return;

    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="empty-msg">Nenhum pedido pendente vinculado a contato financeiro.</td></tr>`;
        return;
    }

    let html = '';
    data.forEach(item => {
        let nome = item.NOME_CF || '—';
        let btnExpandHtml = getExpandButtonHtml('financeiros', item.ID_CF);
        
        const isUmPedido = parseInt(item.QTD_CONTAS) === 1;
        const isUnicaParcela = parseInt(item.QTD_PARCELAS) === 1;

        if (isUmPedido && item.NUMEROS_PEDIDOS && item.NUMEROS_PEDIDOS !== '—') {
            const numeroUnico = item.NUMEROS_PEDIDOS.split(',')[0];
            nome += ` <small style="color: #777; font-size: 12px; margin-left: 6px;">(Ped. ${numeroUnico})</small>`;
            if (isUnicaParcela) btnExpandHtml = '';
        }
        
        html += `
            <tr class="expandable-row" style="cursor:pointer;">
                <td class="expand-col">${btnExpandHtml}</td>
                <td class="nome-col">${nome}</td>
                <td class="text-center">${item.QTD_CONTAS || 0}</td>
                <td class="valor-col">${formatCurrency(item.TOTAL_VALOR)}</td>
                <td class="date-col">${formatDate(item.VENCIMENTO_MAIS_ANTIGO)}</td>
                <td class="col-status-cobranca">${getCobrancaBadge(item, 'financeiros')}</td>
                <td class="cr-col-acoes" style="text-align:right;">
                    ${getAcoesMenuHtml(`${BASE}/contas-receber/sincronizar-unico?aba=financeiros&id=${item.IDS_PEDIDOS}`, 'Sincronizar todas as contas', getCobrancaBtn(item, 'financeiros', nome), item.IDS_PEDIDOS)}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function renderPedidos(data, isPagos) {
    const tbody = document.getElementById('tbody-pedidos');
    if (!tbody) return;

    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" class="empty-msg">Nenhum pedido encontrado.</td></tr>`;
        return;
    }

    let html = '';
    data.forEach(ped => {
        const parcelas = ped.PARCELAS || [];
        const qtdParcelas = parcelas.length;
        const rowClass = qtdParcelas > 1 ? 'expandable-row' : '';
        const numPedido = ped.NUM_PEDIDO !== '—' && ped.NUM_PEDIDO ? ped.NUM_PEDIDO : 'Sem Nº';
        const nomeCliente = ped.NOME_CLIENTE || '—';
        const nomeRep = ped.NOME_REPRESENTANTE || '—';
        const valor = isPagos ? ped.TOTAL_PEDIDO : (ped.TOTAL_PEDIDO - ped.VALOR_PAGO);
        
        let dataVenc = formatDate(ped.DATA_VENCIMENTO_MIN);

        let idsToSync = [];
        if (qtdParcelas === 1) {
            idsToSync.push(ped.ID_PEDIDO);
        } else {
            parcelas.forEach(p => idsToSync.push(p.ID_PEDIDO));
        }
        const idsStr = idsToSync.join(',');

        let expandBtn = '';
        if (qtdParcelas > 1) {
            expandBtn = `
                <button class="btn-expand" data-toggle-detalhes-pedido="${ped.ID_PEDIDO}" title="Ver parcelas">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M7 10l5 5 5-5z"/></svg>
                </button>
            `;
        }

        const cursorStyle = qtdParcelas > 1 ? 'cursor:pointer;' : '';

        html += `
            <tr class="${rowClass}" style="${cursorStyle}" ${qtdParcelas > 1 ? `data-toggle-detalhes-pedido="${ped.ID_PEDIDO}"` : ''}>
                <td class="expand-col">${expandBtn}</td>
                <td><strong>${numPedido}</strong></td>
                <td class="nome-col">${nomeCliente}</td>
                <td class="nome-col">${nomeRep}</td>
                <td class="text-center">${qtdParcelas}</td>
                <td class="valor-col font-semibold">${formatCurrency(valor)}</td>
                <td class="date-col">${dataVenc}</td>
                <td>${getModernBadge(ped.SITUACAO_PEDIDO)}</td>
                <td class="text-center">
                    ${getAcoesMenuHtml(`${BASE}/contas-receber/sincronizar-unico?aba=pedidos&id=${idsStr}`, qtdParcelas > 1 ? 'Sincronizar parcelas' : 'Sincronizar', '', idsStr)}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// ── Expandir detalhes do cliente/representante/financeiros (AJAX) ────────
function toggleDetalhes(tipo, id, element) {
    const row = element.closest('tr');
    const nomeCol = row.querySelector('.nome-col');
    const nomeTitle = nomeCol ? nomeCol.textContent.trim() : 'Detalhes';

    const modal = document.getElementById('modal-detalhes');
    const modalTitle = document.getElementById('modal-detalhes-title');
    const modalBody = document.getElementById('modal-detalhes-body');

    modalTitle.textContent = nomeTitle;
    modalBody.innerHTML = '<div class="detail-content loading" style="text-align:center; padding: 40px;">Carregando...</div>';
    modal.style.display = 'flex';

    fetch(BASE + `/contas-receber/api/lista?tipo=${tipo}&id=${id}`)
        .then(r => r.json())
        .then(response => {
            const contas = response.data || [];
            let content = modalBody;

            if (response.error) {
                content.innerHTML = `<p class="empty-msg text-center">Erro: ${response.error}</p>`;
                return;
            }

            if (!contas.length) {
                content.innerHTML = '<p class="empty-msg text-center">Nenhuma conta encontrada.</p>';
                return;
            }

            if (response.html) {
                content.innerHTML = response.html;
            } else {
                content.innerHTML = '<p class="empty-msg text-center">Nenhuma conta encontrada.</p>';
            }

            content.classList.remove('loading');
        })
        .catch(err => {
            modalBody.innerHTML = '<p class="empty-msg text-center">Erro de conexão.</p>';
        });
}

function openModalPedido(idPedido) {
    const ped = INITIAL_DATA.pedidos.find(p => p.ID_PEDIDO == idPedido);
    if (!ped) return;

    const modal = document.getElementById('modal-detalhes');
    const modalTitle = document.getElementById('modal-detalhes-title');
    const modalBody = document.getElementById('modal-detalhes-body');

    modalTitle.textContent = 'Pedido ' + (ped.NUM_PEDIDO && ped.NUM_PEDIDO !== '—' ? ped.NUM_PEDIDO : 'Sem Nº');
    
    const isPagos = INITIAL_DATA.isPagos;
    let parcelasHtml = '';
    
    ped.PARCELAS.forEach(p => {
        const valorP = isPagos ? p.TOTAL_PEDIDO : (p.TOTAL_PEDIDO - p.VALOR_PAGO);
        const btnBaixa = `<button onclick="abrirModalBaixa('${p.ID_PEDIDO}')" title="Dar Baixa Local" class="btn-action-icon-sm" style="margin-right:4px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></button>`;
        const btnSyncP = `<a href="${BASE}/contas-receber/sincronizar-unico?aba=pedidos&id=${p.ID_PEDIDO}" title="Sincronizar esta parcela" class="btn-action-icon-sm">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
        </a>`;
        
        parcelasHtml += `
            <tr style="background-color: #fff;">
                <td>${formatDate(p.DATA_VENCIMENTO)}</td>
                <td class="valor-col font-semibold pr-40">${formatCurrency(valorP)}</td>
                <td class="text-center">${getModernBadge(p.SITUACAO_PEDIDO)}</td>
                <td class="text-center">${btnBaixa}${btnSyncP}</td>
            </tr>
        `;
    });

    const html = `
        <div class="detail-content" style="padding: 10px;">
            <table class="detail-table inner-detail">
                <thead>
                    <tr>
                        <th>Data de Vencimento</th>
                        <th class="valor-col pr-40">${isPagos ? 'Valor' : 'Valor Restante'}</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ação</th>
                    </tr>
                </thead>
                <tbody>${parcelasHtml}</tbody>
            </table>
        </div>
    `;
    
    modalBody.innerHTML = html;
    modal.style.display = 'flex';
}

// ── Busca Local na Tabela (Filtro em tempo real) ─────────────────────────
(function() {
    const searchInput = document.getElementById('cr-search');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const tables = document.querySelectorAll('.cr-table');
        
        tables.forEach(table => {
            const rows = table.querySelectorAll('tbody tr:not(.detail-row):not(.search-empty-row)');
            let hasVisible = false;
            
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                
                if (rowText.includes(query)) {
                    row.classList.remove('d-none');
                    hasVisible = true;
                } else {
                    row.classList.add('d-none');
                    const nextRow = row.nextElementSibling;
                    if (nextRow && nextRow.classList.contains('detail-row')) {
                        nextRow.classList.add('d-none');
                        row.querySelector('.btn-expand')?.classList.remove('expanded');
                    }
                }
            });
            
            let emptyMsgRow = table.querySelector('tr.search-empty-row');
            if (!hasVisible && rows.length > 0) {
                if (!emptyMsgRow) {
                    const tbody = table.querySelector('tbody');
                    const cols = table.querySelectorAll('thead th').length;
                    emptyMsgRow = document.createElement('tr');
                    emptyMsgRow.className = 'search-empty-row empty-msg';
                    emptyMsgRow.innerHTML = `<td colspan="${cols}" class="text-center" style="padding: 20px;">Nenhum resultado encontrado para "${this.value}".</td>`;
                    tbody.appendChild(emptyMsgRow);
                } else {
                    emptyMsgRow.classList.remove('d-none');
                    emptyMsgRow.querySelector('td').textContent = `Nenhum resultado encontrado para "${this.value}".`;
                }
            } else if (emptyMsgRow) {
                emptyMsgRow.classList.add('d-none');
            }
        });
    });
})();

// ── Botão Sincronizar com feedback visual ──────────────────────────────────
(function() {
    const syncBtn = document.getElementById('btn-sincronizar');
    if (!syncBtn) return;

    syncBtn.addEventListener('click', (e) => {
        if (!confirm('Iniciar sincronização com o Bling?\nEste processo pode levar alguns minutos.')) {
            e.preventDefault();
            return;
        }
        syncBtn.classList.add('syncing');
        syncBtn.innerHTML = '<span class="spinner"></span> Sincronizando...';
        syncBtn.style.pointerEvents = 'none';
    });
})();

// ── Delegação de Eventos ──────────────────────────────────────────────────
document.addEventListener('click', (e) => {
    // Para clientes/representantes via AJAX
    let btnExpand = e.target.closest('[data-toggle-detalhes]');
    
    // Se não clicou no botão, verifica se clicou na linha principal
    if (!btnExpand) {
        const row = e.target.closest('tr');
        if (row && row.parentNode && (row.parentNode.id === 'tbody-padrao' || row.parentNode.id === 'tbody-financeiro')) {
            btnExpand = row.querySelector('[data-toggle-detalhes]');
        }
    }
    
    if (btnExpand) {
        // Não expande se clicou nas ações
        if (e.target.closest('a.btn-action-icon') || e.target.closest('a.btn-action-icon-sm') || e.target.closest('.cr-col-acoes') || e.target.closest('.dropdown')) return;
        
        e.preventDefault();
        const tipo = btnExpand.getAttribute('data-tipo');
        const id = btnExpand.getAttribute('data-id');
        toggleDetalhes(tipo, id, btnExpand);
        return; // Retorna para não cair no próximo
    }
    
    // Para Pedidos (Modal)
    let btnExpandPedido = e.target.closest('[data-toggle-detalhes-pedido]');
    if (!btnExpandPedido) {
        const row = e.target.closest('tr');
        if (row && row.hasAttribute('data-toggle-detalhes-pedido')) {
            btnExpandPedido = row;
        }
    }
    
    if (btnExpandPedido) {
        if (e.target.closest('a.btn-action-icon') || e.target.closest('a.btn-action-icon-sm') || e.target.closest('.cr-col-acoes') || e.target.closest('.dropdown')) return;
        
        e.preventDefault();
        const idPedido = btnExpandPedido.getAttribute('data-toggle-detalhes-pedido');
        openModalPedido(idPedido);
        return;
    }
    
    // Para parcelas embutidas (DOM local - dentro do modal para clientes/reps)
    let btnParcelas = e.target.closest('[data-toggle-parcelas]');
    
    // Se não clicou no botão, verifica se clicou na linha interna expansível
    if (!btnParcelas) {
        const row = e.target.closest('tr.expandable-row');
        if (row && row.closest('.cr-modal')) {
            btnParcelas = row.querySelector('[data-toggle-parcelas]');
        }
    }
    
    if (btnParcelas) {
        // Não expande se clicou no botão de sincronizar
        if (e.target.closest('a.btn-action-icon') || e.target.closest('a.btn-action-icon-sm') || e.target.closest('.btn-acoes-dropdown')) return;

        e.preventDefault();
        const targetId = btnParcelas.getAttribute('data-toggle-parcelas');
        const detailRow = document.getElementById('parcelas_' + targetId);
        
        if (detailRow) {
            if (detailRow.classList.contains('d-none')) {
                detailRow.classList.remove('d-none');
                btnParcelas.classList.add('expanded');
            } else {
                detailRow.classList.add('d-none');
                btnParcelas.classList.remove('expanded');
            }
        }
    }
});

/* ══════════════════════════════════════════════════════════════
   LÓGICA DE COBRANÇAS — integrada ao Contas a Receber
══════════════════════════════════════════════════════════════ */

function setFiltroCobranca(modo) {
    filtroCobranca = modo;

    const btnSem   = document.getElementById('btn-filtro-sem-cobranca');
    const btnTodos = document.getElementById('btn-filtro-todos');
    if (!btnSem || !btnTodos) return;

    if (modo === 'sem') {
        btnSem.classList.add('filtro-cob-btn--active');
        btnTodos.classList.remove('filtro-cob-btn--active');
    } else {
        btnTodos.classList.add('filtro-cob-btn--active');
        btnSem.classList.remove('filtro-cob-btn--active');
    }

    renderAndSort();
}

function getCobrancaBadge(item, tipo) {
    const ativas = (INITIAL_DATA.cobrancasAtivas || {})[tipo] || {};
    
    let id;
    if (tipo === 'financeiros') id = item.ID_CF;
    else if (tipo === 'representantes') id = item.ID_CONTATO_BLING;
    else id = item.ID_CONTATO_BLING;

    const cob = ativas[id] || ativas[String(id)];
    if (cob) {
        return `<span class="modern-badge badge-info" style="font-size:0.7rem;" title="Cobrança Ativa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
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
            return `<span class="modern-badge badge-info" style="background-color:#fdf4ff; color:#a21caf; border: 1px solid #f0abfc;" title="Vinculados em andamento">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Vinculado c/ ${cobradores.join(', ')}
                    </span>`;
        }
    }

    return '';
}

function getCobrancaBtn(item, tipo, nome = '') {
    if (nome && nome.toLowerCase().includes('sac por')) return '';
    const ativas = (INITIAL_DATA.cobrancasAtivas || {})[tipo] || {};
    
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

    if ((!hasItems && ativas[id]) || (hasItems && allCharged)) {
        return ''; // Já sendo totalmente cobrado
    }

    let btnTitle = ativas[id] ? 'Assumir restantes' : 'Assumir cobrança';

    const tplEl = document.getElementById('tpl-btn-cobrar-cr');
    if (tplEl) {
        let tpl = tplEl.innerHTML;
        tpl = tpl.replace(/\{\{id\}\}/g, id).replace(/\{\{tipo\}\}/g, tipo).replace(/\{\{btnTitle\}\}/g, btnTitle);
        return tpl;
    }
    return '';
}

// Setup do modal de cobrança (delegação de eventos)
document.addEventListener('DOMContentLoaded', () => {
    const modalCob = document.getElementById('modal-cobranca-clientes');
    if (!modalCob) return;

    const closeCob = () => modalCob.classList.add('hidden');
    document.getElementById('modal-cobranca-close').addEventListener('click', closeCob);
    document.getElementById('modal-cobranca-overlay').addEventListener('click', closeCob);
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modalCob.classList.contains('hidden')) {
            closeCob();
        }
    });

    document.getElementById('check-all-clientes').addEventListener('change', e => {
        document.querySelectorAll('.check-cliente-cob').forEach(cb => cb.checked = e.target.checked);
    });
    document.getElementById('btn-confirmar-cobranca').addEventListener('click', confirmarCobranca);

    // Delegação no body para capturar cliques nos botões gerados dinamicamente
    document.body.addEventListener('click', e => {
        const btn = e.target.closest('.cr-btn-cobrar');
        if (!btn) return;
        e.stopPropagation();
        iniciarCobranca(btn.getAttribute('data-cob-id'), btn.getAttribute('data-cob-tipo'));
    });
});

let _cobPendente = { id: null, tipo: null };

function iniciarCobranca(id, tipo) {
    if (tipo === 'clientes') {
        if (!confirm('Deseja assumir a cobrança deste cliente?')) return;
        enviarCobranca(tipo, id, [parseInt(id)]);
        return;
    }
    _cobPendente = { id, tipo };
    document.getElementById('modal-cobranca-tipo-texto').innerText = tipo === 'representantes' ? 'Representante' : 'Contato Financeiro';
    document.getElementById('modal-cobranca-clientes').classList.remove('hidden');
    carregarClientesCob(id, tipo);
}

function carregarClientesCob(id, tipo) {
    const container = document.getElementById('clientes-list-container');
    const loading   = document.getElementById('modal-cobranca-loading');
    container.innerHTML = '';
    loading.classList.remove('hidden');

    fetch(`${BASE}/cobrancas/api-clientes-agrupamento?tipo=${tipo}&id=${id}`)
        .then(r => r.json())
        .then(data => {
            loading.classList.add('hidden');
            if (!data.success || !data.html) {
                container.innerHTML = `<p style="color:#ef4444;font-size:0.88rem;text-align:center;padding:16px;">${data.error || 'Nenhum cliente inadimplente.'}</p>`;
                return;
            }
            container.innerHTML = data.html;
            document.getElementById('check-all-clientes').checked = true;
        })
        .catch(() => {
            loading.classList.add('hidden');
            container.innerHTML = `<p style="color:#ef4444;text-align:center;padding:16px;">Erro ao carregar clientes.</p>`;
        });
}

function confirmarCobranca() {
    const selecionados = [...document.querySelectorAll('.check-cliente-cob:checked')].map(c => parseInt(c.value));
    if (!selecionados.length) { alert('Selecione pelo menos um cliente.'); return; }

    const btn = document.getElementById('btn-confirmar-cobranca');
    btn.disabled = true;
    btn.innerText = 'Processando...';

    enviarCobranca(_cobPendente.tipo, _cobPendente.id, selecionados)
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg> Confirmar e Assumir';
            document.getElementById('modal-cobranca-clientes').classList.add('hidden');
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

// ── BAIXA MANUAL ────────────────────────────────────────────────────────────
let parcelasBaixaAtual = [];

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
    const btnTextOriginal = 'Carregando...'; // You might want to show a loading state on the button that triggered it
    
    fetch(`${BASE}/contas-receber/api/parcelas-por-ids?ids=${idsArray.join(',')}`)
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
            document.getElementById('modal-baixa-manual').classList.remove('hidden');
        })
        .catch(err => {
            alert('Erro de conexão ao buscar parcelas.');
        });
}

function fecharModalBaixa() {
    document.getElementById('modal-baixa-manual').classList.add('hidden');
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

function toggleGrupoBaixa(gIdx, headerEl) {
    const content = document.getElementById(`grupo-baixa-${gIdx}`);
    const icon = document.querySelector(`.icon-expand-grupo-${gIdx}`);
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        headerEl.style.borderBottomColor = '#e2e8f0';
        if (icon) icon.style.transform = 'rotate(90deg)';
    } else {
        content.style.display = 'none';
        headerEl.style.borderBottomColor = 'transparent';
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
}

function toggleBaixarTudoCheckbox(isChecked) {
    // Marca ou desmarca todos os checkboxes de grupo
    document.querySelectorAll('.checkbox-grupo-baixa').forEach(chk => {
        chk.checked = isChecked;
    });
    // Aplica a lógica em todos os inputs de valor
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
    // Atualiza os inputs numéricos do grupo
    document.querySelectorAll(`.grupo-input-${gIdx}`).forEach(input => {
        if (isChecked) {
            input.value = parseFloat(input.getAttribute('max')).toFixed(2);
        } else {
            input.value = '0.00';
        }
    });
    // Atualiza os checkboxes individuais das parcelas do grupo
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

    const btnConfirma = document.querySelector('#modal-baixa-manual .btn-sync[style*="background: #16a34a"]');
    const oldText = btnConfirma.innerHTML;
    btnConfirma.innerHTML = 'Baixando...';
    btnConfirma.disabled = true;

    fetch(BASE + '/contas-receber/baixar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
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

function abrirModalExibirAte(actionType, fieldName) {
    document.getElementById('input-action-type').value = actionType;
    const msgEl = document.getElementById('msg-exibir-ate');
    if (actionType.startsWith('limpar')) {
        msgEl.innerHTML = `Deseja <strong>limpar o filtro ${fieldName}</strong> para <strong>todos os usuários</strong>?`;
    } else {
        msgEl.innerHTML = `Tem certeza que deseja <strong>alterar o filtro ${fieldName}</strong>? Essa alteração será salva no sistema e afetará <strong>todos os usuários</strong>.`;
    }
    document.getElementById('modal-exibir-ate').style.display = 'flex';
}

function fecharModalExibirAte() {
    document.getElementById('modal-exibir-ate').style.display = 'none';
    const inputAte = document.getElementById('input-exibir-ate');
    const inputPartir = document.getElementById('input-exibir-partir');
    inputAte.value = inputAte.getAttribute('data-original');
    inputPartir.value = inputPartir.getAttribute('data-original');
    document.getElementById('input-action-type').value = '';
}

function confirmarExibirAte() {
    document.getElementById('form-exibir-ate').submit();
}

function sincronizarPedidoPorId() {
    const id = prompt(`Digite o ID da conta a receber no Bling (ID interno, não o número do pedido):`);
    if (id) {
        window.location.href = BASE + `/contas-receber/sincronizar-unico?aba=pedidos&id=` + id;
    }
}
