#!/usr/bin/env bash
set -euo pipefail

resolve_version_tag() {
  local requested_tag="${1:-latest}"

  if [ "$requested_tag" = "latest" ]; then
    git fetch origin --prune --tags --force >/dev/null 2>&1
    requested_tag="$(git tag | grep -E '^[0-9]+\.[0-9]+\.[0-9]+$' | sort -V | tail -n 1)"
  fi

  [ -n "${requested_tag:-}" ] || fail "Não foi possível resolver a tag"
  printf '%s\n' "$requested_tag"
}

get_last_release_tag() {
  git describe --tags --abbrev=0 \
    --exclude latest \
    --exclude "*alpha*" \
    --exclude "*beta*" \
    --exclude "*rc*" \
    --exclude "*dev*" \
    --exclude "*pre*" \
    --exclude "*alfa*" 2>/dev/null || true
}


get_release_commits() {
  local last_tag="${1:-}"

  if [ -z "$last_tag" ]; then
    log_warn "Nenhuma tag encontrada. Usando todos os commits."
    git log --pretty=format:'%s'
  else
    log_info "Última tag: $last_tag"
    git log "$last_tag"..HEAD --pretty=format:'%s'
  fi
}
