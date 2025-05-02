#!/bin/bash
# send a mail from an user of a domain protected by agentj
# to an external user on an external smtp

ip_smtptest=$(docker inspect "$(docker compose ps --format "{{.ID}}" smtptest)" |grep -Po '(?<=IPAddress": ")(.*)(?=",)')

from='user@blocnormal.fr'
to='root@smtp.test'
subject="mail $RANDOM via agentj"

swaks --from "$from" --to "$to" --server "$ip_smtptest":27 \
	--h-Subject "$subject" --body "from $from to $to sent at $(date +%R)"

echo "sent:"
echo "$subject"
