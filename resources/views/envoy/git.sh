#!/usr/bin/env bash
set -euo pipefail

resolve_version_tag() {
  local requested_tag="${1:-latest}"

  if [ "$requested_tag" = "latest" ]; then
    git fetch origin --tags >/dev/null 2>&1
    requested_tag="$(git tag | grep -E '^[0-9]+\.[0-9]+\.[0-9]+$' | sort -V | tail -n 1)"
  fi

  [ -n "${requested_tag:-}" ] || fail "Não foi possível resolver a tag"
  printf '%s\n' "$requested_tag"
}
