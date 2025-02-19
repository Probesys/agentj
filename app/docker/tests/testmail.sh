#!/bin/bash

cd /var/www/agentj || exit
bash /var/www/agentj/docker/tests/init.sh

test_results=/tmp/test_mails
mail_db=/tmp/test_db/mailpit.db

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

	case "$in_out" in
		# to agentj, from external smtp server
		"in")
			swaks --from $local_addr --to "$aj_addr" --body "sent to agentj" -s smtptest:26 $swaks_opts > "$test_results/$testname.log" 2>&1
			swaks_exit_code=$?
			#test_str="{\"From\":%\"Address\":\"$local_addr\"}"
			test_str="%From%Address\":\"$local_addr\"%To%Address\":\"$aj_addr\"%"
			;;
		# from agentj, via agentj smtp server (but connecting host ip must be authorized)
		"out")
			swaks --to $local_addr --from "$aj_addr" --body "sent from agentj" -s outsmtp $swaks_opts > "$test_results/$testname.log" 2>&1
			swaks_exit_code=$?
			#test_str="{\"From\":{\"Name\":\"\",\"Address\":\"$aj_addr\"}"
			test_str="%From%Address\":\"$aj_addr\"%To%Address\":\"$local_addr\"%"
			;;
		# from agentj, via authorized external smtp server for domain blocnormal.fr, then agentj smtp server
		"outviarelay")
			swaks --to $local_addr --from "$aj_addr" --body "sent from agentj" -s smtptest:27 $swaks_opts > "$test_results/$testname.log" 2>&1
			swaks_exit_code=$?
			#test_str="{\"From\":{\"Name\":\"\",\"Address\":\"$aj_addr\"}"
			test_str="%From%Address\":\"$aj_addr\"%To%Address\":\"$local_addr\"%"
			;;
		# from agentj, via unauthorized external smtp server (then blocked by agentj smtp server)
		"outviabadrelay")
			swaks --to $local_addr --from "$aj_addr" --body "sent from agentj" -s badrelay:27 $swaks_opts > "$test_results/$testname.log" 2>&1
			swaks_exit_code=$?
			#test_str="{\"From\":{\"Name\":\"\",\"Address\":\"$aj_addr\"}"
			test_str="%From%Address\":\"$aj_addr\"%To%Address\":\"$local_addr\"%"
			wait_time=5
			;;
		*)
			echo "unknown value '$in_out' for parameter in_out"
			return
			;;
	esac

	if [ "$swaks_expected" -ne "$swaks_exit_code" ]
	then
		echo -n "swaks error: $swaks_exit_code, expected $swaks_expected, mail_from '$mail_from', aj_addr '$aj_addr', options: '$swaks_opts' "
	fi

	touch "$test_results/mailtester"
	# wait for all mail to be received
	secs=0
	while [ "$(sqlite3 "$mail_db" "select count(*) from mailbox where Metadata like '$test_str'")" -ne "$expected_received_count" ] && [ "$secs" -lt "$wait_time" ]
	do
		sleep 1; secs=$((secs + 1))
	done
	# if we didn't expect any mail, sleep to be sure nothing is received
	if [ "$expected_received_count" -eq 0 ]
	then
		secs="$wait_time"
		sleep "$wait_time"
	fi

	received=$(sqlite3 "$mail_db" "select count(*) from mailbox where Metadata like '$test_str' and read = 0")
	#test "$received" -gt "$(grep -Ec '^DKIM-Signature: ' $test_results/mailtester)" && echo -n "(missing DKIM signature) "
	if [ "$received" -eq "$expected_received_count" ]
	then
		echo "ok ${secs}s"
		echo 'ok' > "$test_results/$testname.result"
	else
		echo "failed ${secs}s, received $received mail with '$test_str', expected $expected_received_count, agentj addr '$aj_addr', remote addr '$mail_from', swaks options '$swaks_opts'"
		echo 'failed' > "$test_results/$testname.result"
	fi

	#sqlite3 "$mail_db" "update mailbox set read = 1 where Metadata like '$test_str%'"
	mail_id=$(sqlite3 "$mail_db" "select ID from mailbox where Metadata like '$test_str'")
	curl -X PUT http://mailpit.test:8025/api/v1/messages -d "{\"IDs\":[\"$mail_id\"], \"Read\": true}"
}

