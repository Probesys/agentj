FROM debian:bookworm-slim AS base

WORKDIR /var/www/
COPY . /var/www/

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
    zip \
    && rm -rf /var/lib/apt/lists/* \
    && composer install --ignore-platform-reqs --no-scripts \
    && yarnpkg install \
    # && yarnpkg add @symfony/webpack-encore \
    && yarnpkg encore production

FROM debian:bookworm-slim AS run

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "AgentJ web app"
LABEL description = "AgentJ frontend to manage mails and senders white/black lists"

COPY --chown=www-data:www-data --from=base /var/www/ /var/www/agentj
WORKDIR /var/www/agentj

RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    cron \
    libssl3 \
    mariadb-client \
    nginx-light \
    opendkim \
    opendkim-tools \
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
    rsyslog \
    sudo \
    supervisor \
    tzdata \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/run/php/ && touch /var/run/php/php82-fpm.pid \
    && rm -rf node_modules \
    && mkdir -p /var/db/dkim

COPY docker/conf/cron/agentj /etc/cron.d/agentj
COPY docker/conf/nginx/nginx.conf /etc/nginx/sites-enabled/default
COPY docker/conf/opendkim/TrustedHosts /var/db/dkim/TrustedHosts
COPY docker/conf/opendkim/opendkim.conf /etc/opendkim/opendkim.conf
COPY docker/conf/php-fpm/www.conf /etc/php/8.2/fpm/pool.d/www.conf
COPY docker/conf/sudo/opendkim /etc/sudoers.d/opendkim
COPY docker/conf/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/files/.env /var/www/agentj/.env
COPY docker/files/amavisd-release /usr/local/bin/amavisd-release
COPY docker/files/deldomainkey.sh /usr/local/bin/deldomainkey.sh
COPY docker/files/gendomainkey.sh /usr/local/bin/gendomainkey.sh

VOLUME /var/db/dkim

EXPOSE 9000
EXPOSE 8891

ENTRYPOINT [ "/entrypoint.sh" ]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
