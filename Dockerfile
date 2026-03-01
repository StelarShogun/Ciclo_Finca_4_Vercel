FROM php:8.2-apache

# Instalamos dependencias y los certificados del sistema (ca-certificates)
RUN apt-get update && \
    apt-get install -y unzip git zip libzip-dev ca-certificates && \
    docker-php-ext-install pdo pdo_mysql zip

# Actualizamos los certificados del sistema para que sean válidos
RUN update-ca-certificates

# Habilita mod_rewrite para Laravel
RUN a2enmod rewrite

COPY . /var/www/html

# Cambia el DocumentRoot de Apache a /var/www/html/public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Configura el directorio de trabajo
WORKDIR /var/www/html

# Instalamos Composer y las dependencias (muy importante)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# Cambia permisos para storage y bootstrap/cache (después de copiar los archivos)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Exponer el puerto 80
EXPOSE 80