if [ -z "$1" ] || [ "$1" = "block" ]
then
	echo "---- block unknown sender/unlock by sending mail ----" 1>&2
	send 'in_bloc_unknown' 'in' 'user@blocnormal.fr' 1 "" 0 'will@blocnormal.fr'
	send 'in_pass_unknown' 'in' 'user@laissepasser.fr' 1

	send 'out_bloc' 'outviarelay' 'user@blocnormal.fr' 1
	send 'out_pass' 'out' 'user@laissepasser.fr' 1

	send 'in_bloc_known' 'in' 'user@blocnormal.fr' 1
	send 'in_pass_known' 'in' 'user@laissepasser.fr' 1
fi

if [ -z "$1" ] || [ "$1" = "virusspam" ]
then
	echo "---- virus/spam ----" 1>&2
	send 'in_bloc_known_virus' 'in' 'user@blocnormal.fr' 0 "--attach @docker/tests/eicar.com.txt"
	send 'in_pass_known_virus' 'in' 'user@laissepasser.fr' 1 "--attach @docker/tests/eicar.com.txt"

	send 'in_bloc_known_spam' 'in' 'user@blocnormal.fr' 1 "--body docker/tests/gtube"
	send 'in_pass_known_spam' 'in' 'user@laissepasser.fr' 1 "--body docker/tests/gtube"

	send 'out_bloc_virus' 'outviarelay' 'user@blocnormal.fr' 0 "--attach @docker/tests/eicar.com.txt"
	send 'out_pass_virus' 'out' 'user@laissepasser.fr' 0 "--attach @docker/tests/eicar.com.txt"

	send 'out_bloc_spam' 'outviarelay' 'user@blocnormal.fr' 0 "--body docker/tests/gtube"
	send 'out_pass_spam' 'out' 'user@laissepasser.fr' 0 "--body docker/tests/gtube"
fi

if [ -z "$1" ] || [ "$1" = "relay" ]
then
	echo "---- don't relay from unregistered smtp or users from another domain ----" 1>&2
	send 'out_bloc_bad_relay1' 'outviabadrelay' 'user@blocnormal.fr' 0
	send 'out_pass_bad_relay1' 'outviabadrelay' 'user@laissepasser.fr' 0
	send 'out_bloc_good_relay_bad_user1' 'out' 'user@blocnormal.fr' 0 "" 24
	send 'out_bloc_good_relay_bad_user2' 'outviarelay' 'user@laissepasser.fr' 0
fi

if [ -z "$1" ] || [ "$1" = "ratelimit" ]
then
	echo "---- rate limit ----" 1>&2
	# Domain 3 mail/s
	swaks -ha --from 'user.domain.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.domain.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.domain.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.domain.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	# 3 out_msgs and 1 sql_limit_report
	send 'rate_limit_domain_3_mail_s' 'outviarelay' 'user.domain.quota@blocnormal.fr' 3 ''

	# Group 2 mail/s
	swaks -ha --from 'user.group1.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.group1.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.group1.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.group1.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	# 2 out_msgs and 2 sql_limit_report
	send 'rate_limit_group_2_mail_s' 'outviarelay' 'user.group1.quota@blocnormal.fr' 2 ''

	# Personnal 1 mail/s
	swaks -ha --from 'user.group1.perso.small.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.group1.perso.small.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.group1.perso.small.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.group1.perso.small.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	# 1 out_msgs and 3 sql_limit_report
	send 'rate_limit_user_1_mail_s' 'outviarelay' 'user.group1.perso.small.quota@blocnormal.fr' 1 ''

	# Personnal 10 mail/s
	swaks -ha --from 'user.group1.perso.large.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.group1.perso.large.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.group1.perso.large.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	swaks -ha --from 'user.group1.perso.large.quota@blocnormal.fr' --to 'root@smtp.test' --server smtptest:27 2>&1
	# expect no swak error and 5 mails
	send 'rate_limit_user_10_mail_s' 'outviarelay' 'user.group1.perso.large.quota@blocnormal.fr' 5

	echo "---- no rate limit ----" 1>&2
	swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1
	swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1
	swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1
	swaks -ha --from 'user@laissepasser.fr' --to 'root@smtp.test' --server outsmtp 2>&1
	# expect no swak error and 5 mails
	send 'rate_limit_unlimited' 'out' 'user@laissepasser.fr' 5
fi

echo "OK" > $test_results/TESTS_DONE
