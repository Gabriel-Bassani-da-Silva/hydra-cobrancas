# Refactoring Arquitetural — Hydra Cobranças

Reorganizar o projeto para separar Views de páginas multi-aba em subpastas, eliminar CSS interno (inline styles e `<style>` em HTML/JS), garantir que todo CSS venha do SASS compilado, mover lógica JS de `<script>` inline para arquivos `.js`, e extrair helpers PHP reutilizáveis para fora das views.

---

## Problemas Identificados

### 1. Views — Páginas monolíticas
- `contas_receber.blade.php` — 608 linhas, contém 5 abas (Clientes, Representantes, Pedidos, Pedras, Baixas)
- `contatos.blade.php` — 513 linhas, contém abas (Clientes sem tel, Pedras, Contatos Financeiros)
- `perfil.blade.php` — 236 linhas, contém abas (Clientes, Representantes, Minhas Baixas)

### 2. JS inline nos Blades
- `contas_receber.blade.php` linhas 485–603: funções `setBaixasFiltro`, `abrirModalDivergencias`, `abrirModalCorrigir`, `confirmarCorrecaoBaixa`, `estornarBaixaPedido`, `estornarBaixa`, `editarBaixa`, `cancelarEdicaoBaixa`, `salvarBaixa` todas em `<script>` inline
- `app.blade.php` linhas 66–84: inicialização do WebSockets inline
- `perfil.blade.php` linha 231: `const BASE_URL = "..."` inline

### 3. Estilos inline no JS
- `baixa_manual.js`: geração de HTML com dezenas de `style="..."` hardcoded
- `modal_detalhes_contas.blade.php`: toda a geração de HTML feita em PHP com `style="..."` inline

### 4. Helpers PHP duplicados nas Views
- `formatPhone()` definida em `contatos.blade.php`
- `formatarCpfCnpj()` definida em `perfil.blade.php`
- `formatCurrencyComponent()`, `formatDateComponent()` definidas em `modal_detalhes_contas.blade.php`
- Cada view define suas próprias funções helper

---

## Proposta de Nova Estrutura

### Views (Blade)
```
views/
  layouts/
    app.blade.php               ← mover JS Echo para arquivo externo
  pages/
    contas_receber/
      index.blade.php           ← shell com tabs + includes
      _tab_clientes.blade.php
      _tab_representantes.blade.php
      _tab_pedras.blade.php
      _tab_pedidos.blade.php
      _tab_baixas.blade.php
    contatos/
      index.blade.php           ← shell com tabs + includes
      _tab_sem_telefone.blade.php
      _tab_pedras.blade.php
      _tab_financeiros.blade.php
    perfil/
      index.blade.php           ← shell com tabs + includes
      _tab_clientes.blade.php
      _tab_representantes.blade.php
      _tab_baixas.blade.php
  components/           ← sem alteração (já está bem)
```

### JS
```
js/
  baixa_manual.js       ← sem inline styles, apenas classes CSS
  contas_receber.js     ← sem alteração estrutural
  contatos.js           ← sem alteração estrutural
  perfil.js             ← sem alteração estrutural
  cobrancas.js          ← sem alteração estrutural
  pages/
    contas_receber_baixas.js   ← extrair as funções inline do blade
    echo_init.js               ← extrair a init do Websocket do layout
```

### SASS
```
sass/pages/
  _contas_receber.scss  ← adicionar classes que estão apenas como inline styles no JS
  _baixa_manual.scss    ← classes para o modal de baixa (substituir styles inline)
```

### PHP — Helpers
```
backend/
  Helpers/
    FormatHelper.php    ← formatPhone(), formatarCpfCnpj(), formatCurrency(), formatDate()
```

---

## Proposta de Mudanças

### Fase 1 — Extrair JS inline do Blade ← IMPACTO BAIXO, GANHO ALTO

#### [MODIFY] contas_receber.blade.php
- Remover o bloco `<script>` das linhas 485–603
- Criar `js/pages/contas_receber_baixas.js` com as funções extraídas
- Adicionar `<script src="{{ asset('js/pages/contas_receber_baixas.js') }}">` no lugar

#### [NEW] js/pages/contas_receber_baixas.js
- Funções: `setBaixasFiltro`, `abrirModalDivergencias`, `abrirModalCorrigir`, `fecharModalCorrigirBaixa`, `confirmarCorrecaoBaixa`, `estornarBaixaPedido`, `estornarBaixa` (versão CR), `editarBaixa`, `cancelarEdicaoBaixa`, `salvarBaixa`

