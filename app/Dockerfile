FROM alpine:latest AS base

WORKDIR /var/www/
COPY . /var/www/
SHELL ["/bin/sh", "-o", "pipefail", "-c"]
RUN apk --update add \
    ca-certificates \
    curl \
    gnupg \
    composer \
    git \
    nodejs \
    php7 \
    php7-bz2 \
    php7-common \
    php7-curl \
    php7-fpm \
    php7-gd \
    php7-imap \
    php7-json \
    php7-mbstring \
    php7-mysqli \
    php7-opcache \
    php7-xml \
    php7-xmlrpc \
    php7-zip \
    tzdata \
    yarn \
    zip \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/* /usr/share/man/??  /usr/share/man/??_* \
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

RUN apk --update add \
    dcron \
    mariadb-client \
    libssl1.1 \
    openssl \
    perl \
    php7 \
    php7-bz2 \
    php7-common \
    php7-ctype \
    php7-curl \
    php7-dom \
    php7-fpm \
    php7-gd \
    php7-iconv \
    php7-imap \
    php7-json \
    php7-mbstring \
    php7-pdo_mysql \
    php7-opcache \
    php7-openssl \
    php7-session \
    php7-simplexml \
    php7-sysvsem \
    php7-tokenizer \
    php7-xml \
    php7-xmlrpc \
    php7-zip \
    sudo \
    supervisor \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/* /usr/share/man/??  /usr/share/man/??_* \
    && mkdir -p /var/run/php/ && touch /var/run/php/php7-fpm.pid \
    && rm -rf node_modules

COPY docker/conf/www.conf /etc/php7/php-fpm.d/www.conf
COPY docker/conf/supervisord.conf /etc/supervisord.conf
COPY docker/files/.env /var/www/agentj/.env
COPY docker/files/amavisd-release /usr/local/bin/amavisd-release
COPY docker/entrypoint.sh /entrypoint.sh

EXPOSE 9000

ENTRYPOINT [ "/entrypoint.sh" ]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
