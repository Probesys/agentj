FROM alpine:latest

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "Amavis & ClamAV dockerized for AgentJ"
LABEL description = "Starts Amavis with DB connection, uses local ClamAV as AV scanner"

RUN apk --update add \
    amavis \
    unarj \
    bzip2 \
    ca-certificates \
    cabextract \
    clamav \
    clamav-clamdscan \
    clamav-db \
    clamav-daemon \
    clamav-libunrar \
    clamav-milter \
    clamav-scanner \
    cpio \
    file \
    freshclam \
    gzip \
    iproute2 \
    perl-db \
    perl-dbd-mysql \
    perl-dbi \
    perl-io-socket-ssl \
    patch \
    pax-utils \
    rsyslog \
    spamassassin \
    spamassassin-client \
    supervisor \
    unzip \
    zip \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/* /usr/share/man/??  /usr/share/man/??_*

COPY conf/supervisor/supervisord.conf /etc/supervisord.conf
COPY conf/amavis/amavisd.conf /etc/amavisd.conf
COPY conf/clamav/clamd.conf /etc/clamav/clamd.conf
COPY entrypoint.sh /entrypoint.sh
COPY healthcheck.sh /healthcheck.sh
COPY amavisd.patch /root/amavisd.patch

RUN patch -p1 /usr/sbin/amavisd < /root/amavisd.patch

RUN mkdir /var/run/clamav && \
    chown clamav:clamav /var/run/clamav && \
    chmod 755 /var/run/clamav && \
    chmod 644 /etc/amavisd.conf && \
    adduser clamav amavis \
    && adduser amavis clamav \
    && chown -R clamav:clamav /var/run/clamav

VOLUME [ "/var/lib/amavis/" ]
VOLUME [ "/var/lib/clamav/" ]

EXPOSE 10024/tcp

ENTRYPOINT [ "/entrypoint.sh" ]
CMD [ "supervisord", "-c", "/etc/supervisord.conf" ]
HEALTHCHECK --start-period=60s CMD [ "/healthcheck.sh" ]
