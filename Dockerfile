# Usar imagen base de PHP 8.3 con Apache
FROM php:8.3-apache

# Variables de entorno para la instalación
ENV DEBIAN_FRONTEND=noninteractive

# Instalar dependencias del sistema necesarias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    zip \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP básicas
RUN docker-php-ext-install mysqli pdo pdo_mysql mbstring zip

# Instalar extensión MongoDB ÚLTIMA VERSIÓN (2.x compatible)
RUN pecl channel-update pecl.php.net \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Verificar que MongoDB está instalado correctamente
RUN php -m | grep mongodb || (echo "ERROR: MongoDB extension not loaded" && exit 1)

# Copiar Composer desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar composer.json y composer.lock primero (para aprovechar cache de Docker)
COPY composer.json composer.lock* ./

# Limpiar cache de Composer y reinstalar dependencias
RUN composer clear-cache \
    && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --ignore-platform-req=ext-mongodb

# Copiar el resto de los archivos del proyecto
COPY . .

# Crear directorios necesarios con permisos
RUN mkdir -p img uploads pdf/temp \
    && chmod -R 755 img uploads pdf

# Configurar permisos correctos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar Apache correctamente
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Verificar que index.php existe
RUN ls -la /var/www/html/index.php || echo "WARNING: index.php not found!"

# Configurar DirectoryIndex para servir index.php por defecto
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    DirectoryIndex index.php index.html\n\
</Directory>' > /etc/apache2/conf-available/docker-php.conf \
    && a2enconf docker-php

# Exponer puerto 80
EXPOSE 80

# Variables de entorno
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2
ENV APACHE_DOCUMENT_ROOT=/var/www/html

# Comando de inicio simple (sin script personalizado que puede causar problemas)
CMD ["apache2-foreground"]