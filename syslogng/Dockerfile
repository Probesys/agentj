FROM alpine:latest

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "AgentJ logs server"
LABEL description = "A central log server for AgentJ stack services"

RUN apk --update add \
    syslog-ng \
    syslog-ng-json \
    syslog-ng-scl \
    syslog-ng-sql \
    syslog-ng-tags-parser \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/* /usr/share/man/??  /usr/share/man/??_*

COPY conf/syslog-ng.conf /etc/syslog-ng/syslog-ng.conf

EXPOSE 514/udp
EXPOSE 601/tcp
EXPOSE 6514/tcp

VOLUME [ "/var/log/" ]

HEALTHCHECK --interval=2m --timeout=3s --start-period=30s CMD /usr/sbin/syslog-ng-ctl stats || exit 1

ENTRYPOINT [ "/usr/sbin/syslog-ng", "-F" ]