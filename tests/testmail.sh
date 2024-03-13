#!/bin/sh

sleep 10

# insert test base
mariadb -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASSWORD $DB_NAME < /srv/sql/blocnormal_laissepasser.sql

# incoming mails from unknown adress
swaks --from 'root@smtp.test' --body 'IN' --to 'user@blocnormal.fr' -s $IN_SMTP
swaks --from 'root@smtp.test' --body 'IN' --to 'user@blocnormal.fr' -s $IN_SMTP --attach /srv/eicar.com.txt

swaks --from 'root@smtp.test' --body 'IN' --to 'user@laissepasser.fr' -s $IN_SMTP
swaks --from 'root@smtp.test' --body 'IN' --to 'user@laissepasser.fr' -s $IN_SMTP --attach /srv/eicar.com.txt

# TODO: check in db


# outgoing mails
#swaks --from 'user0@blocnormal.fr' --body 'OUT' --to 'root@smtp4dev'
