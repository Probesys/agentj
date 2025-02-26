#!/bin/bash
# send a mail from an user of a domain protected by agentj
# to an external user on an external smtp

dx='docker compose exec -u www-data app'

from='user@blocnormal.fr'
to='root@smtp.test'
subject="$RANDOM mail to agentj"

$dx swaks --from "$from" --to "$to" --server smtptest:27 \
	--h-Subject "$subject" --body "from $from to $to sent at $(date +%R)"

echo "sent:"
echo "$subject"
