/**
 * contas_receber_baixas.js — Funções específicas da aba Baixas de Contas a Receber
 * Gerencia divergências, modal de corrigir baixa, edição/estorno de baixas locais.
 *
 * Depende de: BASE_URL (definido no blade via meta[name="base-url"] ou variável global)
 */

// ── Helpers ────────────────────────────────────────────────────────────────

function getBase() {
    return document.querySelector('meta[name="base-url"]')?.content
        || (typeof BASE_URL !== 'undefined' ? BASE_URL : '')
        || '';
}

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

// ── Sub-painel das Baixas: Todas / Divergentes ─────────────────────────────

function setBaixasFiltro(filtro) {
    const btnTodas    = document.getElementById('btn-baixas-todas');
    const btnDiv      = document.getElementById('btn-baixas-divergentes');
    const painelTodas = document.getElementById('painel-baixas-todas');
    const painelDiv   = document.getElementById('painel-baixas-divergentes');
    if (!btnTodas) return;

    if (filtro === 'todas') {
        btnTodas.classList.add('active');
        btnDiv.classList.remove('active');
        painelTodas.style.display = '';
        painelDiv.style.display = 'none';
    } else {
        btnDiv.classList.add('active');
        btnTodas.classList.remove('active');
        painelDiv.style.display = '';
        painelTodas.style.display = 'none';
    }
}

// ── Modal de divergências ──────────────────────────────────────────────────

function abrirModalDivergencias(idCliente) {
    const modal = document.getElementById('modal-detalhes');
    if (!modal) return;

    const title = document.getElementById('modal-detalhes-title');
    const body  = document.getElementById('modal-detalhes-body');

    title.innerText = 'Divergências do Cliente';
    body.innerHTML  = '<div class="text-center loading-msg">Carregando divergências...</div>';
    modal.style.display = 'flex';

    fetch(`${getBase()}/divergencias/api-divergencias-cliente?id=${idCliente}`)
        .then(res => res.json())
        .then(data => {
            if (data.html) {
                body.innerHTML = data.html;
            } else {
                body.innerHTML = `<div class="text-center error-msg">${data.error || 'Erro ao carregar'}</div>`;
            }
        })
        .catch(() => {
            body.innerHTML = '<div class="text-center error-msg">Erro na requisição.</div>';
        });
}

// ── Modal de corrigir baixa local ──────────────────────────────────────────

function abrirModalCorrigir(idPedido, atualPago) {
    document.getElementById('corrigir-id-pedido').value = idPedido;
    document.getElementById('corrigir-novo-valor').value = atualPago;
    document.getElementById('modal-corrigir-baixa').style.display = 'flex';
}

function fecharModalCorrigirBaixa() {
    document.getElementById('modal-corrigir-baixa').style.display = 'none';
}

function confirmarCorrecaoBaixa() {
    const idPedido  = document.getElementById('corrigir-id-pedido').value;
    const novoValor = document.getElementById('corrigir-novo-valor').value;
    if (!idPedido || novoValor === '') {
        alert('Por favor, informe o novo valor.');
        return;
    }

    fetch(`${getBase()}/divergencias/corrigir-baixa`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
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
        .catch(() => alert('Erro de comunicação.'));
}

// ── Estorno de baixas ──────────────────────────────────────────────────────

function estornarBaixaPedido(idPedido) {
    if (!confirm('Deseja realmente estornar todas as baixas locais deste pedido?')) return;

    fetch(`${getBase()}/divergencias/estornar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify({ id_pedido: idPedido })
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                window.location.reload();
            } else {
                alert(res.error || 'Erro ao estornar.');
            }
        })
        .catch(() => alert('Erro de comunicação.'));
}

function estornarBaixaDetalhe(idDetalhe) {
    if (!confirm('Deseja realmente estornar esta baixa local?')) return;

    fetch(`${getBase()}/baixas/estornar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify({ id_detalhe: idDetalhe })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Baixa estornada!');
                location.reload();
            } else {
                alert('Erro: ' + (data.error || 'Falha ao estornar.'));
            }
        })
        .catch(() => alert('Erro de comunicação.'));
}

// ── Edição inline de baixa ─────────────────────────────────────────────────

function editarBaixa(idDetalhe) {
    document.getElementById(`valor-display-${idDetalhe}`).style.display = 'none';
    const input = document.getElementById(`input-baixa-${idDetalhe}`);
    input.style.display = 'inline-block';
    input.focus();
    document.getElementById(`btn-edit-${idDetalhe}`).style.display = 'none';
    document.getElementById(`btn-save-${idDetalhe}`).style.display = 'inline-block';
    document.getElementById(`btn-cancel-${idDetalhe}`).style.display = 'inline-block';
}

function cancelarEdicaoBaixa(idDetalhe) {
    document.getElementById(`valor-display-${idDetalhe}`).style.display = 'inline-block';
    document.getElementById(`input-baixa-${idDetalhe}`).style.display = 'none';
    document.getElementById(`btn-edit-${idDetalhe}`).style.display = 'inline-block';
    document.getElementById(`btn-save-${idDetalhe}`).style.display = 'none';
    document.getElementById(`btn-cancel-${idDetalhe}`).style.display = 'none';
}

function salvarBaixa(idDetalhe) {
    const novoValor = parseFloat(document.getElementById(`input-baixa-${idDetalhe}`).value);
    if (isNaN(novoValor) || novoValor < 0) {
        alert('Valor inválido.');
        return;
    }

    fetch(`${getBase()}/baixas/editar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify({ id_detalhe: idDetalhe, valor: novoValor })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + (data.error || 'Falha ao editar.'));
            }
        })
        .catch(() => alert('Erro de comunicação.'));
}
