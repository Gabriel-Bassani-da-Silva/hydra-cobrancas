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

    // Configuração unificada para Fechar o Modal de Detalhes
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
            const url = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || (typeof BASE_URL !== 'undefined' ? BASE_URL : '');

            e.currentTarget.disabled = true;
            e.currentTarget.innerHTML = 'Atualizando...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch(`${url}/cobranca/atualizar-pedidos`, {
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

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch(`${url}/cobranca/desistir`, {
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

    // Expandir cobrança ao clicar na linha
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('.btn-atualizar-cob') || e.target.closest('.btn-desistir-cob') || e.target.closest('.cr-col-acoes') || e.target.closest('.dropdown-content') || e.target.closest('.checkbox-grupo-baixa') || e.target.closest('.input-baixa-valor')) {
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
    document.addEventListener('click', function (e) {
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
                if (detailRow.classList.contains('d-none') || detailRow.style.display === 'none') {
                    detailRow.classList.remove('d-none');
                    detailRow.style.display = '';
                    btnParcelas.classList.add('expanded');
                } else {
                    detailRow.classList.add('d-none');
                    detailRow.style.display = 'none';
                    btnParcelas.classList.remove('expanded');
                }
            }
        }
    });
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

    fetch(`${document.querySelector('meta[name="base-url"]')?.getAttribute('content') || (typeof BASE_URL !== 'undefined' ? BASE_URL : '')}/perfil/api/pedidos?id=${id}&tipo=${tipo}`)
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

// ── FUNÇÕES PARA EDIÇÃO E ESTORNO DE BAIXAS LOCAIS ────────────────────────────────────────

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
    fetch((typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/baixas/editar', {
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
    fetch((typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/baixas/estornar', {
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

// Variável de controle (flag) para evitar múltiplos cliques/requisições simultâneas
let carregandoBaixas = false;

function abrirModalBaixas(idCliente) {
    // Se já estiver carregando uma requisição, ignora o clique
    if (carregandoBaixas) {
        console.warn(`[abrirModalBaixas] Clique bloqueado! Já existe uma requisição em andamento para o cliente ID: ${idCliente}`);
        return;
    }

    const modal = document.getElementById('modal-detalhes');
    if (!modal) {
        console.error('[abrirModalBaixas] Erro: Elemento #modal-detalhes não foi encontrado na página.');
        return;
    }

    const title = document.getElementById('modal-detalhes-title');
    const body = document.getElementById('modal-detalhes-body');

    // Ativa a trava antes de iniciar o fetch
    carregandoBaixas = true;
    console.log(`[abrirModalBaixas] Iniciando busca das baixas para o cliente ID: ${idCliente}...`);

    title.innerText = 'Minhas Baixas do Cliente';
    body.innerHTML = '<div class="text-center" style="padding: 20px;">Carregando baixas...</div>';

    modal.style.display = 'flex';

    const url = `${document.querySelector('meta[name="base-url"]')?.getAttribute('content') || (typeof BASE_URL !== 'undefined' ? BASE_URL : '')}/perfil/api-baixas-colaborador?id=${idCliente}`;

    fetch(url)
        .then(res => {
            console.log('[abrirModalBaixas] Resposta do servidor recebida. Convertendo para JSON...');
            return res.json();
        })
        .then(data => {
            if (data.html) {
                console.log('[abrirModalBaixas] Sucesso! HTML das baixas renderizado.');
                body.innerHTML = data.html;
            } else {
                console.error('[abrirModalBaixas] O servidor respondeu, mas retornou um erro interno:', data.error);
                body.innerHTML = '<div class="text-center" style="padding: 20px; color: red;">' + (data.error || 'Erro ao carregar') + '</div>';
            }
        })
        .catch(err => {
            console.error('[abrirModalBaixas] Falha crítica na requisição HTTP (Network Error):', err);
            body.innerHTML = '<div class="text-center" style="padding: 20px; color: red;">Erro na requisição.</div>';
        })
        .finally(() => {
            // Libera a trava independente de ter dado certo ou errado
            carregandoBaixas = false;
            console.log('[abrirModalBaixas] Processo finalizado. Trava liberada para novos cliques.');
        });
}