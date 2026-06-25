<?php
/**
 * Templates HTML utilizados pelo JavaScript para injeção via Client-Side Rendering (CSR).
 * Inclua este arquivo no final do <body> de páginas que geram componentes no frontend.
 */
?>

<!-- Template do Botão "Assumir Cobrança" (Tela Contas a Receber) -->
<template id="tpl-btn-cobrar-cr">
    <button class="cr-btn-cobrar" data-cob-id="@{{id}}" data-cob-tipo="@{{tipo}}" title="@{{btnTitle}}" style="display:flex; align-items:center; gap:8px; padding:8px 16px; background:transparent; border:none; color:#334155; font-size:13px; cursor:pointer; width:100%; text-align:left; transition:background 0.2s;" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
        <x-icons.icon-6 width="2" height="15" style="pointer-events:none;" />
        @{{btnTitle}}
    </button>
</template>

<!-- Template do Botão "Cobrar" (Tela Minhas Cobranças) -->
<template id="tpl-btn-cobrar-cob">
    <button class="btn-cobrar btn-puxar" data-id="@{{id}}" data-tipo="@{{tipo}}">
        <x-icons.icon-6 width="2" height="14" style="pointer-events:none" />
        @{{textoBtn}}
    </button>
</template>

<!-- Template do Botão "Em Cobrança" Desativado (Tela Minhas Cobranças) -->
<template id="tpl-btn-cobrar-disabled">
    <button class="btn-cobrar" disabled>Em Cobrança</button>
</template>
