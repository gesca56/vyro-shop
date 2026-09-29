# VYRO — image de production (Render, ou tout hébergeur Docker)
FROM php:8.2-apache

# MariaDB : base intégrée utilisée seulement si aucune base externe n'est configurée (mode démo)
RUN apt-get update \
 && apt-get install -y --no-install-recommends mariadb-server ca-certificates \
 && docker-php-ext-install pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

# Apache : .htaccess actifs, nom de serveur, peu de processus (512 Mo de RAM sur l'offre gratuite)
RUN a2enmod headers \
 && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
 && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

COPY docker/php.ini /usr/local/etc/php/conf.d/vyro.ini
COPY docker/mariadb.cnf /etc/mysql/mariadb.conf.d/99-vyro.cnf
COPY docker/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf

COPY . /var/www/html/

RUN rm -f /var/www/html/config.local.php /var/www/html/database/vyro-export.sql \
 && mkdir -p /var/www/html/uploads/products /var/www/html/uploads/posts \
 && chown -R www-data:www-data /var/www/html/uploads \
 && sed -i 's/\r$//' /var/www/html/docker/entrypoint.sh \
 && chmod +x /var/www/html/docker/entrypoint.sh

ENV PORT=10000
EXPOSE 10000
CMD ["/var/www/html/docker/entrypoint.sh"]
