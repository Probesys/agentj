#!/bin/bash
# send a mail from an external address and smtp
# to an user of a domain protected by agentj

ip_smtptest=$(scripts/_smtptest_ip.sh)

from='root@smtp.test'
to='user@blocnormal.fr'
subject="mail $RANDOM to agentj"

swaks --from "$from" --to "$to" --server "$ip_smtptest":26 \
	--h-Subject "$subject" --body "from $from to $to sent at $(date +%R)"

echo "sent:"
echo "$subject"
