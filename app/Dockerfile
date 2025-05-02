FROM debian:bookworm-slim AS base

SHELL [ "/bin/sh", "-e", "-c" ]

RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    ca-certificates \
    curl \
    gnupg \
    composer \
    git \
    nodejs \
    php8.2 \
    php8.2-bz2 \
    php8.2-cli \
    php8.2-common \
    php8.2-curl \
    php8.2-fpm \
    php8.2-gd \
    php8.2-imap \
    php8.2-ldap \
    php8.2-intl \
    php8.2-mbstring \
    php8.2-mysql \
    php8.2-opcache \
    php8.2-xml \
    php8.2-zip \
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


FROM debian:bookworm-slim AS run

RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    ca-certificates \
    cron \
    libssl3 \
    mariadb-client \
    nginx-light \
    openssl \
    perl \
    php8.2 \
    php8.2-bz2 \
    php8.2-cli \
    php8.2-common \
    php8.2-ctype \
    php8.2-curl \
    php8.2-dom \
    php8.2-fileinfo \
    php8.2-fpm \
    php8.2-gd \
    php8.2-iconv \
    php8.2-imap \
    php8.2-intl \
    php8.2-ldap \
    php8.2-mbstring \
    php8.2-opcache \
    php8.2-mysql \
    php8.2-simplexml \
    php8.2-sysvsem \
    php8.2-tokenizer \
    php8.2-xml \
    php8.2-zip \
    sudo \
    supervisor \
    tzdata


COPY --chown=www-data:www-data --from=base /var/www/ /var/www/agentj
WORKDIR /var/www/agentj

RUN rm -rf /var/lib/apt/lists/*
RUN mkdir -p /var/run/php/ && touch /var/run/php/php82-fpm.pid
RUN rm -rf node_modules

COPY docker/conf/cron/agentj /etc/cron.d/agentj
COPY docker/conf/nginx/nginx.conf /etc/nginx/sites-enabled/default
COPY docker/conf/php-fpm/www.conf /etc/php/8.2/fpm/pool.d/www.conf
COPY docker/conf/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/files/amavisd-release /usr/local/bin/amavisd-release

EXPOSE 9000

ENTRYPOINT [ "/entrypoint.sh" ]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
