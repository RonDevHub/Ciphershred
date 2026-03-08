FROM php:8.3-fpm-alpine
RUN apk add --no-cache nginx supervisor dcron \
    && docker-php-ext-install opcache
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/periodic-shredder.sh /usr/local/bin/shredder
RUN chmod +x /usr/local/bin/shredder
WORKDIR /var/www/html
COPY src/ .
RUN mkdir -p /var/www/html/storage && chown -R www-data:www-data /var/www/html/storage
EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]