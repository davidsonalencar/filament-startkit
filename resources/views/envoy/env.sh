#!/usr/bin/env bash
set -euo pipefail

set_env() {
    local key="$1"
    local value="$2"
    local file="${3:-.env}"

    local escaped_value
    escaped_value=$(printf '%s\n' "$value" | sed 's/[&/\]/\\&/g')

    if grep -q "^${key}=" "$file" 2>/dev/null; then
        local tmp
        tmp=$(mktemp)

        sed "s/^${key}=.*/${key}=${escaped_value}/" "$file" > "$tmp" && cat "$tmp" > "$file"

        rm -f "$tmp"
    else
        echo "${key}=${value}" >> "$file"
    fi
}

get_env() {
    local key="$1"
    local file="${2:-.env}"

    [ -f "$file" ] || return 0
    grep "^${key}=" "$file" | tail -n 1 | cut -d'=' -f2-
}
