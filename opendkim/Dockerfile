FROM debian:bookworm-slim

RUN apt update
RUN apt install -y opendkim libopendbx1-mysql rsyslog
COPY opendkim.conf /etc/opendkim.conf
COPY TrustedHosts /etc/opendkim_sign_hosts
COPY stubdns /srv/stubdns
EXPOSE 8891
CMD rsyslogd & /usr/sbin/opendkim -x /etc/opendkim.conf -f -v
