FROM alpine:latest AS base

WORKDIR /var/www/
COPY . /var/www/
SHELL ["/bin/sh", "-o", "pipefail", "-c"]
RUN echo "https://dl-cdn.alpinelinux.org/alpine/edge/testing/" >> /etc/apk/repositories \
    && apk --no-cache add \
    ca-certificates \
    curl \
    gnupg \
    composer \
    git \
    nodejs \
    php8 \
    php8-bz2 \
    php8-common \
    php8-curl \
    php8-fpm \
    php8-gd \
    php8-imap \
    php8-ldap \
    php8-intl \
    php8-json \
    php8-mbstring \
    php8-mysqli \
    php8-opcache \
    php8-xml \
    php8-zip \
    tzdata \
    yarn \
    zip \
    && composer install --ignore-platform-reqs --no-scripts \
    && yarn install \
    && yarn add @symfony/webpack-encore \
    && yarn encore production

FROM alpine:latest AS run

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "AgentJ web app"
LABEL description = "AgentJ frontend to manage mails and senders white/black lists"

# Ensure www-data user exists
RUN set -x ; \
  addgroup -g 82 -S www-data ; \
  adduser -u 82 -D -S -G www-data www-data && exit 0 ; exit 1

COPY --chown=www-data:www-data --from=base /var/www/ /var/www/agentj
WORKDIR /var/www/agentj

RUN echo "https://dl-cdn.alpinelinux.org/alpine/edge/testing/" >> /etc/apk/repositories \
    && apk --no-cache add \
    dcron \
    mariadb-client \
    libssl1.1 \
    nginx \
    opendkim \
    opendkim-utils \
    openssl \
    perl \
    php8 \
    php8-bz2 \
    php8-common \
    php8-ctype \
    php8-curl \
    php8-dom \
    php8-fileinfo \
    php8-fpm \
    php8-gd \
    php8-iconv \
    php8-imap \
    php8-intl \
    php8-json \
    php8-ldap \
    php8-mbstring \
    php8-pdo_mysql \
    php8-opcache \
    php8-openssl \
    php8-session \
    php8-simplexml \
    php8-sysvsem \
    php8-tokenizer \
    php8-xml \
    php8-zip \
    rsyslog \
    sudo \
    supervisor \
    tzdata \
    && mkdir -p /var/run/php/ && touch /var/run/php/php8-fpm.pid \
    && rm -rf node_modules \
    && mkdir -p /var/db/dkim

COPY docker/conf/cron/agentj /etc/cron.d/agentj
COPY docker/conf/nginx/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/conf/opendkim/TrustedHosts /var/db/dkim/TrustedHosts
COPY docker/conf/opendkim/opendkim.conf /etc/opendkim/opendkim.conf
COPY docker/conf/php-fpm/www.conf /etc/php8/php-fpm.d/www.conf
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
