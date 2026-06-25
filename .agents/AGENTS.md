# Regras de Arquitetura do Projeto Hydra

Este projeto utiliza uma estrutura customizada do Laravel para separar o código da aplicação (desenvolvimento) do núcleo do framework. **Sempre respeite as regras abaixo ao editar caminhos ou rodar comandos.**

## Estrutura de Diretórios
- **`/framework/`**: É a raiz real do Laravel. Contém o `composer.json`, `.env`, `artisan`, `public/index.php`, `storage`, e `bootstrap`. Nenhum código de negócio do usuário (Controllers, Views, Models) fica aqui. **Para rodar comandos artisan ou composer, use sempre como diretório de trabalho `framework/`**.
- **`/dev/backend/`**: Contém a lógica de Backend (`app/`, `routes/`, `database/migrations/`).
- **`/dev/frontend/`**: Contém as Views (`views/`), CSS (`css/`) e JS (`js/`). As views ficam puras aqui sem passar pelo motor padrão do `resources/views`.
- **`docs/`**: Contém as documentações e exemplos de JSON retornado pelas APIs consumidas (ex: Bling API).

## Regras de Comportamento (AI)
1. Ao procurar o `composer.json` ou tentar rodar `php artisan`, lembre-se que eles estão na pasta `/framework/`.
2. Ao editar arquivos `.gitignore` ou `.dockerignore`, lembre-se de prefixar caminhos de vendors ou cache com `/framework/` (ex: `/framework/vendor/`).
3. O front-end usa pré-processador SASS. **NÃO use TailwindCSS** a menos que estritamente solicitado. Ao criar ou modificar estilos, edite os arquivos SCSS/SASS que serão compilados para gerar o CSS final (os arquivos compilados são incluídos diretamente no HTML).
4. O servidor Nginx no Docker (local) está configurado para apontar o root para `/var/www/html/framework/public` e ler arquivos CSS de `/var/www/html/dev/frontend/css/`. Nunca tente buscar o Nginx root na pasta `/backend/public`.
5. Em deploy de produção (ex: Railway), utiliza-se o `Dockerfile.prod` com a imagem `serversideup/php` que engloba Nginx+PHP na mesma imagem, já definindo o `WEB_DOCUMENT_ROOT` para o caminho do framework.
