/**
 * contatos.js — Vanilla JS para a página de Contatos
 * Gerencia modais, autocomplete de telefone e seleção de vínculos.
 */

const BASE = document.querySelector('meta[name="base-url"]')?.content
           || window.location.pathname.replace(/\/contatos.*/, '')
           || '/hydraRemake';

// ── Modal telefone ──────────────────────────────────────────────────────
function openAddTel(id, aba) {
    document.getElementById('tel-action').value = 'add';
    document.getElementById('tel-id-contato').value = id;
    document.getElementById('tel-id-tel').value = '';
    document.getElementById('tel-aba').value = aba;
    document.getElementById('tel-num').value = '';
    document.getElementById('modal-tel-title').textContent = 'Adicionar Telefone';
    const modalTel = document.getElementById('modal-tel');
    modalTel.style.zIndex = '9999';
    modalTel.style.display = 'flex';
}

function openEditTel(idTel, num, aba) {
    document.getElementById('tel-action').value = 'edit';
    document.getElementById('tel-id-contato').value = '';
    document.getElementById('tel-id-tel').value = idTel;
    document.getElementById('tel-aba').value = aba;
    document.getElementById('tel-num').value = num;
    document.getElementById('modal-tel-title').textContent = 'Editar Telefone';
    const modalTel = document.getElementById('modal-tel');
    modalTel.style.zIndex = '9999';
    modalTel.style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => {
        if (e.target === m) m.style.display = 'none';
    });
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(m => {
            if (m.style.display !== 'none') {
                m.style.display = 'none';
            }
        });
    }
});

// ── Autocomplete: Telefone ──────────────────────────────────────────────
(function() {
    const telInput = document.getElementById('cf-tel-input');
    const telDrop = document.getElementById('tel-dropdown');
    if (!telInput) return;

    let debounce;
    telInput.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => {
            const q = telInput.value.trim();
            if (q.length < 2) { telDrop.innerHTML = ''; telDrop.style.display = 'none'; return; }
            fetch(BASE + '/contatos/api/telefones?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (!data.length) { telDrop.style.display = 'none'; return; }
                    telDrop.innerHTML = data.map(t =>
                        `<div class="ac-item" data-value="${t.NUM_TEL}">${t.NUM_TEL}</div>`
                    ).join('');
                    telDrop.style.display = 'block';
                    telDrop.querySelectorAll('.ac-item').forEach(el => {
                        el.addEventListener('click', () => {
                            telInput.value = el.dataset.value;
                            telDrop.style.display = 'none';
                        });
                    });
                });
        }, 250);
    });
    document.addEventListener('click', e => {
        if (!telInput.contains(e.target) && !telDrop.contains(e.target)) {
            telDrop.style.display = 'none';
        }
    });
})();

