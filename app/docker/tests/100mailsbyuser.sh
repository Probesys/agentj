#!/bin/bash

cd /var/www/agentj || exit

# wait for app to be started (for db migrations)
echo "waiting app"
while [ "$(curl -so /dev/null -w '%{http_code}' http://localhost/login)" -ne 200 ];
do
	echo -n '.'
	sleep 1
done
echo ' ok'

php bin/console d:f:l --append

for user in 'user.domain.quota' 'user.group1.perso.large.quota' 'user.group1.perso.small.quota' 'user.group1.quota' 'user'
do
  echo "$user"
	echo out
	for i in $(seq 1 100)
	do
		swaks --from "$user@blocnormal.fr" --to "root@smtp.test" --server outsmtp > /tmp/out_"$user"_"$i"
		echo -n '.'
  done
	echo ok
	echo in
	for i in $(seq 1 100)
	do
		swaks --to "$user@blocnormal.fr" --from "root@smtp.test" --server smtptest:26 > /tmp/in_"$user"_"$i"
		echo -n '.'
  done
	echo ok
done
