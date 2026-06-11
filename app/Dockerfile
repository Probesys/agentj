FROM debian:trixie-slim AS base

SHELL [ "/bin/sh", "-e", "-c" ]

RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    ca-certificates \
    curl \
    gnupg \
    composer \
    git \
    nodejs \
    php8.4 \
    php8.4-bz2 \
    php8.4-cli \
    php8.4-common \
    php8.4-curl \
    php8.4-fpm \
    php8.4-gd \
    php8.4-ldap \
    php8.4-intl \
    php8.4-mbstring \
    php8.4-mysql \
    php8.4-opcache \
    php8.4-xml \
    php8.4-zip \
    tzdata \
    yarnpkg \
    zip

RUN rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/
COPY --chown=www-data:www-data . /var/www/
RUN chown www-data:www-data /var/www

USER www-data:www-data
RUN composer install --ignore-platform-reqs --no-scripts --no-cache
RUN yarnpkg install
RUN yarnpkg encore production


FROM debian:trixie-slim AS run

RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    ca-certificates \
    cron \
    libssl3 \
    mariadb-client \
    nginx-light \
    openssl \
    perl \
    php8.4 \
    php8.4-bz2 \
    php8.4-cli \
    php8.4-common \
    php8.4-ctype \
    php8.4-curl \
    php8.4-dom \
    php8.4-fileinfo \
    php8.4-fpm \
    php8.4-gd \
    php8.4-iconv \
    php8.4-intl \
    php8.4-ldap \
    php8.4-mbstring \
    php8.4-opcache \
    php8.4-mysql \
    php8.4-simplexml \
    php8.4-sysvsem \
    php8.4-tokenizer \
    php8.4-xml \
    php8.4-zip \
    sudo \
    supervisor \
    tzdata


COPY --chown=www-data:www-data --from=base /var/www/ /var/www/agentj
WORKDIR /var/www/agentj

RUN rm -rf /var/lib/apt/lists/*
RUN mkdir -p /var/run/php/ && touch /var/run/php/php84-fpm.pid
RUN rm -rf node_modules

COPY docker/conf/cron/agentj /etc/cron.d/agentj
COPY docker/conf/nginx/nginx.conf /etc/nginx/sites-enabled/default
COPY docker/conf/php-fpm/www.conf /etc/php/8.4/fpm/pool.d/www.conf
COPY docker/conf/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/setup-env.sh /setup-env.sh
COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/entrypoint-worker.sh /entrypoint-worker.sh
COPY docker/files/amavisd-release /usr/local/bin/amavisd-release

EXPOSE 9000

ENTRYPOINT [ "/entrypoint.sh" ]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
