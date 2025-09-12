#!/bin/bash

dx='docker compose exec -u www-data app'

source .env
echo "waiting app"
while [ "$(curl -so /dev/null -w '%{http_code}' "http://localhost:$PROXY_PORT/login")" -ne 200 ];
do
	echo -n '.'
	sleep 1
done
echo ' ok'

ip_smtptest=$(docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$(docker compose ps -q smtptest)")
ip_outsmtp=$(docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$(docker compose ps -q outsmtp)")

# add tests data to db if not already here
$dx php bin/console doctrine:fixtures:load --append

test_results=./tests/results
mailpit_api="http://localhost:$MAILPIT_WEB_PORT/api/v1"
curl='curl -s'

send() {
	# for log
	testname="$1"
	# in|out|out_proxy (send to agentj or via agentj)
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

	to_addr="${to_addr:-root@smtp.test}"
	wait_time=${TEST_TIMEOUT:-15}
	message_subject="test_${testname}_$RANDOM"
	_expected_subject="${expected_subject:-$message_subject}"
	expected_subject="${_expected_subject// /%20}"

	echo -n "[$testname] ... "

	case "$in_out" in
		# to agentj, from external smtp server
		"in")
			# mail is resent by AgentJ
			_from="$from_addr"
			from_addr="$to_addr"
			to_addr="$_from"
			smtp_server="$ip_smtptest:26"
			;;
		# from agentj, via authorized smtp (see fixtures)
		"out")
			smtp_server="$ip_smtptest:27"
			;;
		# from agentj, directly via outsmtp container
		"out_proxy")
			smtp_server="$ip_outsmtp"
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
		messages_json=$($curl "$mailpit_api/search?query=subject:\"$expected_subject\"%20!is:tagged%20is:unread")
		recv_count=$(echo "$messages_json" | jq ".messages_count")
		sender=$(echo "$messages_json" | jq -r ".messages[0].From.Address")
		# don't wait if
		# - we did expect to receive at least one mail
		# - we did not expect less mail than sent (for rate limit)
		# - we received what was expected
		if [ "$expected_received_count" -gt 0 ] \
			&& [ "$expected_received_count" -ge "$mail_to_send" ] \
			&& [ "$recv_count" -eq "$expected_received_count" ]; then
			break
		fi
	done

	# if not specified, sender is from_addr
	test -z "$expected_sender" && expected_sender="$from_addr"
	# if we don't expect mail, sender is null (from jq)
	test "$expected_received_count" -eq 0 && expected_sender="null"
	if [ "$recv_count" -eq "$expected_received_count" ] \
		&& [ "$expected_sender" = "$sender" ]; then
		echo "ok ${secs}s"
		echo 'ok' > "$test_results/$testname.result"
		tag='ok'
	else
		echo "failed ${secs}s, $recv_count/$expected_received_count mail, to '$to_addr', from '$sender'/'$expected_sender', swaks options '$swaks_opts'"
		echo 'failed' > "$test_results/$testname.result"
		tag='FAIL'
	fi

	# set read status and tag
	if [ "$recv_count" -gt 0 ]; then
	  message_ids=$($curl "$mailpit_api/search?query=$expected_subject" \
	    | jq ".messages[].ID" | tr '\n' ',' | sed 's/,$//')
	  $curl -o /dev/null -X PUT "$mailpit_api/messages" --json "{\"IDs\":[$message_ids], \"Read\":true}"
	  $curl -o /dev/null -X PUT "$mailpit_api/tags" --json "{\"IDs\":[$message_ids], \"Tags\":[\"test:$tag\"]}"
	fi

	expected_subject=
	expected_received_count=
	expected_sender=
	to_addr=
	mail_to_send=
	swaks_expected=
}

