FROM debian:stable-slim AS build

WORKDIR /var/www/html
COPY . /var/www/html/
SHELL ["/bin/bash", "-o", "pipefail", "-c"]
RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    ca-certificates \
    curl \
    gnupg \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - ; \
    echo "deb https://dl.yarnpkg.com/debian/ stable main" >> \
    /etc/apt/sources.list.d/yarn.list ; \
    apt-get update \
    && apt-get -y install --no-install-recommends \
    composer \
    git \
    nodejs \
    php7.3 \
    php7.3-bz2 \
    php7.3-cli \
    php7.3-common \
    php7.3-curl \
    php7.3-fpm \
    php7.3-gd \
    php7.3-imap \
    php7.3-json \
    php7.3-mbstring \
    php7.3-mysql \
    php7.3-opcache \
    php7.3-readline \
    php7.3-xml \
    php7.3-xmlrpc \
    php7.3-zip \
    yarn \
    zip \
    && rm -rf /var/lib/apt/lists/* \
    && composer install --ignore-platform-reqs --no-scripts \
    && yarn install \
    && yarn add @symfony/webpack-encore \
    && yarn encore production

FROM debian:stable-slim AS run

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "AgentJ web app"
LABEL description = "AgentJ frontend to manage mails and senders white/black lists"

COPY --chown=www-data:www-data --from=build /var/www/html/ /var/www/html/agentj
WORKDIR /var/www/html/agentj

RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    cron \
    mariadb-client \
    openssl \
    perl \
    php7.3 \
    php7.3-bz2 \
    php7.3-cli \
    php7.3-common \
    php7.3-curl \
    php7.3-fpm \
    php7.3-gd \
    php7.3-imap \
    php7.3-json \
    php7.3-mbstring \
    php7.3-mysql \
    php7.3-opcache \
    php7.3-readline \
    php7.3-xml \
    php7.3-xmlrpc \
    php7.3-zip \
    sudo \
    supervisor \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/run/php/ && touch /var/run/php/php7.3-fpm.pid \
    && rm -rf node_modules

COPY docker/conf/www.conf /etc/php/7.3/fpm/pool.d/www.conf
COPY docker/conf/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/files/.env /var/www/html/agentj/.env
COPY docker/files/amavisd-release /usr/local/bin/amavisd-release
COPY docker/db_init.sh /db_init.sh
COPY docker/entrypoint.sh /entrypoint.sh

EXPOSE 9000

ENTRYPOINT [ "/entrypoint.sh" ]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
