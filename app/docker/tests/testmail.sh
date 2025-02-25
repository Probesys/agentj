#!/bin/bash

cd /var/www/agentj || exit
bash /var/www/agentj/docker/tests/init.sh

test_results=/tmp/test_mails
mailpit_api="http://mailpit.test:$MAILPIT_WEB/api/v1"
curl='curl -s'

send() {
	# for log
	testname="$1"
	# in|out|outviarelay|outviabadrelay (send to agentj or via agentj)
	in_out="$2"
	from_addr="$3"
	# number of received mail expected
	expected_received_count="$4"
	# number of mail to send, default same as expected 
	mail_to_send="${5:-$expected_received_count}"
	# additionnal swaks options (eg attach a file)
	swaks_opts="$6"
	# expected swaks error code (if empty, means no error expected)
	swaks_expected="${7:-0}"

	to_addr='root@smtp.test'
	wait_time=${TEST_TIMEOUT:-30}
	message_subject="test_${testname}_$RANDOM"

	echo -n "[$testname] ... "

	case "$in_out" in
		# to agentj, from external smtp server
		"in")
			# mail is resent by AgentJ
			_from="$from_addr"
			from_addr="$to_addr"
			to_addr="$_from"
			smtp_server="smtptest:26"
			;;
		# from agentj, via agentj smtp server (but connecting host ip must be authorized)
		"out")
			smtp_server="outsmtp"
			;;
		# from agentj, via authorized external smtp server for domain blocnormal.fr, then agentj smtp server
		"outviarelay")
			smtp_server="smtptest:27"
			;;
		# from agentj, via unauthorized external smtp server (then blocked by agentj smtp server)
		"outviabadrelay")
			smtp_server="badrelay:27"
			;;
		*)
			echo "unknown value '$in_out' for parameter in_out"
			return
			;;
	esac

	for c in $(seq "$mail_to_send")
	do
		# $swaks_opts should expand
		# shellcheck disable=SC2086
		swaks --from "$from_addr" --to "$to_addr" --server "$smtp_server" \
			--h-Subject="$message_subject" --body "$testname from: $from_addr to: $to_addr via: $smtp_server" \
			$swaks_opts > "$test_results/${testname}_$c.log" 2>&1
		swaks_exit_code=$?
	done

	if [ "$swaks_expected" -ne "$swaks_exit_code" ]
	then
		echo -n "swaks error: $swaks_exit_code, expected $swaks_expected, from '$from_addr', to '$to_addr', options: '$swaks_opts' "
	fi

	secs=0
	recv_count=0
	while [ "$secs" -lt "$wait_time" ]
	do
		sleep 1; secs=$((secs + 1))
		recv_count=$($curl "$mailpit_api/search?query=$message_subject" | jq ".messages_count")
		# don't wait only if we expect more than 0 mail and less than sent
		if [ "$expected_received_count" -ne 0 ] \
			&& [ "$expected_received_count" -eq "$mail_to_send" ] \
			&& [ "$recv_count" -eq "$expected_received_count" ]; then
			break
		fi
	done

	if [ "$recv_count" -eq "$expected_received_count" ]; then
		echo "ok ${secs}s"
		echo 'ok' > "$test_results/$testname.result"
		tag='ok'
	else
		echo "failed ${secs}s, $recv_count/$expected_received_count mail, from '$from_addr', to '$to_addr', swaks options '$swaks_opts'"
		echo 'failed' > "$test_results/$testname.result"
		tag='TEST_FAILED'
	fi

	# set read status and tag
	if [ "$recv_count" -gt 0 ]; then
	  message_ids=$($curl "$mailpit_api/search?query=$message_subject" \
	    | jq ".messages[].ID" | tr '\n' ',' | sed 's/,$//')
	  $curl -o /dev/null -X PUT "$mailpit_api/messages" --json "{\"IDs\":[$message_ids], \"Read\":true}"
	  $curl -o /dev/null -X PUT "$mailpit_api/tags" --json "{\"IDs\":[$message_ids], \"Tags\":[\"$tag\"]}"
	fi
}

