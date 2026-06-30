{{-- _tab_pedidos.blade.php
     Tabela de Pedidos
     Variáveis esperadas: $isPagos --}}

<div class="card">
    <div class="table-responsive">
        <table class="cr-table" id="table-pedidos">
            <thead>
                <tr>
                    <th></th>
                    <th>Documento</th>
                    <th data-sort="az" class="sortable sortable-header">Cliente <span class="sort-icon"></span></th>
                    <th>Representante</th>
                    <th data-sort="qtd" class="center-col sortable sortable-header">Qtd. Parcelas <span class="sort-icon"></span></th>
                    <th data-sort="valor" class="valor-col sortable sortable-header"><?= $isPagos ? 'Valor Total' : 'Valor Restante' ?> <span class="sort-icon"></span></th>
                    <th data-sort="venc" class="date-col sortable sortable-header">Vencimento <span class="sort-icon"></span></th>
                    <th data-sort="situacao" class="text-center sortable sortable-header-middle">Situação <span class="sort-icon"></span></th>
                    <th class="cr-col-acoes">Ações</th>
                </tr>
            </thead>
            <tbody id="tbody-pedidos">
                <!-- JS vai preencher -->
            </tbody>
        </table>
    </div>
</div>
