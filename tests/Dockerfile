FROM debian:bookworm-slim

RUN apt update
RUn apt install -y swaks opensmtpd mariadb-client curl dnsmasq
COPY dnsmasq.conf /etc/dnsmasq.conf
COPY smtpd.conf /etc/smtpd.conf
RUN echo 'smtp.test' > /etc/mailname
COPY eicar.com.txt /srv/eicar.com.txt
COPY sql/*.sql /srv/sql/
COPY testmail.sh /usr/local/sbin/testmail.sh

CMD sh /usr/local/sbin/testmail.sh & smtpd -F & dnsmasq -d
