FROM debian:bookworm-slim

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "Amavis & ClamAV dockerized for AgentJ"
LABEL description = "Starts Amavis with DB connection, uses local ClamAV as AV scanner"

SHELL [ "/bin/sh", "-e", "-c" ]

RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    amavisd-new \
    bzip2 \
    ca-certificates \
    cabextract \
    clamav \
    clamav-freshclam \
    clamdscan \
    clamav-daemon \
    clamav-milter \
    cpio \
    cron \
    file \
    gpg \
    gpg-agent \
    gzip \
    iproute2 \
    libdbd-mysql-perl \
    libdbi-perl \
    libio-socket-ssl-perl \
    libmail-spf-perl \
    patch \
    pax-utils \
    rsyslog \
    spamassassin \
    spamc \
    spamd \
    supervisor \
    tzdata \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

COPY conf/supervisor/supervisord.conf /etc/supervisord.conf
COPY conf/amavis/amavisd.conf /etc/amavisd.conf
COPY conf/clamav/clamd.conf /etc/clamav/clamd.conf
COPY conf/cron/agentj-amavis /etc/cron.d/agentj-amavis
COPY conf/spamassassin/local.cf /etc/mail/spamassassin/local.cf
COPY entrypoint.sh /entrypoint.sh
COPY healthcheck.sh /healthcheck.sh
COPY amavisd_conf.patch /root/amavisd_conf.patch
COPY amavisd_out_sql_log.patch /root/amavisd_out_sql_log.patch

RUN patch -p1 /usr/share/perl5/Amavis/Conf.pm < /root/amavisd_conf.patch
RUN patch -p1  /usr/share/perl5/Amavis/Out/SQL/Log.pm < /root/amavisd_out_sql_log.patch
RUN /usr/bin/freshclam --log=/var/log/clamav/freshclam.log \
        --daemon-notify=/etc/clamav/clamd.conf \
        --config-file=/etc/clamav/freshclam.conf
RUN /usr/bin/sa-update

VOLUME [ "/var/lib/amavis" ]

EXPOSE 10024/tcp

CMD [ "supervisord", "-c", "/etc/supervisord.conf" ]
ENTRYPOINT [ "/entrypoint.sh" ]
HEALTHCHECK --start-period=60s CMD [ "/healthcheck.sh" ]
