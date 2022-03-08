FROM alpine:latest

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "AgentJ logs server"
LABEL description = "A central log server for AgentJ stack services"

RUN apk --update add \
    dcron \
    syslog-ng \
    syslog-ng-json \
    syslog-ng-scl \
    syslog-ng-sql \
    syslog-ng-tags-parser \
    tzdata \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/* /usr/share/man/??  /usr/share/man/??_*

COPY conf/syslog-ng.conf /etc/syslog-ng/syslog-ng.conf
COPY entrypoint.sh /entrypoint.sh

EXPOSE 514/udp
EXPOSE 601/tcp
EXPOSE 6514/tcp

VOLUME [ "/var/log/" ]

HEALTHCHECK --interval=2m --timeout=3s --start-period=30s CMD /usr/sbin/syslog-ng-ctl stats || exit 1
ENTRYPOINT [ "/entrypoint.sh" ]
CMD [ "/usr/sbin/syslog-ng", "-F" ]
