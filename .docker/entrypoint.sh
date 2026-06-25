#!/bin/bash
set -e

# Se as pastas vendor e node_modules não existirem (ex: no primeiro run com volume mapeado), instala as dependências
if [ ! -d "vendor" ]; then
    echo "Instalando dependências do Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi



# Ajuste de permissões
echo "Ajustando permissões das pastas de armazenamento..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Se não houver arquivo .env e o .env.docker existir, cria um baseado nele
if [ ! -f ".env" ] && [ -f ".env.docker" ]; then
    echo "Criando arquivo .env a partir do .env.docker..."
    cp .env.docker .env
    php artisan key:generate --no-interaction
fi

# Aguardar o banco de dados estar disponível antes de rodar as migrations
echo "Aguardando banco de dados..."

# Carregar variáveis do .env para o ambiente
set -a
[ -f .env ] && . .env
set +a

# Tenta conectar no banco por até 30 segundos
for i in {1..30}; do
    if php -r "try { new PDO('mysql:host=db;dbname=' . (getenv('DB_DATABASE') ?: 'hydraRemake'), getenv('DB_USERNAME') ?: 'root', getenv('DB_PASSWORD') ?: 'secret'); exit(0); } catch (Exception \$e) { exit(1); }"; then
        echo "Banco de dados conectado!"
        break
    fi
    echo "Banco indisponível, aguardando 1s..."
    sleep 1
done

# Rodar as migrations
echo "Executando migrations..."
php artisan migrate --force

echo "Iniciando processo principal..."
# Executar o comando passado pro container (no caso, php-fpm)
exec "$@"
