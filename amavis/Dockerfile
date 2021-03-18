FROM debian:stable-slim

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "Amavis & ClamAV dockerized for AgentJ"
LABEL description = "Starts Amavis with DB connection, uses local ClamAV as AV scanner"

RUN apt-get update -q --fix-missing &&\
    apt-get -y install --no-install-recommends \
    amavisd-new \
    arj \
    bzip2 \
    ca-certificates \
    cabextract \
    clamav \
    clamav-base \
    clamav-daemon \
    clamav-freshclam \
    clamdscan \
    cpio \
    file \
    gzip \
    iproute2 \
    lhasa \
    libdbd-mysql-perl \
    libdbi-perl \
    nomarch \
    patch \
    pax \
    spamassassin \
    spamc \
    supervisor \
    unrar-free \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

COPY conf/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY conf/amavis/50-user /etc/amavis/conf.d/50-user
COPY conf/clamav/clamd.conf /etc/clamav/clamd.conf
COPY entrypoint.sh /entrypoint.sh
COPY healthcheck.sh /healthcheck.sh
COPY amavisd-new.patch /root/amavisd-new.patch

RUN patch -p1 /usr/sbin/amavisd-new < /root/amavisd-new.patch

RUN mkdir /var/run/clamav && \
    chown clamav:clamav /var/run/clamav && \
    chmod 755 /var/run/clamav && \
    chmod 644 /etc/amavis/conf.d/* && \
    adduser clamav amavis \
    && adduser amavis clamav \
    && chown -R clamav:clamav /var/run/clamav

VOLUME [ "/var/lib/amavis/" ]
VOLUME [ "/var/lib/clamav/" ]

EXPOSE 10024/tcp

ENTRYPOINT [ "/entrypoint.sh" ]
CMD [ "supervisord", "-c", "/etc/supervisor/supervisord.conf" ]
HEALTHCHECK --start-period=60s CMD [ "/healthcheck.sh" ]
