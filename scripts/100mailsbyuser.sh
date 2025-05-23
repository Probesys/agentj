#!/bin/bash

ip_smtptest=$(scripts/_smtptest_ip.sh)

for user in 'user.domain.quota' 'user.group1.perso.large.quota' 'user.group1.perso.small.quota' 'user.group1.quota' 'user'
do
  echo "$user"
	echo out
	for _i in $(seq 1 100)
	do
		swaks --from "$user@blocnormal.fr" --to "root@smtp.test" --server "$ip_smtptest":27 > /dev/null
		echo -n .
	done
	echo ok
	echo in
	for _i in $(seq 1 100)
	do
		swaks --to "$user@blocnormal.fr" --from "root@smtp.test" --server "$ip_smtptest":26 > /dev/null
		echo -n .
	done
	echo ok
done
