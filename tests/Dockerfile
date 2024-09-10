FROM debian:bookworm-slim

RUN apt update 

ARG UID=1001
ARG GID=1001

RUN groupadd -g $GID mailtester
RUN useradd -u $UID -g $GID mailtester

RUN apt install -y swaks opensmtpd curl opensmtpd-filter-dkimsign dnsmasq

COPY dkim.priv /srv/dkim.priv
RUN echo 'smtp.test' > /etc/mailname
COPY smtpd.conf /etc/smtpd.conf
COPY dnsmasq.conf /etc/dnsmasq.conf

CMD smtpd -d & dnsmasq -d
