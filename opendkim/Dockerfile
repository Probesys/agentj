FROM debian:stable-slim

LABEL maintainer="Sébastien Poher <sebastien.poher@probesys.com>"
LABEL name = "openDKIM service for AgentJ"
LABEL description = "Sign outgoing mails (used with relay container)"

RUN apt-get update -q --fix-missing && \
    apt-get -y install --no-install-recommends \
    opendkim \
    opendkim-tools \
    openssl \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

COPY ./conf/opendkim/opendkim.conf /etc/opendkim/opendkim.conf
COPY ./conf/opendkim/TrustedHosts /etc/opendkim/TrustedHosts
COPY ./conf/opendkim/SigningTable /etc/opendkim/SigningTable
COPY ./conf/opendkim/KeyTable /etc/opendkim/KeyTable
COPY ./conf/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY ./entrypoint.sh /entrypoint.sh

VOLUME /etc/opendkim
VOLUME /tmp/keys

EXPOSE 8891

ENTRYPOINT [ "/entrypoint.sh" ]
CMD [ "supervisord", "-c", "/etc/supervisor/supervisord.conf" ]