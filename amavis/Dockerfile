FROM debian:bookworm-slim 

LABEL maintainer = "Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "Amavis & ClamAV dockerized for AgentJ"
LABEL description = "Starts Amavis with DB connection, uses local ClamAV as AV scanner"


RUN --mount=type=cache,id=apt-cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,id=apt-lib,target=/var/lib/apt,sharing=locked \
    --mount=type=cache,id=debconf,target=/var/cache/debconf,sharing=locked \
    apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    amavisd-new \
    arj \
    cabextract \
    clamav \
    clamav-daemon \
    cpio \
    lhasa \
    libdbi-perl \
    libjson-perl \
    liblwp-protocol-https-perl \
    libwww-perl \
    lzop \
    nomarch \
    p7zip \
    rpm \
    spamassassin \
    spamc \
    spamd \
    gnupg \
    libmail-spf-perl \
    libencode-detect-perl \
    libio-socket-ssl-perl \
    libnet-patricia-perl \
    libdbd-mysql-perl \
    #unrar \
    pyzor  \
    razor \
    supervisor  \ 
    tzdata  \
    iproute2 \
    cron \
    patch  


COPY amavisd.patch /root/amavisd.patch
RUN cd /usr/share/perl5/Amavis && patch -p1 < /root/amavisd.patch

COPY conf/clamav/clamd.conf /etc/clamav/clamd.conf
RUN /usr/bin/freshclam --log=/var/log/clamav/freshclam.log \
        --daemon-notify=/etc/clamav/clamd.conf \
        --config-file=/etc/clamav/freshclam.conf

COPY conf/spamassassin/local.cf /etc/mail/spamassassin/local.cf
RUN /usr/bin/sa-update

COPY conf/supervisor/supervisord.conf /etc/supervisord.conf
COPY conf/amavis/*.conf /etc/
COPY conf/cron/agentj-amavis /etc/cron.d/agentj-amavis
COPY entrypoint.sh /entrypoint.sh
COPY healthcheck.sh /healthcheck.sh

EXPOSE 10024/tcp

CMD [ "supervisord", "-c", "/etc/supervisord.conf" ]
ENTRYPOINT [ "/entrypoint.sh" ]
HEALTHCHECK --start-period=60s CMD [ "/healthcheck.sh" ]
