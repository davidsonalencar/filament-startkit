#!/usr/bin/env bash
set -euo pipefail


wait_for_container_health() {
    local container="$1"

    for i in $(seq 1 40); do
        STATUS=$(docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}starting{{end}}' $container 2>/dev/null || true)

        if [ "$STATUS" = "healthy" ]; then
            echo "✅ $container healthy"
            exit 0
        fi

        echo "⏳ waiting $container... attempt $i/40 status=$STATUS"
        sleep 3
    done

    docker logs $container || true
    exit 1
}
