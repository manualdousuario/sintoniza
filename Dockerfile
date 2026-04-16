FROM shinsenter/php:8.4-fpm-nginx

ENV ENABLE_CRONTAB=1
ENV APP_PATH=/app
ENV DOCUMENT_ROOT=public
ENV TZ=UTC
ENV ENABLE_TUNING_FPM=1
ENV DISABLE_AUTORUN_SCRIPTS=0

COPY app/ ${APP_PATH}/
WORKDIR ${APP_PATH}

RUN composer config platform.php-64bit 8.4 && \
    composer install --no-interaction --optimize-autoloader --no-dev

COPY crontab /etc/crontab.d/lastfm
RUN chmod 0644 /etc/crontab.d/lastfm

COPY /startup/* /startup/
RUN chmod +x /startup/*

RUN chown -R www-data:www-data ${APP_PATH} && \
    chmod -R 755 ${APP_PATH}

EXPOSE 80