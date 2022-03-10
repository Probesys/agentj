FROM alpine:latest

LABEL maintainer="Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "Postfix outgoing relay for AgentJ"
LABEL description = "Postfix server to relay good emails to their final server"

SHELL [ "/bin/sh", "-o", "pipefail", "-c" ]

RUN apk --update add \
    openssl \
    postfix \
    postfix-mysql \
    supervisor \
    rsyslog \
    tzdata \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/* /usr/share/man/??  /usr/share/man/??_* \
    && sed -i "s/inet_interfaces = localhost/inet_interfaces = all/g" /etc/postfix/main.cf

COPY conf/postfix/* /etc/postfix/
COPY entrypoint.sh /entrypoint.sh
COPY conf/supervisor/supervisord.conf /etc/supervisord.conf

EXPOSE 25/tcp

ENTRYPOINT [ "/entrypoint.sh" ] 
HEALTHCHECK --start-period=60s CMD postfix status > /dev/null 2>&1 || exit 1
CMD [ "supervisord", "-c", "/etc/supervisord.conf" ]