#### [NEW] js/pages/echo_init.js
- Extrair inicialização Pusher/Echo do `app.blade.php` linhas 66–84

#### [MODIFY] app.blade.php
- Substituir `<script>` de WebSocket por `<script src="{{ asset('js/pages/echo_init.js') }}">`

---

### Fase 2 — Separar Views em subpastas ← IMPACTO MÉDIO

#### [NEW] views/pages/contas_receber/ (pasta)
- `index.blade.php` — contém: header, search, tabs, sub-tabs, `@include` de cada aba
- `_tab_clientes.blade.php` — tabela `#table-padrao` para clientes
- `_tab_representantes.blade.php` — tabela `#table-padrao` para representantes
- `_tab_pedras.blade.php` — tabela `#table-padrao` para pedras
- `_tab_pedidos.blade.php` — tabela `#table-pedidos`
- `_tab_baixas.blade.php` — painéis baixas + divergências + modais inline (corrigir baixa)

#### [DELETE] views/pages/contas_receber.blade.php

#### [NEW] views/pages/contatos/ (pasta)
- `index.blade.php` — header, tabs, includes
- `_tab_sem_telefone.blade.php`
- `_tab_pedras.blade.php`
- `_tab_financeiros.blade.php`
- `_tab_financeiros_rep.blade.php`

#### [DELETE] views/pages/contatos.blade.php

#### [NEW] views/pages/perfil/ (pasta)
- `index.blade.php`
- `_tab_cobrancas.blade.php` (reutilizado por clientes e representantes)
- `_tab_baixas.blade.php`

#### [DELETE] views/pages/perfil.blade.php

---

### Fase 3 — Inline styles → Classes SASS ← IMPACTO MÉDIO

#### [MODIFY] js/baixa_manual.js
- Substituir todos os `style="..."` por classes CSS
- Ex: `style="color:#ef4444; font-weight: 600;"` → `class="valor-devendo"`
- Ex: `style="background: #f8fafc; padding: 10px 15px; border-left: 3px solid #3b82f6;"` → `class="parcelas-detail-inner"`

#### [NEW] sass/pages/_baixa_manual.scss
- Criar todas as classes necessárias para o modal de baixa

#### [MODIFY] sass/pages/_contas_receber.scss
- Adicionar classes faltantes que estão apenas como inline no JS/PHP

---

### Fase 4 — PHP Helpers reutilizáveis ← IMPACTO BAIXO

#### [NEW] backend/Helpers/FormatHelper.php
```php
<?php
namespace App\Helpers;

class FormatHelper {
    public static function phone(string $num): string { ... }
    public static function document(string $doc): string { ... }
    public static function currency(float $val): string { ... }
    public static function date(string $date): string { ... }
}
```

#### [MODIFY] views/pages/contatos/index.blade.php
- Remover `function formatPhone()` inline
- Usar `App\Helpers\FormatHelper::phone()`

#### [MODIFY] views/pages/perfil/index.blade.php
- Remover `function formatarCpfCnpj()` inline
- Usar `App\Helpers\FormatHelper::document()`

#### [MODIFY] views/components/modal_detalhes_contas.blade.php
- Remover `function formatCurrencyComponent()` e `formatDateComponent()` inline
- Usar `FormatHelper::currency()` e `FormatHelper::date()`

---

## Ordem de Execução Sugerida

1. **Fase 1** — JS inline → arquivos externos (sem risco de quebrar nada)
2. **Fase 4** — PHP Helpers (sem risco de quebrar nada)
3. **Fase 3** — SASS + remover inline styles do JS
4. **Fase 2** — Separar views em subpastas (maior esforço, risco médio)

## Open Questions

> [!IMPORTANT]
> **Escopo**: Posso executar todas as fases de uma vez ou prefere uma fase por vez para validar progressivamente?

> [!IMPORTANT]
> **Fase 2 — Controllers**: Ao renomear `contas_receber.blade.php` para `contas_receber/index.blade.php`, o `return view('pages.contas_receber', ...)` nos controllers precisará mudar para `return view('pages.contas_receber.index', ...)`. Confirmar que posso fazer isso.

> [!NOTE]
> **Inline styles no modal_detalhes_contas.blade.php**: O arquivo gera HTML em PHP puro com strings concatenadas. A refatoração completa desse arquivo é trabalhosa. Posso manter o arquivo como está e focar apenas no `baixa_manual.js` e no blade de contas_receber para a Fase 3?
