FROM debian:bookworm-slim

RUN apt update 
RUN apt install -y swaks opensmtpd mariadb-client curl dnsmasq opensmtpd-filter-dkimsign
COPY smtpd.conf /etc/smtpd.conf
COPY dkim.priv /srv/dkim.priv
RUN echo 'smtp.test' > /etc/mailname
COPY eicar.com.txt /srv/eicar.com.txt
COPY sql/*.sql /srv/sql/
COPY testmail.sh /usr/local/sbin/testmail.sh

CMD sh /usr/local/sbin/testmail.sh 'smtp.test' 25 reinit_test_db & smtpd -F & dnsmasq -d
