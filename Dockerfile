FROM php:8.2-apache

RUN apt-get update && \
	apt-get install -y unzip git zip libzip-dev && \
	docker-php-ext-install pdo pdo_mysql zip

# Habilita mod_rewrite para Laravel
RUN a2enmod rewrite

COPY . /var/www/html

# Cambia el DocumentRoot de Apache a /var/www/html/public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Cambia permisos para storage y bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Configura el directorio de trabajo
WORKDIR /var/www/html

# Exponer el puerto 80
EXPOSE 80

# Opcional: Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
