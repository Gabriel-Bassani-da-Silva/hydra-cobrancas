document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('cr-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
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
    // Fechar Modal de Detalhes
    const btnClose = document.getElementById('modal-detalhes-close');
    if (btnClose) {
        btnClose.addEventListener('click', () => {
            document.getElementById('modal-detalhes').style.display = 'none';
            document.querySelectorAll('.btn-expand.expanded').forEach(b => b.classList.remove('expanded'));
        });
    }
});

function toggleAcoesMenu(btn, event) {
    event.stopPropagation();
    // Fecha outros menus abertos
    document.querySelectorAll('.action-menu-dropdown').forEach(menu => {
        if (menu !== btn.nextElementSibling) {
            menu.style.display = 'none';
        }
    });
    const dropdown = btn.nextElementSibling;
    dropdown.style.display = dropdown.style.display === 'none' || dropdown.style.display === '' ? 'block' : 'none';
}

// Fechar menu se clicar fora
document.addEventListener('click', () => {
    document.querySelectorAll('.action-menu-dropdown').forEach(menu => {
        menu.style.display = 'none';
    });
});

document.addEventListener('DOMContentLoaded', () => {
    // Dropdown hover effect
    const hoverEls = document.querySelectorAll('.dropdown-hover');
    hoverEls.forEach(el => {
        el.addEventListener('mouseenter', () => {
            el.querySelector('.dropdown-content').style.display = 'block';
        });
        el.addEventListener('mouseleave', () => {
            el.querySelector('.dropdown-content').style.display = 'none';
        });
    });

    // Atualizar Cobrança
    const btnAtualizar = document.querySelectorAll('.btn-atualizar-cob');
    btnAtualizar.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const idCobranca = e.currentTarget.getAttribute('data-id');
            const url = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || (typeof BASE_URL !== 'undefined' ? BASE_URL : '');
            
            e.currentTarget.disabled = true;
            e.currentTarget.innerHTML = 'Atualizando...';

            fetch(`${url}/cobrancas/atualizar-pedidos`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_cobranca: idCobranca })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Pedidos da cobrança atualizados com sucesso (pagos removidos, novos pendentes adicionados)!');
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
            if (!confirm('Tem certeza que deseja desistir desta cobrança? Ela será encerrada e voltará a ficar disponível para outros colaboradores.')) return;
            
            const idCobranca = e.currentTarget.getAttribute('data-id');
            const url = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || (typeof BASE_URL !== 'undefined' ? BASE_URL : '');
            
            e.currentTarget.disabled = true;
            e.currentTarget.innerHTML = 'Cancelando...';

            fetch(`${url}/cobrancas/desistir`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
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
    }

    // Expandir cobrança ao clicar na linha
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.btn-atualizar-cob') || e.target.closest('.btn-desistir-cob') || e.target.closest('.cr-col-acoes') || e.target.closest('.dropdown-content')) {
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

    // Delegação global para botoes de parcelas e click do toggleDetalhes
    document.addEventListener('click', function(e) {
        let btnParcelas = e.target.closest('[data-toggle-parcelas]');
        
        if (!btnParcelas) {
            const row = e.target.closest('tr.expandable-row');
            if (row && row.closest('.cr-modal')) {
                btnParcelas = row.querySelector('[data-toggle-parcelas]');
            }
        }
        
        if (btnParcelas) {
            if (e.target.closest('a.btn-action-icon') || e.target.closest('a.btn-action-icon-sm')) return;
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
});

window.toggleDetalhesPerfil = function(btnEl, id, nome, tipo) {
    if (!btnEl) return;
    
    document.querySelectorAll('.btn-expand.expanded').forEach(b => {
        if (b !== btnEl) b.classList.remove('expanded');
    });
    btnEl.classList.toggle('expanded');

    const modal  = document.getElementById('modal-detalhes');
    const title  = document.getElementById('modal-detalhes-title');
    const body   = document.getElementById('modal-detalhes-body');

    title.textContent = "Pedidos de: " + nome;
    body.innerHTML    = `<div class="detail-content loading" style="text-align:center;padding:40px;">Carregando...</div>`;
    modal.style.display = 'flex';

    fetch(`${(typeof BASE_URL !== 'undefined' ? BASE_URL : '')}/perfil/api/pedidos?id=${id}&tipo=${tipo}`)
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