// ── Autocomplete + Multi-select: Vínculos ────────────────────────────────
(function() {
    const input = document.getElementById('vinculos-input');
    const drop = document.getElementById('vinculos-dropdown');
    const tagsBox = document.getElementById('vinculos-tags');
    if (!input) return;

    const selected = new Map(); // id => {nome, tipo}

    function renderTags() {
        tagsBox.innerHTML = '';
        selected.forEach((info, id) => {
            const tag = document.createElement('span');
            tag.className = 'tag';
            tag.innerHTML = `${info.nome} <span class="tag-type">${info.tipo}</span><button type="button" class="tag-remove" data-id="${id}">✕</button>`;
            // Hidden input for form submission
            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = 'vinculos[]'; hidden.value = id;
            tag.appendChild(hidden);
            tagsBox.appendChild(tag);
        });
        tagsBox.querySelectorAll('.tag-remove').forEach(btn => {
            btn.addEventListener('click', () => {
                selected.delete(btn.dataset.id);
                renderTags();
            });
        });
    }

    let debounce;
    input.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => {
            const q = input.value.trim();
            if (q.length < 2) { drop.innerHTML = ''; drop.style.display = 'none'; return; }
            
            const tipoVinculo = document.querySelector('input[name="tipo_vinculo"]:checked').value;
            fetch(BASE + '/contatos/api/contatos?q=' + encodeURIComponent(q) + '&tipo=' + encodeURIComponent(tipoVinculo))
                .then(r => r.json())
                .then(data => {
                    const filtered = data.filter(c => !selected.has(String(c.ID_CONTATO_BLING)));
                    if (!filtered.length) { drop.style.display = 'none'; return; }
                    drop.innerHTML = filtered.map(c => {
                        const doc = c.NUMERO_DOCUMENTO ? ` — ${c.NUMERO_DOCUMENTO}` : '';
                        return `<div class="ac-item" data-id="${c.ID_CONTATO_BLING}" data-nome="${c.NOME_CONTATO}" data-tipo="${c.TIPO}">
                            <span class="ac-name">${c.NOME_CONTATO}${doc}</span>
                            <span class="ac-badge ac-badge-${c.TIPO.toLowerCase()}">${c.TIPO}</span>
                        </div>`;
                    }).join('');
                    drop.style.display = 'block';
                    drop.querySelectorAll('.ac-item').forEach(el => {
                        el.addEventListener('click', () => {
                            selected.set(el.dataset.id, { nome: el.dataset.nome, tipo: el.dataset.tipo });
                            renderTags();
                            input.value = '';
                            drop.style.display = 'none';
                        });
                    });
                });
        }, 250);
    });
    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !drop.contains(e.target)) {
            drop.style.display = 'none';
        }
    });
    
    // Limpar os vínculos se o usuário mudar o tipo
    document.querySelectorAll('input[name="tipo_vinculo"]').forEach(radio => {
        radio.addEventListener('change', () => {
            selected.clear();
            renderTags();
        });
    });

    // Expor para a função de edição
    window.cfSelectedVinculos = selected;
    window.cfRenderTags = renderTags;
})();

// ── Alternar Status (Confirmado/Tentativa) via AJAX ─────────────────────
function toggleStatus(btn, idTel) {
    const formData = new FormData();
    formData.append('id_tel', idTel);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch(BASE + '/contatos/toggle-confirmado', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            window.phonesModified = true;
            // Trocar classes e texto do botão
            if (btn.classList.contains('badge-confirmed')) {
                btn.classList.remove('badge-confirmed');
                btn.classList.add('badge-attempt');
                btn.textContent = '?';
            } else {
                btn.classList.remove('badge-attempt');
                btn.classList.add('badge-confirmed');
                btn.textContent = '✓';
            }
            
            // Atualizar rastro visual
            if (data.user) {
                const telItem = btn.closest('.tel-item');
                let trackDiv = telItem.querySelector('.tracking-info');
                if (!trackDiv) {
                    trackDiv = document.createElement('div');
                    trackDiv.className = 'tracking-info';
                    trackDiv.style.cssText = 'font-size: 0.75rem; color: #94a3b8; margin-top: 4px; padding-left: 28px;';
                    telItem.appendChild(trackDiv);
                }
                const oldText = trackDiv.innerHTML;
                const criado = oldText.match(/Criado por: ([^|]+)/);
                trackDiv.innerHTML = '';
                if (criado && criado[1].trim() !== '') {
                    trackDiv.innerHTML = `Criado por: ${criado[1].trim()} | `;
                }
                trackDiv.innerHTML += `Alterado por: ${data.user}`;
            }
        }
    })
    .catch(err => console.error('Erro ao alternar status:', err));
}

