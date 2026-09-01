#!/bin/bash
set -euo pipefail

compose=(docker compose -f docker-compose.yml -f compose.dev.yml -f compose.milter-poc.yml)
mailpit_api="http://mailpit.test:8025/api/v1"
count="${1:-20}"
full_milters="inet:127.0.0.1:9999,inet:opendkimfinal:8892,inet:rspamdarc:11332"
original_milters=$("${compose[@]}" exec -T smtp postconf -P -h "127.0.0.1:10026/inet/smtpd_milters")

if ! [[ "$count" =~ ^[1-9][0-9]*$ ]]; then
    echo "message count must be a positive integer" >&2
    exit 2
fi

set_milters() {
    "${compose[@]}" exec -T smtp postconf -P \
        "127.0.0.1:10026/inet/smtpd_milters=$1" >/dev/null
    "${compose[@]}" exec -T smtp postfix reload
    sleep 1
}

restore() {
    set_milters "$original_milters"
}
trap restore EXIT

wait_messages() {
    local subject="$1"
    local messages delivered=0

    for _ in $(seq 1 120); do
        messages=$("${compose[@]}" exec -T smtptest curl -fsS \
            "$mailpit_api/search?query=subject:$subject")
        delivered=$(jq -r '.messages_count' <<<"$messages")
        if [ "$delivered" -eq "$count" ]; then
            return 0
        fi
        sleep 1
    done

    echo "$subject delivered $delivered messages, expected $count" >&2
    return 1
}

measure() {
    local name="$1"
    local milters="$2"
    local subject="auth-benchmark-$name-$(date +%s)-$$"
    local start elapsed

    set_milters "$milters"
    start=$(date +%s%N)
    for i in $(seq 1 "$count"); do
        "${compose[@]}" exec -T smtptest swaks \
            --server smtp:10025 \
            --from root@smtp.test \
            --to user@laissepasser.fr \
            --header "Subject: $subject" \
            --header "Content-Type: text/plain; charset=UTF-8" \
            --body "Message $i visits https://example.org/benchmark" >/dev/null
    done
    wait_messages "$subject"
    elapsed=$((($(date +%s%N) - start) / 1000000))
    printf '%-14s %6d ms total, %4d ms/message (%d messages)\n' \
        "$name" "$elapsed" "$((elapsed / count))" "$count"
}

measure no-milter ""
measure url-only "inet:127.0.0.1:9999"
measure dkim-arc "$full_milters"
