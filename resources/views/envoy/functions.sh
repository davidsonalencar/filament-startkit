
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

set_upstream() {
    local host="$1"
    local port="${2:-9000}"
    local file="${3:-./.docker/nginx/app_upstream.conf}"
    local tmp
    tmp=$(mktemp)

    sed "s/[a-zA-Z_\-]:[0-9]+/${host}:${port}/" "$file" > "$tmp" && cat "$tmp" > "$file"

    rm -f "$tmp"
}
