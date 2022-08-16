FROM alpine:3.15

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "Amavis & ClamAV dockerized for AgentJ"
LABEL description = "Starts Amavis with DB connection, uses local ClamAV as AV scanner"

RUN apk --no-cache add \
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
    dcron \
    file \
    freshclam \
    gpg-agent \
    gzip \
    iproute2 \
    perl-dbd-mysql \
    perl-db \
    perl-dbi \
    perl-io-socket-ssl \
    perl-mail-spf \
    patch \
    pax-utils \
    rsyslog \
    spamassassin \
    spamassassin-client \
    supervisor \
    tzdata \
    unzip \
    zip

COPY conf/supervisor/supervisord.conf /etc/supervisord.conf
COPY conf/amavis/amavisd.conf /etc/amavisd.conf
COPY conf/clamav/clamd.conf /etc/clamav/clamd.conf
COPY conf/cron/agentj-amavis /etc/cron.d/agentj-amavis
COPY entrypoint.sh /entrypoint.sh
COPY healthcheck.sh /healthcheck.sh
COPY amavisd.patch /root/amavisd.patch

RUN patch -p1 /usr/sbin/amavisd < /root/amavisd.patch
RUN /usr/bin/freshclam --log=/var/log/clamav/freshclam.log \
        --daemon-notify=/etc/clamav/clamd.conf \
        --config-file=/etc/clamav/freshclam.conf
RUN /usr/bin/sa-update

VOLUME [ "/var/amavis" ]

EXPOSE 10024/tcp

CMD [ "supervisord", "-c", "/etc/supervisord.conf" ]
ENTRYPOINT [ "/entrypoint.sh" ]
HEALTHCHECK --start-period=60s CMD [ "/healthcheck.sh" ]
