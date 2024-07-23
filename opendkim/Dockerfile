FROM debian:bookworm-slim

RUN apt update
RUN apt install -y opendkim libopendbx1-mysql rsyslog iproute2
COPY entrypoint.sh /entrypoint.sh
COPY opendkim.conf /etc/opendkim.conf
COPY TrustedHosts /etc/trusted.opendkim.conf
# used only during tests. cf test.yml
RUN touch /etc/opendkim.test.conf
EXPOSE 8891
RUN touch /var/log/syslog
ENTRYPOINT [ "/entrypoint.sh" ]
# let rsyslogd start ; and actively wait for db
# if no rsyslogd, docker detect the fail and restart the container
CMD rsyslogd & tail -f /var/log/syslog & while true ; do sleep 1; opendkim -f;  done
