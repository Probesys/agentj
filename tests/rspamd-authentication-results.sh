#!/bin/bash
set -euo pipefail

compose=(docker compose -f docker-compose.yml -f compose.dev.yml -f compose.milter-poc.yml)
mailpit_api="http://mailpit.test:8025/api/v1"
suffix="$(date +%s)-$$"
subject="rspamd-auth-results-$suffix"

for _ in $(seq 1 60); do
    status=$(docker inspect --format '{{.State.Health.Status}}' "$("${compose[@]}" ps -q rspamdauth)")
    if [ "$status" = "healthy" ]; then
        break
    fi
    sleep 1
done
test "$status" = "healthy"

"${compose[@]}" exec -T smtptest swaks \
    --server 127.0.0.1:26 \
    --from verifier@smtp.test \
    --to user@laissepasser.fr \
    --header "Subject: $subject" \
    --header "Authentication-Results: forged.example; dkim=pass header.d=attacker.test" \
    --body "Rspamd authentication result sentinel" >/dev/null

for _ in $(seq 1 30); do
    messages=$("${compose[@]}" exec -T smtptest curl -fsS "$mailpit_api/search?query=subject:$subject")
    id=$(jq -r '.messages[0].ID // empty' <<<"$messages")
    if [ -n "$id" ]; then
        raw=$("${compose[@]}" exec -T smtptest curl -fsS "$mailpit_api/message/$id/raw")
        break
    fi
    sleep 1
done

test -n "${raw:-}"
test "$(grep -ci '^Authentication-Results:' <<<"$raw")" -eq 1
grep -Fqi 'Authentication-Results: auth.agentj.test;' <<<"$raw"
for result in 'dkim=pass' 'spf=pass' 'dmarc=pass'; do
    grep -Fqi "$result" <<<"$raw"
done
if grep -Fqi 'forged.example' <<<"$raw"; then
    echo "forged Authentication-Results header was retained" >&2
    exit 1
fi

echo "Rspamd publishes trusted SPF, DKIM, and DMARC results: ok"
