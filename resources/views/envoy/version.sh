next_version() {
  local version_option="$1"
  local last_tag="${2:-}"
  local year month clean_tag vyear vmonth vpatch

  year="$(date +%Y)"
  month="$(date +%-m)"

  if [ "$version_option" != "latest" ]; then
    printf '%s\n' "$version_option"
    return 0
  fi

  if [ -z "$last_tag" ]; then
    printf '%s.%s.1\n' "$year" "$month"
    return 0
  fi

  clean_tag="${last_tag#v}"

  if [[ "$clean_tag" =~ ^([0-9]{4})\.([0-9]{1,2})\.([0-9]+)$ ]]; then
    vyear="${BASH_REMATCH[1]}"
    vmonth="${BASH_REMATCH[2]}"
    vpatch="${BASH_REMATCH[3]}"

    if [ "$vyear" = "$year" ] && [ "$vmonth" = "$month" ]; then
      printf '%s.%s.%s\n' "$year" "$month" "$((vpatch + 1))"
    else
      printf '%s.%s.1\n' "$year" "$month"
    fi
  else
    log_warn "Última tag em formato inesperado: $last_tag"
    printf '%s.%s.1\n' "$year" "$month"
  fi
}
