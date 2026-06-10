#!/bin/bash
# send a mail from an user of a domain protected by agentj
# to an external user on an external smtp

ip_smtptest=$(scripts/_smtptest_ip.sh)

from='user@blocnormal.fr'
to='root@smtp.test'
subject="mail $RANDOM via agentj"
virus=false
spam=false

r=()
while getopts "a: b: t: v s" opt; do
    case $opt in
        a)
            from="$OPTARG"
            ;;
        b)
            t+=("$OPTARG")
            ;;
        v)
            virus=true
            ;;
        s)
            spam=true
            ;;
        t)
            subject="$OPTARG"
            ;;
        ?)
            echo "Invalid option: -$OPTARG"
            exit 1
            ;;
    esac
done
shift $((OPTIND -1))

delim=""
joined=""
for item in "${t[@]}"; do
  joined="$joined$delim$item"
  delim=","
done

body="'from ${from} to ${t:-$to} sent at $(date +%R)'"
if [ "$spam" = true ] ; then
    body="@tests/gtube"
fi

command="swaks --from '${from}' --to '${joined:-$to}' --server '$ip_smtptest':26 \
	--h-Subject '$subject' --body $body"

if [ "$virus" = true ] ; then
    command="$command --attach @tests/eicar.com.txt"
fi

eval $command

echo "sent:"
echo "$subject"
