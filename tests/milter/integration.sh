#!/bin/bash
set -euo pipefail

compose=(docker compose -f docker-compose.yml -f compose.dev.yml -f compose.milter-poc.yml)
mailpit_api="http://mailpit.test:8025/api/v1"

wait_healthy() {
    local service="$1"
    local container status

    container=$("${compose[@]}" ps -q "$service")
    if [ -z "$container" ]; then
        echo "$service is not running" >&2
        return 1
    fi

    for _ in $(seq 1 60); do
        status=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container")
        if [ "$status" = "healthy" ] || [ "$status" = "running" ]; then
            return 0
        fi
        sleep 1
    done

    echo "$service did not become healthy" >&2
    return 1
}

send_message() {
    local subject="$1"
    local body="$2"
    local recipients="${3:-user@laissepasser.fr}"

    "${compose[@]}" exec -T smtptest swaks \
        --server 127.0.0.1:26 \
        --from root@smtp.test \
        --to "$recipients" \
        --header "Subject: $subject" \
        --header "Content-Type: text/plain; charset=UTF-8" \
        --header "Authentication-Results: forged.example; dkim=pass header.d=attacker.test" \
        --body "$body" >/dev/null
}

send_post_amavis_message() {
    local subject="$1"
    local recipients="$2"

    "${compose[@]}" exec -T smtptest swaks \
        --server smtp:10025 \
        --from root@smtp.test \
        --to "$recipients" \
        --header "Subject: $subject" \
        --header "Content-Type: text/plain; charset=UTF-8" \
        --header "X-AgentJ-Policy: spoofed" \
        --body "Router integration marker $subject" >/dev/null
}

wait_messages() {
    local subject="$1"
    local expected="$2"
    local messages count

    for _ in $(seq 1 30); do
        messages=$("${compose[@]}" exec -T smtptest curl -fsS "$mailpit_api/search?query=subject:$subject")
        count=$(jq -r '.messages_count' <<<"$messages")
        if [ "$count" -eq "$expected" ]; then
            sleep 2
            messages=$("${compose[@]}" exec -T smtptest curl -fsS "$mailpit_api/search?query=subject:$subject")
            count=$(jq -r '.messages_count' <<<"$messages")
            if [ "$count" -eq "$expected" ]; then
                printf '%s' "$messages"
                return 0
            fi
        fi
        sleep 1
    done

    echo "message $subject count is $count, expected $expected" >&2
    return 1
}

raw_message() {
    local id="$1"

    "${compose[@]}" exec -T smtptest curl -fsS "$mailpit_api/message/$id/raw"
}

message_text() {
    local subject="$1"
    local messages id

    for _ in $(seq 1 30); do
        messages=$("${compose[@]}" exec -T smtptest curl -fsS "$mailpit_api/search?query=subject:$subject")
        id=$(jq -r '.messages[0].ID // empty' <<<"$messages")
        if [ -n "$id" ]; then
            "${compose[@]}" exec -T smtptest curl -fsS "$mailpit_api/message/$id" | jq -r '.Text'
            return 0
        fi
        sleep 1
    done

    echo "message $subject was not delivered" >&2
    return 1
}

wait_healthy smtp
wait_healthy amavis
wait_healthy rspamdauth
wait_healthy policyrouter
wait_healthy urlrewritermilter
wait_healthy opendkimfinal
wait_healthy rspamdarc

suffix="$(date +%s)-$$"
noop_subject="milter-noop-$suffix"
rewrite_subject="milter-rewrite-$suffix"

send_message "$noop_subject" "AgentJ milter no-op marker $suffix"
noop_text=$(message_text "$noop_subject")
grep -Fq "AgentJ milter no-op marker $suffix" <<<"$noop_text"
if grep -Fq 'https://agentj.invalid/r?u=' <<<"$noop_text"; then
    echo "no-op message unexpectedly contains a rewritten URL" >&2
    exit 1
fi

send_message "$rewrite_subject" "Visit https://example.org/path for the integration test."
rewrite_text=$(message_text "$rewrite_subject")
grep -Fq 'https://agentj.invalid/r?u=aHR0cHM6Ly9leGFtcGxlLm9yZy9wYXRo' <<<"$rewrite_text"
rewrite_messages=$(wait_messages "$rewrite_subject" 1)
rewrite_id=$(jq -r '.messages[0].ID' <<<"$rewrite_messages")
raw_message "$rewrite_id" | "${compose[@]}" --profile test run --build --rm -T authenticationverifier

same_subject="router-same-policy-$suffix"
send_post_amavis_message "$same_subject" "alice@laissepasser.fr,carol@laissepasser.fr"
same_messages=$(wait_messages "$same_subject" 1)
same_id=$(jq -r '.messages[0].ID' <<<"$same_messages")
same_raw=$(raw_message "$same_id")
test "$(grep -ci '^X-AgentJ-Policy:' <<<"$same_raw")" -eq 1
grep -Fqi 'X-AgentJ-Policy: 1' <<<"$same_raw"
if grep -Fqi 'spoofed' <<<"$same_raw"; then
    echo "same-policy message retained the untrusted policy header" >&2
    exit 1
fi

mixed_subject="router-mixed-policy-$suffix"
send_post_amavis_message "$mixed_subject" "alice@laissepasser.fr,john@laissepasser.fr"
mixed_messages=$(wait_messages "$mixed_subject" 2)
mapfile -t mixed_ids < <(jq -r '.messages[].ID' <<<"$mixed_messages")
policy1=0
policy2=0
for id in "${mixed_ids[@]}"; do
    raw=$(raw_message "$id")
    test "$(grep -ci '^X-AgentJ-Policy:' <<<"$raw")" -eq 1
    if grep -Fqi 'X-AgentJ-Policy: 1' <<<"$raw"; then
        grep -Fqi 'for <alice@laissepasser.fr>' <<<"$raw"
        policy1=$((policy1 + 1))
    elif grep -Fqi 'X-AgentJ-Policy: 2' <<<"$raw"; then
        grep -Fqi 'for <john@laissepasser.fr>' <<<"$raw"
        policy2=$((policy2 + 1))
    else
        echo "split message has no trusted policy header" >&2
        exit 1
    fi
    if grep -Fqi 'spoofed' <<<"$raw"; then
        echo "split message retained the untrusted policy header" >&2
        exit 1
    fi
done
test "$policy1" -eq 1
test "$policy2" -eq 1

wait_healthy urlrewritermilter
echo "Postfix -> Rspamd auth -> Amavis -> router -> URL milter -> DKIM -> ARC: ok"
