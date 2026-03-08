FROM php:8.3-fpm-alpine

# System-Abhängigkeiten & PHP-Erweiterungen
RUN apk add --no-cache nginx supervisor \
    && docker-php-ext-install opcache

# Konfiguration kopieren
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/periodic-shredder.sh /usr/local/bin/shredder

RUN chmod +x /usr/local/bin/shredder

WORKDIR /var/www/html
# Wir kopieren den src Inhalt direkt
COPY src/ .

# Verzeichnisse anlegen und Rechte setzen
RUN mkdir -p /var/www/html/storage && \
    chown -R www-data:www-data /var/www/html/storage && \
    chmod -R 775 /var/www/html/storage

EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]