FROM debian:stable-slim

LABEL maintainer="Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "Postfix outgoing relay for AgentJ"
LABEL description = "Postfix server to relay good emails to their final server"

SHELL [ "/bin/bash", "-o", "pipefail", "-c" ]

RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    opendkim \
    opendkim-tools \
    openssl \
    postfix \
    postfix-mysql \
    supervisor \
    rsyslog \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i "s/inet_interfaces = localhost/inet_interfaces = all/g" /etc/postfix/main.cf

COPY conf/postfix/* /etc/postfix/
COPY conf/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY conf/opendkim/opendkim.conf /etc/opendkim/opendkim.conf
COPY conf/opendkim/TrustedHosts /etc/opendkim/TrustedHosts
COPY conf/opendkim/SigningTable /etc/opendkim/SigningTable
COPY conf/opendkim/KeyTable /etc/opendkim/KeyTable
COPY conf/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY entrypoint.sh /entrypoint.sh

VOLUME /etc/opendkim

EXPOSE 25/tcp

ENTRYPOINT [ "/entrypoint.sh" ] 
HEALTHCHECK --start-period=60s CMD postfix status || exit 1 
CMD [ "supervisord", "-c", "/etc/supervisor/supervisord.conf" ]
