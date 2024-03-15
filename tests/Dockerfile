FROM debian:bookworm-slim

RUN apt update ; apt install -y swaks opensmtpd mariadb-client
COPY smtpd.conf /etc/smtpd.conf
RUN echo 'smtp.test' > /etc/mailname
COPY eicar.com.txt /srv/eicar.com.txt
COPY sql/*.sql /srv/sql/
COPY testmail.sh /usr/local/sbin/testmail.sh

CMD sh /usr/local/sbin/testmail.sh & smtpd -F
