# Usar imagen base de PHP 8.3 con Apache
FROM php:8.3-apache

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
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP básicas
RUN docker-php-ext-install mysqli pdo pdo_mysql mbstring curl

# Instalar extensión MongoDB (lo más importante)
RUN pecl install mongodb-1.19.3 \
    && docker-php-ext-enable mongodb

# Copiar Composer desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar composer.json primero (para aprovechar cache de Docker)
COPY composer.json composer.lock* ./

# Instalar dependencias de Composer (sin dev para producción)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copiar el resto de los archivos del proyecto
COPY . .

# Crear directorio para imágenes si no existe
RUN mkdir -p img && chmod -R 755 img

# Configurar permisos correctos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Habilitar mod_rewrite de Apache (necesario para URLs amigables)
RUN a2enmod rewrite

# Configurar Apache para usar el directorio correcto
RUN sed -i 's|/var/www/html|/var/www/html|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Exponer puerto 80 (Railway redireccionará automáticamente)
EXPOSE 80

# Variable de entorno para Railway (Railway usa PORT dinámico)
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2

# Comando para iniciar Apache
CMD ["apache2-foreground"]