if [ -z "$1" ] || [ "$1" = "block" ]
then
	echo "---- block unknown sender, receive report, authorize by sending mail ----" 1>&2
	{ sleep 5 && $dx php bin/console agentj:send-auth-mail-token >/dev/null; } &
	expected_sender="will@blocnormal.fr" \
		send 'in_bloc_unknown' 'in' 'user@blocnormal.fr' 1
	send 'in_pass_unknown' 'in' 'user@laissepasser.fr' 1

	$dx php bin/console ag:report >/dev/null
	expected_subject="Messages en attente sur AgentJ pour user@blocnormal.fr" \
		expected_sender="will@blocnormal.fr" \
		to_addr="user@blocnormal.fr" \
		send 'report' 'out' 'user@blocnormal.fr' 1 0

	send 'out_bloc' 'out' 'user@blocnormal.fr' 1
	send 'out_pass' 'out' 'user@laissepasser.fr' 1

	send 'in_bloc_known' 'in' 'user@blocnormal.fr' 1
	send 'in_pass_known' 'in' 'user@laissepasser.fr' 1
fi

if [ -z "$1" ] || [ "$1" = "virusspam" ]
then
	echo "---- virus/spam ----" 1>&2
	send 'in_bloc_known_virus' 'in' 'user@blocnormal.fr' 0 1 "--attach @tests/eicar.com.txt"
	send 'in_pass_known_virus' 'in' 'user@laissepasser.fr' 1 1 "--attach @tests/eicar.com.txt"

	send 'in_bloc_known_spam' 'in' 'user@blocnormal.fr' 1 1 "--body @tests/gtube"
	send 'in_pass_known_spam' 'in' 'user@laissepasser.fr' 1 1 "--body @tests/gtube"

	send 'out_bloc_virus' 'out' 'user@blocnormal.fr' 0 1 "--attach @tests/eicar.com.txt"
	send 'out_pass_virus' 'out' 'user@laissepasser.fr' 0 1 "--attach @tests/eicar.com.txt"

	send 'out_bloc_spam' 'out' 'user@blocnormal.fr' 0 1 "--body @tests/gtube"
	send 'out_pass_spam' 'out' 'user@laissepasser.fr' 0 1 "--body @tests/gtube"
fi

if [ -z "$1" ] || [ "$1" = "relay" ]
then
	echo "---- don't relay from inexistants users ----" 1>&2
	send 'out_bloc_unknown_user' 'out' 'inexistant_user@blocnormal.fr' 0 1
	echo "---- don't relay from unknown servers ----" 1>&2
	send 'out_bloc_dont_relay_unknown' 'out_proxy' 'user@blocnormal.fr' 0 1 "" 24
fi

if [ -z "$1" ] || [ "$1" = "ratelimit" ]
then
	echo "---- rate limit ----" 1>&2
	# Domain quota 3 mail/5s
	# 3 out_msgs and 1 sql_limit_report
	send 'rate_limit_domain_3_mail_s' 'out' 'user.domain.quota@blocnormal.fr' 3 5

	# Group quota 2 mail/5s
	# 2 out_msgs and 2 sql_limit_report
	send 'rate_limit_group_2_mail_s' 'out' 'user.group1.quota@blocnormal.fr' 2 5

	# Personnal quota 1 mail/5s
	# 1 out_msgs and 3 sql_limit_report
	send 'rate_limit_user_1_mail_s' 'out' 'user.group1.perso.small.quota@blocnormal.fr' 1 5

	# Personnal quota 10 mail/5s
	send 'rate_limit_user_10_mail_s' 'out' 'user.group1.perso.large.quota@blocnormal.fr' 5 5

	echo "---- no rate limit ----" 1>&2
	send 'rate_limit_unlimited' 'out' 'user@laissepasser.fr' 10 10
fi

if [ -z "$1" ] || [ "$1" = "dsn" ]
then
	echo "---- non delivery message ----" 1>&2
	expected_subject="Undelivered Mail Returned to Sender" \
		expected_sender="MAILER-DAEMON@$DOMAIN" \
		to_addr="inexistant@mail.addr" \
		send 'dsn_non_delivery' 'out' 'user@blocnormal.fr' 1 1
fi

echo "OK" > $test_results/TESTS_DONE
