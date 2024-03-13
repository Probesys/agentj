FROM debian:bookworm-slim

RUN apt update ; apt install -y swaks opensmtpd mariadb-client
COPY smtpd.conf /etc/smtpd.conf
COPY sql/*.sql /srv/sql/
COPY vmailtable /etc/vmailtable
COPY eicar.com.txt /srv/eicar.com.txt
COPY testmail.sh /usr/local/sbin/testmail.sh

RUN echo smtp.test > /etc/mailname

CMD sh /usr/local/sbin/testmail.sh & smtpd -dv