if [ -z "$1" ] || [ "$1" = "block" ]
then
	echo "---- block unknown sender/unlock by sending mail ----" 1>&2
	{ sleep 5 && php bin/console ag:msgs >/dev/null; } &
	# TODO check subject & validation mail sender is will@blocnormal.fr
	send 'in_bloc_unknown' 'in' 'user@blocnormal.fr' 1
	send 'in_pass_unknown' 'in' 'user@laissepasser.fr' 1

	send 'out_bloc' 'outviarelay' 'user@blocnormal.fr' 1
	send 'out_pass' 'out' 'user@laissepasser.fr' 1

	send 'in_bloc_known' 'in' 'user@blocnormal.fr' 1
	send 'in_pass_known' 'in' 'user@laissepasser.fr' 1
fi

if [ -z "$1" ] || [ "$1" = "virusspam" ]
then
	echo "---- virus/spam ----" 1>&2
	send 'in_bloc_known_virus' 'in' 'user@blocnormal.fr' 0 1 "--attach @docker/tests/eicar.com.txt"
	send 'in_pass_known_virus' 'in' 'user@laissepasser.fr' 1 1 "--attach @docker/tests/eicar.com.txt"

	send 'in_bloc_known_spam' 'in' 'user@blocnormal.fr' 1 1 "--body docker/tests/gtube"
	send 'in_pass_known_spam' 'in' 'user@laissepasser.fr' 1 1 "--body docker/tests/gtube"

	send 'out_bloc_virus' 'outviarelay' 'user@blocnormal.fr' 0 1 "--attach @docker/tests/eicar.com.txt"
	send 'out_pass_virus' 'out' 'user@laissepasser.fr' 0 1 "--attach @docker/tests/eicar.com.txt"

	send 'out_bloc_spam' 'outviarelay' 'user@blocnormal.fr' 0 1 "--body docker/tests/gtube"
	send 'out_pass_spam' 'out' 'user@laissepasser.fr' 0 1 "--body docker/tests/gtube"
fi

if [ -z "$1" ] || [ "$1" = "relay" ]
then
	echo "---- don't relay from unregistered smtp or users from another domain ----" 1>&2
	send 'out_bloc_bad_relay1' 'outviabadrelay' 'user@blocnormal.fr' 0
	send 'out_pass_bad_relay1' 'outviabadrelay' 'user@laissepasser.fr' 0
	send 'out_bloc_good_relay_bad_user1' 'out' 'user@blocnormal.fr' 0 1 "" 24
	send 'out_bloc_good_relay_bad_user2' 'outviarelay' 'user@laissepasser.fr' 0
fi

if [ -z "$1" ] || [ "$1" = "ratelimit" ]
then
	echo "---- rate limit ----" 1>&2
	# Domain quota 3 mail/s
	# 3 out_msgs and 1 sql_limit_report
	send 'rate_limit_domain_3_mail_s' 'outviarelay' 'user.domain.quota@blocnormal.fr' 3 5

	# Group quota 2 mail/s
	# 2 out_msgs and 2 sql_limit_report
	send 'rate_limit_group_2_mail_s' 'outviarelay' 'user.group1.quota@blocnormal.fr' 2 5

	# Personnal quota 1 mail/s
	# 1 out_msgs and 3 sql_limit_report
	send 'rate_limit_user_1_mail_s' 'outviarelay' 'user.group1.perso.small.quota@blocnormal.fr' 1 5

	# Personnal quota 10 mail/s
	send 'rate_limit_user_10_mail_s' 'outviarelay' 'user.group1.perso.large.quota@blocnormal.fr' 5 5

	echo "---- no rate limit ----" 1>&2
	send 'rate_limit_unlimited' 'out' 'user@laissepasser.fr' 10 10
fi

echo "OK" > $test_results/TESTS_DONE
