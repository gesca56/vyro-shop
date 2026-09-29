#!/bin/sh
# VYRO — démarrage du conteneur
set -e
PORT="${PORT:-10000}"

if [ -z "$DB_HOST" ]; then
    # Pas de base externe : MariaDB intégrée (démo — les données repartent de zéro à chaque redémarrage)
    echo "[VYRO] Aucune base externe (DB_HOST vide) : MariaDB intégrée, mode DÉMO."
    mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld
    if [ ! -d /var/lib/mysql/mysql ]; then
        mariadb-install-db --user=mysql --datadir=/var/lib/mysql > /dev/null
    fi
    mariadbd --user=mysql --datadir=/var/lib/mysql &
    i=0
    until mariadb-admin ping --silent 2>/dev/null; do
        i=$((i + 1))
        [ "$i" -gt 60 ] && echo "[VYRO] MariaDB ne démarre pas." && exit 1
        sleep 1
    done
    mariadb -e "CREATE DATABASE IF NOT EXISTS vyro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                CREATE USER IF NOT EXISTS 'vyro'@'%' IDENTIFIED BY 'vyro_local';
                GRANT ALL PRIVILEGES ON vyro.* TO 'vyro'@'%';
                FLUSH PRIVILEGES;"
    export DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=vyro DB_USER=vyro DB_PASS=vyro_local DB_SSL=0
fi

# Création des tables au premier démarrage, mises à jour ensuite, identifiants admin
php /var/www/html/docker/init-db.php

# Apache écoute sur le port imposé par l'hébergeur (Render : $PORT)
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "[VYRO] Site prêt sur le port ${PORT}."
exec apache2-foreground
