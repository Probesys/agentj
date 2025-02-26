#!/bin/bash
# send a mail from an external adress and smtp
# to a user of a domain protected by agentj

dx='docker compose exec -u www-data app'

from='root@smtp.test'
to='user@blocnormal.fr'
subject="$RANDOM mail from agentj"

$dx swaks --from "$from" --to "$to" --server smtptest:26 \
	--h-Subject "$subject" --body "from $from to $to sent at $(date +%R)"

echo "sent:"
echo "$subject"
