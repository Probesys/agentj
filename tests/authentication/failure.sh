#!/bin/bash
set -euo pipefail

compose=(docker compose -f docker-compose.yml -f compose.dev.yml -f compose.milter-poc.yml)
mailpit_api="http://mailpit.test:8025/api/v1"

wait_healthy() {
    local service="$1"
    local container status

    container=$("${compose[@]}" ps -q "$service")
    for _ in $(seq 1 60); do
        status=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container")
        if [ "$status" = "healthy" ]; then
            return 0
        fi
        sleep 1
    done

    echo "$service did not become healthy" >&2
    return 1
}

restore() {
    "${compose[@]}" start opendkimfinal rspamdarc >/dev/null
}
trap restore EXIT

wait_delivery() {
    local subject="$1"
    local messages id

    for _ in $(seq 1 60); do
        messages=$("${compose[@]}" exec -T smtptest curl -fsS \
            "$mailpit_api/search?query=subject:$subject")
        id=$(jq -r '.messages[0].ID // empty' <<<"$messages")
        if [ -n "$id" ]; then
            printf '%s' "$id"
            return 0
        fi
        sleep 1
    done

    echo "$subject was not delivered after service recovery" >&2
    return 1
}

failed_delivery_count() {
    "${compose[@]}" exec -T smtp postqueue -p \
        | grep -Fc '451 4.3.0 routing failed; retry later' || true
}

test_failure() {
    local service="$1"
    local subject="auth-$service-failure-$(date +%s)-$$"
    local failures_before failures_after id

    failures_before=$(failed_delivery_count)

    "${compose[@]}" stop "$service" >/dev/null
    "${compose[@]}" exec -T smtptest swaks \
        --server 127.0.0.1:26 \
        --from root@smtp.test \
        --to user@laissepasser.fr \
        --header "Subject: $subject" \
        --header "Content-Type: text/plain; charset=UTF-8" \
        --body "Visit https://example.org/$service-failure" >/dev/null

    for _ in $(seq 1 90); do
        failures_after=$(failed_delivery_count)
        if [ "$failures_after" -gt "$failures_before" ]; then
            break
        fi
        sleep 1
    done
    test "${failures_after:-0}" -gt "$failures_before"

    "${compose[@]}" start "$service" >/dev/null
    wait_healthy "$service"
    # Docker DNS can briefly retain the missing milter name after a restart.
    sleep 2
    "${compose[@]}" exec -T smtp postqueue -f
    id=$(wait_delivery "$subject")
    "${compose[@]}" exec -T smtptest curl -fsS \
        "$mailpit_api/message/$id/raw" \
        | "${compose[@]}" --profile test run --build --rm -T authenticationverifier
    echo "$service failure retained and retried the message: ok"
}

test_failure opendkimfinal
test_failure rspamdarc
