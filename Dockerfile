# Imagen base PHP CLI (más simple que Apache)
FROM php:8.3-cli

ENV DEBIAN_FRONTEND=noninteractive

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libssl-dev libcurl4-openssl-dev pkg-config \
    libzip-dev libpng-dev libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql mbstring zip

# Instalar MongoDB extension
RUN pecl channel-update pecl.php.net && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb

# Verificar instalación
RUN php -m | grep -E '(mongodb|mysqli)' && \
    echo "✓ Extensiones instaladas correctamente"

# Copiar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiar dependencias
COPY composer.json composer.lock* ./

# Instalar dependencias
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-req=ext-mongodb

# Copiar código fuente
COPY . .

# Crear directorios con permisos
RUN mkdir -p img uploads pdf/temp && \
    chmod -R 777 img uploads pdf/temp

# Crear script de inicio
RUN printf '#!/bin/bash\n\
set -e\n\
\n\
PORT=${PORT:-8080}\n\
\n\
echo "================================="\n\
echo "  Iniciando servidor PHP"\n\
echo "  Puerto: $PORT"\n\
echo "  Directorio: $(pwd)"\n\
echo "================================="\n\
\n\
echo ""\n\
echo "Archivos disponibles:"\n\
ls -lah | grep -E "\\.php$" | head -n 10\n\
echo ""\n\
\n\
echo "Extensiones PHP cargadas:"\n\
php -m | grep -E "(mongodb|mysqli)"\n\
echo ""\n\
\n\
echo "Iniciando servidor en 0.0.0.0:$PORT"\n\
exec php -S 0.0.0.0:$PORT -t . 2>&1\n' > /start.sh && \
chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]