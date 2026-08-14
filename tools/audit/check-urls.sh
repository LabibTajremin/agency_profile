#!/usr/bin/env bash
#
# Proves every URL the audits measure actually answers 200 before measuring it.
#
# Lighthouse reports an unreachable page as a runtime error, not a low score, so a wrong archive
# base and a slow page fail the job in exactly the same way. This separates them: either a URL
# is missing, or the page is too slow, and those have different fixes.

set -uo pipefail

BASE_URL="${EDULUME_BASE_URL:-http://localhost:8888}"

URLS=(
  "/"
  "/courses/"
  "/destinations/"
  "/institutions/"
  "/services/"
  "/about/"
  "/contact/"
)

failed=0

for path in "${URLS[@]}"; do
  status="$(curl -sS -o /dev/null -w '%{http_code}' -L "${BASE_URL}${path}" || echo "000")"

  if [ "$status" = "200" ]; then
    printf '  200  %s\n' "$path"
  else
    printf '  %s  %s\n' "$status" "$path"
    failed=1
  fi
done

if [ "$failed" -ne 0 ]; then
  printf '\nOne or more audited URLs did not answer 200. The audits measure pages, not 404s.\n' >&2
  exit 1
fi

printf '\nEvery audited URL answers 200.\n'
