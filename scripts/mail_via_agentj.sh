#!/bin/bash
# send a mail from an user of a domain protected by agentj
# to an external user on an external smtp

ip_smtptest=$(scripts/_smtptest_ip.sh)

from='user@blocnormal.fr'
to='root@smtp.test'
subject="mail $RANDOM via agentj"
virus=false
spam=false

usage() {
    cat << 'EOF'
Usage: $0 [OPTIONS]

Options:
    -a          from addresses (can be used several times)
    -b          to addresses (can be used several times)
    -c          mail body
    -h          mail headers (can be used several times)
    -v          toggle to attach a virus to the mail
    -s          toggle to modify body to make it appear like spam
    -t          mail subject
    -x          usage

Examples:
    $0 -b test@domain.fr -b test2@domain.fr -t 'fancy subject' -s
    $0 -t 'Lottery' -b test@domain.fr -v
EOF
    exit 1
}



t=()
headers=()
while getopts "a: b: c: t: h: v s x" opt; do
    case $opt in
        a)
            from="$OPTARG"
            ;;
        b)
            t+=("$OPTARG")
            ;;
        c)
            content="$OPTARG"
            ;;
        h)
            headers+=("$OPTARG")
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
        x)
            usage
            exit 0
            ;;
        ?)
            usage
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

if [ ! -z $content ] ; then
    body=$content
fi

if [ "$spam" = true ] ; then
    body="@tests/gtube"
fi

command="swaks --from '${from}' --to '${joined:-$to}' --server '$ip_smtptest':27 \
	--h-Subject '$subject' --body $body"

for h in "${headers[@]}"; do
    command="$command --add-header $h"
done

if [ "$virus" = true ] ; then
    command="$command --attach @tests/eicar.com.txt"
fi

eval $command

echo "sent:"
echo "$subject"
