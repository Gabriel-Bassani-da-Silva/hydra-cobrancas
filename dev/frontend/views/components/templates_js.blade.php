<?php
/**
 * Templates HTML utilizados pelo JavaScript para injeção via Client-Side Rendering (CSR).
 * Inclua este arquivo no final do <body> de páginas que geram componentes no frontend.
 */
?>

<!-- Template do Botão "Assumir Cobrança" (Tela Contas a Receber) -->
<template id="tpl-btn-cobrar-cr">
    <button class="cr-btn-cobrar" data-cob-id="@{{id}}" data-cob-tipo="@{{tipo}}" title="@{{btnTitle}}" style="display:flex; align-items:center; gap:8px; padding:8px 16px; background:transparent; border:none; color:#334155; font-size:13px; cursor:pointer; width:100%; text-align:left; transition:background 0.2s;" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15" style="pointer-events:none;">
            <line x1="12" y1="1" x2="12" y2="23"></line>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
        @{{btnTitle}}
    </button>
</template>

<!-- Template do Botão "Cobrar" (Tela Minhas Cobranças) -->
<template id="tpl-btn-cobrar-cob">
    <button class="btn-cobrar btn-puxar" data-id="@{{id}}" data-tipo="@{{tipo}}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" style="pointer-events:none">
            <line x1="12" y1="1" x2="12" y2="23"></line>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
        @{{textoBtn}}
    </button>
</template>

<!-- Template do Botão "Em Cobrança" Desativado (Tela Minhas Cobranças) -->
<template id="tpl-btn-cobrar-disabled">
    <button class="btn-cobrar" disabled>Em Cobrança</button>
</template>
