#!/bin/bash

dx='docker compose exec -u www-data app'

for user in 'user.domain.quota' 'user.group1.perso.large.quota' 'user.group1.perso.small.quota' 'user.group1.quota' 'user'
do
  echo "$user"
	echo out
	for _i in $(seq 1 100)
	do
		$dx swaks --from "$user@blocnormal.fr" --to "root@smtp.test" --server smtptest:27 > /dev/null 2>&1
		echo -n .
	done
	echo ok
	echo in
	for _i in $(seq 1 100)
	do
		$dx swaks --to "$user@blocnormal.fr" --from "root@smtp.test" --server smtptest:26 > /dev/null 2>&1
		echo -n .
	done
	echo ok
done
