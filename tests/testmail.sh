#!/bin/sh

# wait for app to be started (for db migrations)
echo "waiting app"
while [ $(curl -so /dev/null -w '%{http_code}' http://$APP_HOST/login) -ne "200" ];
do
	echo -n '.'
	sleep 1
done
echo ' ok'


# insert test base
if [ "$1" = "reinit_db" ];
then
	echo 'reinit db'
	mariadb -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASSWORD $DB_NAME < /srv/sql/blocnormal_laissepasser.sql
	[ "$?" -eq "0" ] || { echo 'failed to insert test data, exiting'; exit $?; }
fi 

send() {
	# for log
	testname="$1"
	# in|out (send to agentj or via agentj)
	in_out="$2"
	# agentj test address (from or to)
	aj_addr="$3"
	# number of received mail expected
	expected_received_count="$4"
	# additionnal swaks options (eg attach a file)
	swaks_opts="$5"
	# expected swaks error code (if empty, means no error expected)
	swaks_expected="${6:-0}"
	local_addr='root@smtp.test'
	test_str=''

	echo "---- $testname ----" 1>&2
	echo -n "[$testname] ... "

	case $in_out in
		"in")
			swaks --from $local_addr --to $aj_addr --body "sent to agentj" -s 127.0.0.1:26 $swaks_opts > /srv/$testname.log 2>&1
			swaks_exit_code=$?
			test_str="From: $local_addr"
			;;
		"out")
			swaks --to $local_addr --from $aj_addr --body "sent from agentj" -s $OUT_SMTP $swaks_opts > /srv/$testname.log 2>&1
			swaks_exit_code=$?
			test_str="From: $aj_addr"
			;;
		*)
			echo "unknown value '$in_out' for parameter in_out (should be 'in' or 'out')"
			return
			;;
	esac

	if [ "$swaks_expected" -ne "$swaks_exit_code" ]
	then
		echo -n "swaks error: $swaks_exit_code, expected $swaks_expected, options: '$swaks_opts' "
	fi

	touch /var/mail/root
	# wait for all mail to be received, or 10 seconds
	secs=0
	while [ "$(grep -c "$test_str" /var/mail/root)" -ne "$expected_received_count" ] && [ "$secs" -lt "10" ]
	do
		sleep 1; secs=$((secs + 1))
	done
	# if we didn't expect any mail, sleep 10 to be sure nothing is received
	if [ "$expected_received_count" -eq 0 ]
	then
		secs=10
		sleep 10
	fi

	received=$(grep -c "$test_str" /var/mail/root)
	test "$received" -gt "$(grep -Ec '^DKIM-Signature: ' /var/mail/root)" && echo -n "(missing DKIM signature) "
	if [ "$received" -eq "$expected_received_count" ]
	then
		echo "ok (${secs}s)"
	else
		echo "failed, received $received mail (expected $expected_received_count) with '$test_str'. agentj address: '$aj_addr', swaks options: '$swaks_opts'"
	fi

	mv /var/mail/root /var/mail/$testname
}

send 'in_bloc_unknown' 'in' 'user@blocnormal.fr' 0
send 'in_pass_unknown' 'in' 'user@laissepasser.fr' 1

send 'out_bloc' 'out' 'user@blocnormal.fr' 1
send 'out_pass' 'out' 'user@laissepasser.fr' 1

send 'in_bloc_known' 'in' 'user@blocnormal.fr' 1
send 'in_pass_known' 'in' 'user@laissepasser.fr' 1

send 'in_bloc_known_virus' 'in' 'user@blocnormal.fr' 0 '--attach /srv/eicar.com.txt'
send 'in_pass_known_virus' 'in' 'user@laissepasser.fr' 0 '--attach /srv/eicar.com.txt'

send 'out_bloc_virus' 'out' 'user@blocnormal.fr' 0 '--attach /srv/eicar.com.txt'
send 'out_pass_virus' 'out' 'user@laissepasser.fr' 0 '--attach /srv/eicar.com.txt'

echo "---- test trigger rate limiting ----" 1>&2
# trigger rate limit for user@blocnormal.fr which is limited to 1 mail per second
swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server $OUT_SMTP 
swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server $OUT_SMTP 
swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server $OUT_SMTP 
swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server $OUT_SMTP 
# expect swak error 25 and one mail
send 'out_rate_limit' 'out' 'user@blocnormal.fr' 1 "" 25

echo "---- test trigger rate limiting ----" 1>&2
# same without rate limit
swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server $OUT_SMTP 
swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server $OUT_SMTP 
swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server $OUT_SMTP 
swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server $OUT_SMTP 
sleep 10
# expect no swak error and 5 mails
send 'out_no_rate_limit' 'out' 'user@laissepasser.fr' 5

