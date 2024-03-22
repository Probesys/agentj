FROM debian:bookworm-slim

RUN apt update
RUN apt install -y opendkim libopendbx1-mysql rsyslog
COPY opendkim.conf /etc/opendkim.conf
# used only during tests. cf test.yml
RUN touch /etc/opendkim.test.conf
EXPOSE 8891
RUN touch /var/log/syslog
# let rsyslogd start ; and actively wait for db
CMD rsyslogd & tail -f /var/log/syslog & while true ; do sleep 1; opendkim -f;  done
