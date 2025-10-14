FROM php:8.0-fpm

RUN apt-get update && apt-get install -y nginx cron nano procps unzip git \
    && docker-php-ext-install pdo_mysql mysqli

# Increase PHP memory limit to 512M
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini
 
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY default.conf /etc/nginx/sites-available/default

RUN mkdir -p /app
COPY app/ /app/

WORKDIR /app
RUN composer install --no-interaction --optimize-autoloader

# Copia e configura permissões do script de inicialização
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN touch /app/logs/cron.log
RUN echo '* */12 * * * root php "/app/cli/sintoniza" >> /app/logs/cron.log 2>&1' >> /etc/crontab

RUN chown -R www-data:www-data /app && chmod -R 755 /app

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
