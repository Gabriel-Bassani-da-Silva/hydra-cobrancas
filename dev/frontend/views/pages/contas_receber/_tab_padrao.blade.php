{{-- _tab_padrao.blade.php
     Tabela principal de Clientes / Representantes / Pedras (grupos: padrao, cheques, antecipados)
     Variáveis esperadas: $aba, $grupo --}}

<div class="card">
    <div class="table-responsive">
        <table class="cr-table" id="table-padrao">
            <thead>
                <tr>
                    <th></th>
                    <th data-sort="az" class="sortable sortable-header"><?= $aba === 'representantes' ? 'Representante' : 'Cliente' ?> <span class="sort-icon"></span></th>
                    <?php if ($aba === 'representantes'): ?><th>Clientes</th>@endif
                    <th data-sort="qtd" class="center-col sortable sortable-header">Qtd. Pedidos <span class="sort-icon"></span></th>
                    <th data-sort="valor" class="valor-col sortable sortable-header">Total em Aberto <span class="sort-icon"></span></th>
                    <th data-sort="venc" class="date-col sortable sortable-header">Vencimento Mais Antigo <span class="sort-icon"></span></th>
                    <th class="col-status-cobranca">Status Cobrança</th>
                    <th data-sort="tel" class="center-col sortable col-telefone sortable-header">Telefone <span class="sort-icon"></span></th>
                    <th class="cr-col-acoes">Ações</th>
                </tr>
            </thead>
            <tbody id="tbody-padrao">
                <!-- JS vai preencher -->
            </tbody>
        </table>
    </div>
</div>