// ── Busca Dinâmica na Tabela ─────────────────────────────────────────────
(function() {
    const searchInput = document.getElementById('search-table');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.phone-table tbody tr');
            
            rows.forEach(row => {
                if (row.querySelector('.empty-msg')) return;
                // Extrair texto limpo (ignorando tags e ícones)
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
})();

// ── Edição de Contato Financeiro (Modal) ──────────────────────────────────
function openNewCFModal() {
    document.getElementById('cf-form-title').textContent = 'Novo Contato Financeiro';
    resetCFForm();
    document.getElementById('modal-cf').style.display = 'flex';
}

function openEditCF(idContato, nome, numTel, vinculosRaw) {
    document.getElementById('cf-id-contato').value = idContato;
    document.querySelector('#form-cf input[name="nome"]').value = nome;
    document.getElementById('cf-tel-input').value = numTel;
    
    document.getElementById('cf-form-title').textContent = 'Editar Contato Financeiro';
    document.getElementById('cf-submit-btn').textContent = 'Atualizar Contato Financeiro';
    
    // Limpar os vínculos atuais
    window.cfSelectedVinculos.clear();
    
    if (vinculosRaw) {
        // Formato: id:Nome [Tipo]|id:Nome [Tipo]
        const items = vinculosRaw.split('|');
        items.forEach(item => {
            const parts = item.split(':');
            if (parts.length >= 2) {
                const id = parts[0];
                const rest = parts.slice(1).join(':'); // O nome pode ter ':'
                const match = rest.match(/(.+) \[(Cliente|Representante)\]$/);
                if (match) {
                    window.cfSelectedVinculos.set(id, { nome: match[1], tipo: match[2] });
                    const radio = document.querySelector(`input[name="tipo_vinculo"][value="${match[2].toLowerCase()}"]`);
                    if (radio) radio.checked = true;
                }
            }
        });
    }
    
    window.cfRenderTags();
    document.getElementById('modal-cf').style.display = 'flex';
}

function resetCFForm() {
    document.getElementById('cf-id-contato').value = '';
    document.getElementById('form-cf').reset();
    
    document.getElementById('cf-form-title').textContent = 'Novo Contato Financeiro';
    document.getElementById('cf-submit-btn').textContent = 'Salvar';
    
    window.cfSelectedVinculos.clear();
    window.cfRenderTags();
}

// ── Dropdown Menus ──────────────────────────────────────────────────────────
window.toggleDropdown = function(event, button) {
    event.preventDefault();
    event.stopPropagation();
    const dropdown = button.closest('.dropdown');
    const menu = dropdown.querySelector('.dropdown-menu');
    
    // Close other open dropdowns
    document.querySelectorAll('.dropdown-menu.show').forEach(m => {
        if (m !== menu) m.classList.remove('show');
    });
    
    menu.classList.toggle('show');
};

document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(m => {
            m.classList.remove('show');
        });
    }
});

// ── Event Delegation & Listeners ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Checkbox filters
    const filterComTelefone = document.getElementById('filter-com-telefone');
    const filterComConfirmado = document.getElementById('filter-com-confirmado');
    if (filterComTelefone || filterComConfirmado) {
        const handleFilterChange = () => {
            const comTel = filterComTelefone && filterComTelefone.checked ? '&com_telefone=1' : '';
            const comConf = filterComConfirmado && filterComConfirmado.checked ? '&com_confirmado=1' : '';
            const aba = new URLSearchParams(window.location.search).get('aba') || 'clientes';
            window.location.href = BASE + '/contatos?aba=' + aba + comTel + comConf;
        };

        if (filterComTelefone) filterComTelefone.addEventListener('change', handleFilterChange);
        if (filterComConfirmado) filterComConfirmado.addEventListener('change', handleFilterChange);
    }

    // Click events delegation
    document.addEventListener('click', (e) => {
        // Toggle Status Badge
        const btnToggleStatus = e.target.closest('.badge[data-id]');
        if (btnToggleStatus && btnToggleStatus.getAttribute('title') === 'Alternar status') {
            e.preventDefault();
            const id = btnToggleStatus.getAttribute('data-id');
            toggleStatus(btnToggleStatus, id);
            return;
        }

        // Toggle Origem Button
        const btnToggleOrigem = e.target.closest('.btn-toggle[data-id][data-origem]');
        if (btnToggleOrigem) {
            e.preventDefault();
            const id = btnToggleOrigem.getAttribute('data-id');
            const origem = btnToggleOrigem.getAttribute('data-origem');
            toggleOrigem(btnToggleOrigem, id, origem);
            return;
        }

        // Edit Tel Button
        const btnEditTel = e.target.closest('.btn-edit[data-id][data-num]');
        if (btnEditTel) {
            e.preventDefault();
            const id = btnEditTel.getAttribute('data-id');
            const num = btnEditTel.getAttribute('data-num');
            const aba = btnEditTel.getAttribute('data-aba');
            openEditTel(id, num, aba);
            return;
        }

        // Add Tel Button
        const btnAddTel = e.target.closest('.btn-add[data-id][data-aba]');
        if (btnAddTel) {
            e.preventDefault();
            const id = btnAddTel.getAttribute('data-id');
            const aba = btnAddTel.getAttribute('data-aba');
            openAddTel(id, aba);
            // Also close dropdown if opened
            const dropdownMenu = btnAddTel.closest('.dropdown-menu');
            if (dropdownMenu) dropdownMenu.classList.remove('show');
            return;
        }
        
        // Add Tel Button (Sem telefone)
        const btnAddTelSem = e.target.closest('.btn-add');
        if (btnAddTelSem && btnAddTelSem.hasAttribute('onclick')) {
            // Let the inline onclick handle it, just close dropdown
            const dropdownMenu = btnAddTelSem.closest('.dropdown-menu');
            if (dropdownMenu) dropdownMenu.classList.remove('show');
        }

        // Edit CF Button
        const btnEditCF = e.target.closest('.btn-edit-cf[data-id]');
        if (btnEditCF) {
            e.preventDefault();
            const id = btnEditCF.getAttribute('data-id');
            const nome = btnEditCF.getAttribute('data-nome');
            const tel = btnEditCF.getAttribute('data-tel');
            const vinculos = btnEditCF.getAttribute('data-vinculos');
            openEditCF(id, nome, tel, vinculos);
        }
    });
    
    // Interceptar formulários para enviar via AJAX (Evita recarregar a página)
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.matches('form[action*="salvar-telefone-contato"]') || form.matches('form[action*="excluir-telefone"]') || form.matches('form[action*="sincronizar-contato-unico"]')) {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳';
            btn.disabled = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(r => r.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                if (form.id !== 'manage-form-sync') closeModal('modal-tel'); // Fecha modal de edição/adição
            })
            .catch(err => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                console.error(err);
            });
        }
    });
});

