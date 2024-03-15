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
mariadb -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASSWORD $DB_NAME < /srv/sql/blocnormal_laissepasser.sql
[ "$?" -eq "0" ] || { echo 'failed to insert test data, exiting'; exit $?; }

# args:
# testname: for log
# in|out: send a mail to agentj or via agentj
# addr: agentj test address
# expected_result: 0 the mail should have been received, 1 should not
# swaks_opts: additionnal swaks options (eg attach a file)
send() {
	testname="$1"
	in_out="$2"
	aj_addr="$3"
	expected_result="$4"
	swaks_opts="$5"
	local_addr='root@smtp.test'
	test_str=''

	echo -n "[$testname] ... "

	case $in_out in
		"in")
			swaks --from $local_addr --to $aj_addr --body "sent to agentj" -s $IN_SMTP $swaks_opts > /srv/$logname.log 2>&1
			swaks_exit_code=$?
			test_str='From: root@smtp.test'
			;;
		"out")
			swaks --to $local_addr --from $aj_addr --body "sent from agentj" -s $OUT_SMTP $swaks_opts > /srv/$logname.log 2>&1
			swaks_exit_code=$?
			test_str="From: $aj_addr"
			;;
		*)
			echo "unknown value '$in_out' for parameter in_out (should be 'in' or 'out')"
			return
			;;
	esac

	[ "$swaks_exit_code" -ne 0 ] && echo "swaks error: $swaks_exit_code swaks_opts: '$swaks_opts' ... "

	# wait 10 seconds, except if a mail is received
	secs=0
	while [ ! -f /var/mail/root ] && [ "$secs" -lt "10" ]
	do
		sleep 1; secs=$((secs + 1))
	done

	touch /var/mail/root
	grep -q "$test_str" /var/mail/root
	received="$?"
	if [ "$received" -eq "$expected_result" ]
	then
		echo "ok (${secs}s)"
	else
		echo "failed. aj_addr: '$aj_addr' ; test_str: '$test_str' ; swaks_opts: '$swaks_opts'"
	fi

	mv /var/mail/root /var/mail/$testname
}

send 'in_bloc_unknown' 'in' 'user@blocnormal.fr' 1
send 'in_pass_unknown' 'in' 'user@laissepasser.fr' 0

send 'out_bloc' 'out' 'user@blocnormal.fr' 0
send 'out_pass' 'out' 'user@laissepasser.fr' 0

send 'in_bloc_known' 'in' 'user@blocnormal.fr' 0
send 'in_pass_known' 'in' 'user@laissepasser.fr' 0

send 'in_bloc_known_virus' 'in' 'user@blocnormal.fr' 1 '--attach /srv/eicar.com.txt'
send 'in_pass_known_virus' 'in' 'user@laissepasser.fr' 1 '--attach /srv/eicar.com.txt'

send 'out_bloc_virus' 'out' 'user@blocnormal.fr' 1 '--attach /srv/eicar.com.txt'
send 'out_pass_virus' 'out' 'user@laissepasser.fr' 1 '--attach /srv/eicar.com.txt'

send 'out_bloc2' 'out' 'user@blocnormal.fr' 0
send 'out_pass2' 'out' 'user@laissepasser.fr' 0
send 'out_bloc3' 'out' 'user@blocnormal.fr' 0
send 'out_pass3' 'out' 'user@laissepasser.fr' 0
