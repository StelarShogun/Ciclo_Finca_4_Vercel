FROM php:8.5-apache

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        unzip git zip curl supervisor \
        libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev libavif-dev libexif-dev && \
    docker-php-ext-install -j"$(nproc)" pdo pdo_mysql zip exif && \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-avif && \
    docker-php-ext-install -j"$(nproc)" gd && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache config
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Dependencias Node
COPY package.json package-lock.json* ./
RUN npm install

# Copiar proyecto
COPY . .

# Laravel deps
RUN composer install --no-interaction

# Build de assets Vite para producción (genera public/build/manifest.json)
RUN npm run build

# Permisos
RUN chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Supervisor: gestiona Apache + worker de cola + scheduler con autorestart.
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# EntryPoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80 5173

ENTRYPOINT ["docker-entrypoint.sh"]