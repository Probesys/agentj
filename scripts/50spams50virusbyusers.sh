#!/bin/bash

ip_smtptest=$(docker inspect "$(docker compose ps --format "{{.ID}}" smtptest)" |grep -Po '(?<=IPAddress": ")(.*)(?=",)')
count=${1:-10}

echo " -- legit"
for user in 'user.domain.quota' 'user.group1.perso.large.quota' 'user.group1.perso.small.quota' 'user.group1.quota' 'user'
do
	echo "$user"
	echo -n out
	for i in $(seq 1 "$count")
	do
		swaks --from "$user@blocnormal.fr" --to "root@smtp.test" --server "$ip_smtptest":27 --h-Subject "$i legit" --body "legit content" > /dev/null
		echo -n '.'
	done
	echo ok
	echo -n in
	for i in $(seq 1 "$count")
	do
		swaks --to "$user@blocnormal.fr" --from "root@smtp.test" --server "$ip_smtptest":26 --h-Subject "$i legit" --body "legit content" > /dev/null
		echo -n '.'
	done
	echo ok
done

echo " -- spam"
for user in 'user.domain.quota' 'user.group1.perso.large.quota' 'user.group1.perso.small.quota' 'user.group1.quota' 'user'
do
	echo "$user"
	echo -n out
	for i in $(seq 1 "$count")
	do
		swaks --from "$user@blocnormal.fr" --to "root@smtp.test" --server "$ip_smtptest":27 --h-Subject "$i spam" --body "XJS*C4JDBQADN1.NSBN3*2IDNEN*GTUBE-STANDARD-ANTI-UBE-TEST-EMAIL*C.34X" > /dev/null
		echo -n '.'
	done
	echo ok
	echo -n in
	for i in $(seq 1 "$count")
	do
		swaks --to "$user@blocnormal.fr" --from "root@smtp.test" --server "$ip_smtptest":26 --h-Subject "$i spam" --body "XJS*C4JDBQADN1.NSBN3*2IDNEN*GTUBE-STANDARD-ANTI-UBE-TEST-EMAIL*C.34X" > /dev/null
		echo -n '.'
	done
	echo ok
done

echo " -- virus"
for user in 'user.domain.quota' 'user.group1.perso.large.quota' 'user.group1.perso.small.quota' 'user.group1.quota' 'user'
do
	echo "$user"
	echo -n out
	for i in $(seq 1 "$count")
	do
		swaks --from "$user@blocnormal.fr" --to "root@smtp.test" --server "$ip_smtptest":27 --h-Subject "$i virus" --attach @docker/tests/eicar.com.txt > /dev/null
		echo -n '.'
	done
	echo ok
	echo -n in
	for i in $(seq 1 "$count")
	do
		swaks --to "$user@blocnormal.fr" --from "root@smtp.test" --server "$ip_smtptest":26 --h-Subject "$i virus" --attach @docker/tests/eicar.com.txt > /dev/null
		echo -n '.'
	done
	echo ok
done
