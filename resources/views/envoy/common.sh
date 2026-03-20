#!/usr/bin/env bash
set -euo pipefail

log() {
  printf '%s\n' "$*"
}

log_info() {
  printf '>> %s\n' "$*"
}

log_warn() {
  printf '⚠️ %s\n' "$*"
}

log_error() {
  printf '❌ %s\n' "$*" >&2
}

log_success() {
    printf '✅ %s\n' "$*"
}

fail() {
  log_error "$*"
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || fail "Comando não encontrado: $1"
}

require_var() {
  local name="$1"
  [ -n "${!name:-}" ] || fail "Variável obrigatória não definida: $name"
}

require_arg() {
  local name="$1"
  [ -n "${!name:-}" ] || fail "Parâmetro obrigatória não definido: --$name"
}

require_file() {
  [ -f "$1" ] || fail "Arquivo não encontrado: $1"
  [ -s "$1" ] || fail "Arquivo encontra-se vazio: $1"
}

require_dir() {
  [ -d "$1" ] || fail "Diretório não encontrado: $1"
}

is_prerelease_tag() {
  local tag="${1:-}"
  local lower
  lower="$(printf '%s' "$tag" | tr '[:upper:]' '[:lower:]')"

  case "$lower" in
    *alpha*|*beta*|*rc*|*dev*|*pre*|*alfa*)
      return 0
      ;;
    *)
      return 1
      ;;
  esac
}
