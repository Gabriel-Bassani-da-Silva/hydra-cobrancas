# Cobranças Hydra

Bem-vindo ao **Cobranças Hydra**, o sistema dedicado de gestão de cobranças, contas a receber e contatos, fortemente integrado com o ERP **Bling**. Este projeto foi construído para lidar com a sincronização de dados financeiros, processamento de webhooks e gestão de carteiras de cobrança.

## 🏗️ Estrutura do Projeto

O sistema é construído sobre o ecossistema Laravel e orquestrado inteiramente via Docker. 

### Infraestrutura (Docker)
O projeto roda nos seguintes containers principais (definidos no `docker-compose.yml`):
- **`hydra-app`**: O container principal rodando a aplicação Laravel (PHP-FPM).
- **`hydra-nginx`**: Servidor Web Nginx que roteia as requisições para o app.
- **`hydra-mysql`**: Banco de dados relacional MySQL (`cobrancas_hydra`) onde os dados do sistema (Pedidos, Cobranças, Clientes) ficam armazenados com garantias transacionais (ACID).
- **`hydra-redis`**: Banco de dados em memória (Key-Value) usado para Cache, Sessões e gestão da Fila de processamento.
- **`hydra-queue`**: Um container focado inteiramente em processar tarefas demoradas no background (Jobs do Laravel), impedindo que o sistema trave durante sincronizações longas.

### Organização de Código
No diretório `backend/app/Http/Controllers`, você encontrará os cérebros do sistema:
- `BlingController`: Lida com callbacks, webhooks e a autenticação OAuth do Bling.
- `CobrancaController`: Gestão da esteira de cobranças.
- `ContasReceberController`: Sincronização de contas, vinculação de representantes e baixas.
- `ContatosController`: Sincronização e importação de vendedores/clientes.

---

## ⚙️ Como Funciona (Arquitetura e Integração)

O **Cobranças Hydra** tem um fluxo de dados desenhado para ser resiliente a grandes volumes de integrações.

1. **Integração Bling:** O sistema possui rotas abertas (callbacks) e rotas ativas que se comunicam com a API do Bling. Ao buscar atualizações de Contatos ou Contas a Receber, o sistema salva as credenciais e busca grandes volumes de dados.
2. **Processamento Assíncrono (Filas):** Muitas ações de "Sincronização" (como importar contatos ou atualizar o status financeiro de múltiplos pedidos) não acontecem na hora em que o usuário clica no botão. Esses pedidos são enviados para o **Redis**, e o container `hydra-queue` os consome no background. Isso acelera a resposta da interface.
3. **Persistência de Dados:** Toda operação que afete os registros contábeis/financeiros cai no banco de dados MySQL para manter a consistência dos relatórios de divergências.

---

## 🚀 Como Rodar o Projeto (Setup Local)

Siga os passos abaixo para iniciar a infraestrutura pela primeira vez em seu ambiente de desenvolvimento.

### 1. Pré-requisitos
- Ter o **Docker** e o **Docker Compose** instalados na sua máquina.
- As portas `80`, `3306` e `6379` devem estar livres.

### 2. Configurando o Ambiente
1. Entre na pasta `backend`.
2. Copie o arquivo de exemplo do ambiente:
   ```bash
   cp .env.example .env
   ```
3. Garanta que as configurações do `.env` estejam apontando para os containers Docker corretamente:
   ```env
   APP_NAME="Cobranças Hydra"
   DB_HOST=db
   DB_DATABASE=cobrancas_hydra
   SESSION_DRIVER=redis
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   REDIS_HOST=redis
   ```

### 3. Subindo a Infraestrutura
Volte para a pasta raiz do projeto (onde está o `docker-compose.yml`) e suba os containers:
```bash
docker-compose up -d
```

### 4. Instalando Dependências e Banco de Dados
Com os containers rodando, acesse o container principal da aplicação para instalar o framework e construir o banco:
```bash
# Instala os pacotes do PHP
docker exec hydra-app composer install

# Gera a chave da aplicação (apenas na primeira vez)
docker exec hydra-app php artisan key:generate

# Roda as migrações para construir as tabelas no MySQL
docker exec hydra-app php artisan migrate
```

O sistema estará disponível em `http://localhost`.

---

## 🧩 Principais Módulos

- **Autenticação Bling (`/bling/auth`):** Renova e captura os tokens de acesso OAuth necessários para conversar com o ERP.
- **Contas a Receber (`/contas-receber`):** Exibe as faturas. Permite a sincronização massiva ou individual com o Bling, baixa manual e vinculação de representantes (vendedores).
- **Importação de Contatos (`/contatos/importar`):** Módulo robusto para importar contatos em lote, incluindo template de download, mapeamento de colunas e confirmação de carga.
- **Divergências (`/divergencias`):** Tela para checar inconsistências entre o ERP e a base local de cobranças.
