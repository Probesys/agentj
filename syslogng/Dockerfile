FROM debian:bookworm-slim

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "AgentJ logs server"
LABEL description = "A central log server for AgentJ stack services"

RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    cron \
    syslog-ng-core \
    syslog-ng-scl \
    syslog-ng-mod-sql \
    tzdata \
    && rm -rf /var/lib/apt/lists/*

COPY conf/syslog-ng.conf /etc/syslog-ng/syslog-ng.conf
COPY conf/cron/syslogng-rotate /etc/cron.d/syslogng-rotate
COPY entrypoint.sh /entrypoint.sh

EXPOSE 514/udp

VOLUME [ "/var/log/" ]

HEALTHCHECK --interval=2m --timeout=3s --start-period=30s CMD /usr/sbin/syslog-ng-ctl stats || exit 1
ENTRYPOINT [ "/entrypoint.sh" ]
CMD [ "/usr/sbin/syslog-ng", "-F", "--no-caps" ]
