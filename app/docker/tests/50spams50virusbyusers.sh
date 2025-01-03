#!/bin/bash

cd /var/www/agentj || exit

bash /var/www/agentj/docker/tests/init.sh

count=${1:-10}

echo "-- legit"
for user in 'user.domain.quota' 'user.group1.perso.large.quota' 'user.group1.perso.small.quota' 'user.group1.quota' 'user'
do
	echo "$user"
	echo -n out
	for i in $(seq 1 "$count")
	do
		swaks --from "$user@blocnormal.fr" --to "root@smtp.test" --server outsmtp --h-Subject legit --body "legit content" > /tmp/out_"$user"_"$i"
		echo -n '.'
	done
	echo ok
	echo -n in
	for i in $(seq 1 "$count")
	do
		swaks --to "$user@blocnormal.fr" --from "root@smtp.test" --server smtptest:26 --h-Subject legit --body "legit content" > /tmp/in_"$user"_"$i"
		echo -n '.'
	done
	echo ok
done

echo "-- spam"
for user in 'user.domain.quota' 'user.group1.perso.large.quota' 'user.group1.perso.small.quota' 'user.group1.quota' 'user'
do
	echo "$user"
	echo -n out
	for i in $(seq 1 "$count")
	do
		swaks --from "$user@blocnormal.fr" --to "root@smtp.test" --server outsmtp --h-Subject spam --body "XJS*C4JDBQADN1.NSBN3*2IDNEN*GTUBE-STANDARD-ANTI-UBE-TEST-EMAIL*C.34X" > /tmp/out_"$user"_"$i"
		echo -n '.'
	done
	echo ok
	echo -n in
	for i in $(seq 1 "$count")
	do
		swaks --to "$user@blocnormal.fr" --from "root@smtp.test" --server smtptest:26 --h-Subject spam --body "XJS*C4JDBQADN1.NSBN3*2IDNEN*GTUBE-STANDARD-ANTI-UBE-TEST-EMAIL*C.34X" > /tmp/in_"$user"_"$i"
		echo -n '.'
	done
	echo ok
done

echo "-- virus"
for user in 'user.domain.quota' 'user.group1.perso.large.quota' 'user.group1.perso.small.quota' 'user.group1.quota' 'user'
do
	echo "$user"
	echo -n out
	for i in $(seq 1 "$count")
	do
		swaks --from "$user@blocnormal.fr" --to "root@smtp.test" --server outsmtp --h-Subject virus --attach @docker/tests/eicar.com.txt > /tmp/out_"$user"_"$i"
		echo -n '.'
	done
	echo ok
	echo -n in
	for i in $(seq 1 "$count")
	do
		swaks --to "$user@blocnormal.fr" --from "root@smtp.test" --server smtptest:26 --h-Subject virus --attach @docker/tests/eicar.com.txt > /tmp/in_"$user"_"$i"
		echo -n '.'
	done
	echo ok
done
