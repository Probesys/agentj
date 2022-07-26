FROM debian:stable-slim

LABEL maintainer="Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "Postfix incoming smtp for AgentJ"
LABEL description = "Postfix server to relay incoming mails to anti-spam stack"

SHELL [ "/bin/sh", "-e", "-c" ]

RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    postfix \
    postfix-mysql \
    postfix-policyd-spf-python \
    supervisor \
    rsyslog \
    tzdata \
    && rm -rf /var/lib/apt/lists/* \
    && sed -i "s/inet_interfaces = localhost/inet_interfaces = all/g" /etc/postfix/main.cf

COPY conf/postfix/* /etc/postfix/
COPY conf/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY conf/postifx-policyd-spf-python/policyd-spf.conf /etc/postfix-policyd-spf-python/policyd-spf.conf
COPY entrypoint.sh /entrypoint.sh

EXPOSE 25/tcp
EXPOSE 10025/tcp

VOLUME /etc/postfix
VOLUME /var/spool/postfix

ENTRYPOINT [ "/entrypoint.sh" ]
HEALTHCHECK --start-period=60s CMD postfix status >/dev/null || exit 1
CMD [ "supervisord", "-c", "/etc/supervisor/supervisord.conf" ]
