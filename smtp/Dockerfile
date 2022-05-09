FROM alpine:latest

LABEL maintainer="Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "Postfix incoming smtp for AgentJ"
LABEL description = "Postfix server to relay incoming mails to anti-spam stack"

SHELL [ "/bin/sh", "-o", "pipefail", "-c" ]

RUN apk --update add \
    bind-tools \
    postfix \
    postfix-mysql \
    supervisor \
    rsyslog \
    tzdata \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/* /usr/share/man/??  /usr/share/man/??_* \
    && sed -i "s/inet_interfaces = localhost/inet_interfaces = all/g" /etc/postfix/main.cf

COPY conf/postfix/* /etc/postfix/
COPY conf/supervisor/supervisord.conf /etc/supervisord.conf
COPY entrypoint.sh /entrypoint.sh

EXPOSE 25/tcp
EXPOSE 10025/tcp

VOLUME /etc/postfix
VOLUME /var/spool/postfix

ENTRYPOINT [ "/entrypoint.sh" ]
HEALTHCHECK --start-period=60s CMD postfix status >/dev/null || exit 1
CMD [ "supervisord", "-c", "/etc/supervisord.conf" ]