// ── WebSockets (Tempo Real) ───────────────────────────────────────────────
try {
    if (typeof window !== 'undefined' && window.Echo) {
        window.Echo.channel('contatos')
            .listen('TelefonesAtualizados', (e) => {
                console.log('Recebido update via WebSockets:', e);
                const idContato = e.idContatoBling;
                const telefones = e.telefones;
                
                // 1. Atualizar o JSON embutido na tabela
                const dataDiv = document.getElementById('phones-data-' + idContato);
                if (dataDiv) {
                    dataDiv.textContent = JSON.stringify(telefones);
                }
                
                // 2. Atualizar a Tabela de Contatos visível
                const row = document.querySelector(`tr[onclick*="'${idContato}'"]`);
                if (row) {
                    const telColBling = row.querySelector('td:nth-child(3)');
                    const telColManual = row.querySelector('td:nth-child(4)');
                    
                    const blingTels = telefones.filter(t => t.origem === 'bling');
                    const manualTels = telefones.filter(t => t.origem === 'manual');
                    
                    const renderTels = (tels) => {
                        if (!tels || tels.length === 0) return '<span class="no-phone">Sem telefone</span>';
                        let html = '<div class="tel-list-container">';
                        tels.forEach(t => {
                            const cssClass = t.confirmado == 1 ? 'confirmed-text' : 'attempt-text';
                            html += `<div class="tel-item simplified"><span class="tel-num ${cssClass}">${formatPhoneJS(t.num)}</span></div>`;
                        });
                        html += '</div>';
                        return html;
                    };

                    if (telColBling) telColBling.innerHTML = renderTels(blingTels);
                    if (telColManual) telColManual.innerHTML = renderTels(manualTels);
                }
                
                // 3. Atualizar o Modal de Gerenciamento, se estiver aberto para este contato
                if (document.getElementById('modal-manage-phones') && document.getElementById('modal-manage-phones').style.display !== 'none') {
                    const currentSyncId = document.getElementById('manage-sync-id').value;
                    if (currentSyncId == idContato) {
                        const nome = document.getElementById('manage-phones-title').textContent.replace('Telefones de ', '');
                        const aba = document.getElementById('manage-sync-aba').value;
                        if (typeof window.openManagePhonesModal === 'function') {
                            window.openManagePhonesModal(idContato, nome, aba);
                        }
                    }
                }
            });
    }
} catch (err) {
    console.warn("Erro ao inicializar escuta do Echo WebSocket:", err);
}

