FROM debian:bookworm-slim

RUN apt update ; apt install -y opendkim libopendbx1-mysql
RUN mkdir -p /var/db/dkim
COPY opendkim.conf /etc/opendkim.conf
# wrong
COPY TrustedHosts /var/db/dkim/TrustedHosts
EXPOSE 8891
CMD /usr/sbin/opendkim -x /etc/opendkim.conf -f -v
