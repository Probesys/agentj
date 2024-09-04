#!/bin/sh

# wait for app to be started (for db migrations)
echo "waiting app"
while [ $(curl -so /dev/null -w '%{http_code}' http://$APP_HOST/login) -ne "200" ];
do
  echo -n '.'
  sleep 1
done
echo ' ok'

echo "---- check if reinit_db is set ----" 1>&2
echo "reinit_db: $1"
if [ "$1" = "reinit_db" ];
then
  echo 'clearing db and insert test data' 1>&2
  mariadb -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME < /tests/sql/test.sql
  [ "$?" -eq "0" ] || { echo 'failed to insert test data, exiting'; exit $?; }
fi

echo "---- test SQL query ----" 1>&2
SQL_QUERY="SELECT
             COALESCE(
               JSON_ARRAYAGG(
                 JSON_ARRAY(
                   GREATEST(
                     CAST(JSON_UNQUOTE(JSON_EXTRACT(u.quota, '$[0].quota_emails')) AS SIGNED),
                     CAST(JSON_UNQUOTE(JSON_EXTRACT(g.quota, '$[0].quota_emails')) AS SIGNED)
                   ),
                   GREATEST(
                     CAST(JSON_UNQUOTE(JSON_EXTRACT(u.quota, '$[0].quota_seconds')) AS SIGNED),
                     CAST(JSON_UNQUOTE(JSON_EXTRACT(g.quota, '$[0].quota_seconds')) AS SIGNED)
                   )
                 )
               ),
               '[]'
             ) as quota_array
           FROM
             users u
           LEFT JOIN user_groups ug ON u.id = ug.user_id
           LEFT JOIN groups g ON ug.groups_id = g.id
           WHERE
             u.email = 'user@laissepasser.fr';"

echo "Executing SQL query:" 1>&2
echo "$SQL_QUERY" 1>&2
echo "Result:" 1>&2
mariadb -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME -e "$SQL_QUERY" 1>&2

echo "---- test trigger rate limiting bloc----" 1>&2
# trigger rate limit for user@laissepasser.fr which is limited to 1 mail per second
# swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1
# swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1
# swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1
# swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1

swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1