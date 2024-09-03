FROM debian:bookworm-slim

RUN apt update 
RUN apt install -y swaks opensmtpd mariadb-client curl opensmtpd-filter-dkimsign
COPY smtpd.conf /etc/smtpd.conf
COPY dkim.priv /srv/dkim.priv
RUN echo 'smtp.test' > /etc/mailname
# TODO maybe use volumes to allow easier testing
COPY eicar.com.txt /srv/eicar.com.txt
COPY sql/*.sql /srv/sql/
COPY testmail.sh /usr/local/sbin/testmail.sh
COPY fasttest_mail.sh /usr/local/sbin/fasttest_mail.sh

# CMD sh /usr/local/sbin/testmail.sh reinit_db | tee /srv/general.log & smtpd -d
# CMD sh /usr/local/sbin/fasttest_mail.sh reinit_db | tee /srv/general.log & smtpd -d
CMD smtpd -d
