{{-- _tab_financeiro.blade.php
     Tabela de Contatos Financeiros
     Variáveis esperadas: $aba --}}

<div class="card">
    <div class="table-responsive">
        <table class="cr-table" id="table-financeiro">
            <thead>
                <tr>
                    <th></th>
                    <th data-sort="az" class="sortable sortable-header">Contato Financeiro <span class="sort-icon"></span></th>
                    <th data-sort="qtd" class="center-col sortable sortable-header">Qtd. Pedidos <span class="sort-icon"></span></th>
                    <th data-sort="valor" class="valor-col sortable sortable-header">Total em Aberto <span class="sort-icon"></span></th>
                    <th data-sort="venc" class="date-col sortable sortable-header">Vencimento Mais Antigo <span class="sort-icon"></span></th>
                    <th class="col-status-cobranca">Status Cobrança</th>
                    <th class="cr-col-acoes">Ações</th>
                </tr>
            </thead>
            <tbody id="tbody-financeiro">
                <!-- JS vai preencher -->
            </tbody>
        </table>
    </div>
</div>