// ── Funções Auxiliares: Gerenciar Telefones Modal ────────────────────────
window.phonesModified = false;

function formatPhoneJS(num) {
    let clean = (num || '').replace(/\D/g, '');
    if (clean.length === 11) return clean.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    if (clean.length === 10) return clean.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    return num;
}

window.openManagePhonesModal = function(idContato, nomeContato, aba) {
    window.phonesModified = false;
    document.getElementById('manage-phones-title').textContent = `Telefones de ${nomeContato}`;
    
    // Configurar os botões do footer
    const btnAdd = document.getElementById('manage-btn-add');
    btnAdd.onclick = () => openAddTel(idContato, aba);
    
    document.getElementById('manage-sync-id').value = idContato;
    document.getElementById('manage-sync-aba').value = aba;

    // Pegar dados do JSON injetado
    const dataDiv = document.getElementById('phones-data-' + idContato);
    let telefones = [];
    if (dataDiv) {
        try { telefones = JSON.parse(dataDiv.textContent); } catch (e) {}
    }

    const blingContainer = document.getElementById('manage-phones-bling');
    const manualContainer = document.getElementById('manage-phones-manual');
    blingContainer.innerHTML = '';
    manualContainer.innerHTML = '';

    telefones.forEach(t => {
        const div = document.createElement('div');
        div.className = 'tel-item';
        
        const isConfirmado = t.confirmado == 1;
        const badgeClass = isConfirmado ? 'badge-confirmed' : 'badge-attempt';
        const badgeIcon = isConfirmado ? '✓' : '?';
        const formattedNum = formatPhoneJS(t.num);
        const nextOrigem = t.origem === 'bling' ? 'manual' : 'bling';

        let trackHtml = '';
        if (t.origem === 'manual') {
            if (t.criado_por || t.alterado_por) {
                trackHtml = '<div style="font-size: 0.75rem; color: #94a3b8; margin-top: 4px; padding-left: 28px;">';
                if (t.criado_por) trackHtml += `Criado por: ${t.criado_por} `;
                if (t.criado_por && t.alterado_por) trackHtml += '| ';
                if (t.alterado_por) trackHtml += `Alterado por: ${t.alterado_por}`;
                trackHtml += '</div>';
            }
        }

        div.innerHTML = `
            <div style="display: flex; align-items: center; width: 100%;">
                <button type="button" class="badge ${badgeClass}" title="Alternar status" data-id="${t.id}">${badgeIcon}</button>
                <span class="tel-num" style="font-weight: 500;">${formattedNum}</span>
                <div class="tel-actions" style="margin-left: auto;">
                    ${t.origem !== 'bling' ? `<button type="button" class="btn-icon btn-edit" data-id="${t.id}" data-num="${formattedNum}" data-aba="${aba}" title="Editar">✎</button>` : ''}
                    ${t.origem !== 'bling' ? `
                    <form method="POST" action="${BASE}/contatos/excluir-telefone" class="inline-form" onsubmit="return confirm('Excluir?')">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]')?.content || ''}">
                        <input type="hidden" name="id_tel" value="${t.id}">
                        <input type="hidden" name="id_contato" value="${idContato}">
                        <input type="hidden" name="aba" value="${aba}">
                        <button type="submit" class="btn-icon btn-delete" title="Excluir">✕</button>
                    </form>
                    ` : ''}
                </div>
            </div>
            ${trackHtml}
        `;
        
        if (t.origem === 'bling') {
            blingContainer.appendChild(div);
        } else {
            manualContainer.appendChild(div);
        }
    });

    if (!blingContainer.innerHTML) blingContainer.innerHTML = '<span class="no-phone">Sem telefone</span>';
    if (!manualContainer.innerHTML) manualContainer.innerHTML = '<span class="no-phone">Sem telefone</span>';

    document.getElementById('modal-manage-phones').style.display = 'flex';
};

window.closeModalAndReload = function(id) {
    document.getElementById(id).style.display = 'none';
    if (window.phonesModified) {
        window.location.reload();
    }
};
