#!/bin/sh

cd /var/www/agentj/

test_results=/tmp/test_mails

# wait for app to be started (for db migrations)
echo "waiting app"
while [ $(curl -so /dev/null -w '%{http_code}' http://localhost/login) -ne "200" ];
do
	echo -n '.'
	sleep 1
done
echo ' ok'

# add tests data to db if not already here
php bin/console doctrine:fixtures:load --append

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
	# expected From
	mail_from=${7:-$local_addr}
	test_str=''
	# app cron which send validation mails run every min
	# if overriden, tests will fails
	wait_time=${TEST_TIMEOUT:-60}

	echo -n "[$testname] ... "

	case $in_out in
		"in")
			swaks --from $local_addr --to $aj_addr --body "sent to agentj" -s smtptest:26 $swaks_opts > $test_results/$testname.log 2>&1
			swaks_exit_code=$?
			test_str="From $mail_from"
			;;
		"out")
			swaks --to $local_addr --from $aj_addr --body "sent from agentj" -s outsmtp $swaks_opts > $test_results/$testname.log 2>&1
			swaks_exit_code=$?
			test_str="From $aj_addr"
			;;
		"outviarelay")
			swaks --to $local_addr --from $aj_addr --body "sent from agentj" -s smtptest:27 $swaks_opts > $test_results/$testname.log 2>&1
			swaks_exit_code=$?
			test_str="From $aj_addr"
			;;
		"outviabadrelay")
			swaks --to $local_addr --from $aj_addr --body "sent from agentj" -s badrelay:27 $swaks_opts > $test_results/$testname.log 2>&1
			swaks_exit_code=$?
			test_str="From $aj_addr"
			;;
		*)
			echo "unknown value '$in_out' for parameter in_out (should be 'in' or 'out')"
			return
			;;
	esac

	if [ "$swaks_expected" -ne "$swaks_exit_code" ]
	then
		echo -n "swaks error: $swaks_exit_code, expected $swaks_expected, mail_from '$mail_from', aj_addr '$aj_addr', options: '$swaks_opts' "
	fi

	touch $test_results/mailtester
	# wait for all mail to be received
	secs=0
	while [ "$(grep -c "$test_str" $test_results/mailtester)" -ne "$expected_received_count" ] && [ "$secs" -lt "$wait_time" ]
	do
		sleep 1; secs=$((secs + 1))
	done
	# if we didn't expect any mail, sleep to be sure nothing is received
	if [ "$expected_received_count" -eq 0 ]
	then
		secs=$wait_time
		sleep $wait_time
	fi

	received=$(grep -c "$test_str" $test_results/mailtester)
	test "$received" -gt "$(grep -Ec '^DKIM-Signature: ' $test_results/mailtester)" && echo -n "(missing DKIM signature) "
	if [ "$received" -eq "$expected_received_count" ]
	then
		echo "ok ${secs}s"
	else
		echo "failed ${secs}s, received $received mail with '$test_str', expected $expected_received_count. agentj address $aj_addr, mail from $mail_from, swaks options '$swaks_opts'"
	fi

	mv $test_results/mailtester $test_results/mailbox_$testname
}

echo "---- captcha/block/allow/virus/relay ----" 1>&2
send 'in_bloc_unknown' 'in' 'user@blocnormal.fr' 1 "" 0 'will@blocnormal.fr'
send 'in_pass_unknown' 'in' 'user@laissepasser.fr' 1

send 'out_bloc' 'outviarelay' 'user@blocnormal.fr' 1
send 'out_pass' 'outviarelay' 'user@laissepasser.fr' 1

send 'in_bloc_known' 'in' 'user@blocnormal.fr' 1
send 'in_pass_known' 'in' 'user@laissepasser.fr' 1

send 'in_bloc_known_virus' 'in' 'user@blocnormal.fr' 0 "--attach @docker/tests/eicar.com.txt"
send 'in_pass_known_virus' 'in' 'user@laissepasser.fr' 1 "--attach @docker/tests/eicar.com.txt"

send 'out_bloc_virus' 'outviarelay' 'user@blocnormal.fr' 0 "--attach @docker/tests/eicar.com.txt"
send 'out_pass_virus' 'outviarelay' 'user@laissepasser.fr' 0 "--attach @docker/tests/eicar.com.txt"

echo "---- don't relay from unregistered smtp ----" 1>&2
send 'out_bloc_bad_relay' 'outviabadrelay' 'user@blocnormal.fr' 0
send 'out_pass_bad_relay' 'outviabadrelay' 'user@laissepasser.fr' 0

echo "---- trigger domain rate limit: 2 mail/s ----" 1>&2
swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'user@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
# expect swak error 25 and two mail
send 'out_domain_rate_limit' 'out' 'user@blocnormal.fr' 2 "" 25

echo "---- trigger user rate limit: 1 mail/s ----" 1>&2
swaks -ha --from 'limited@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'limited@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'limited@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'limited@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
# expect swak error 25 and two mail
send 'out_rate_limit' 'out' 'limited@blocnormal.fr' 1 "" 25

echo "---- don't trigger domain rate limit: user allowed to 1000 mail/s ----" 1>&2
swaks -ha --from 'unlimited@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'unlimited@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'unlimited@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'unlimited@blocnormal.fr' --to 'root@smtp.test' --server outsmtp 2>&1
# expect no swak error and 5 mail
send 'out_rate_limit' 'out' 'unlimited@blocnormal.fr' 5

echo "---- no rate limit ----" 1>&2
swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1
swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1
sleep 10
# expect no swak error and 5 mails
send 'out_no_rate_limit' 'out' 'user@laissepasser.fr' 5

echo "OK" > $test_results/TESTS_DONE
