#!/usr/bin/env bash
# Fail-closed guard for every manually dispatched production workflow.
set -euo pipefail
set +x

fail() { printf 'PRODUCTION DISPATCH REJECTED: %s\n' "$1" >&2; exit 1; }

[[ "${GITHUB_EVENT_NAME:-}" == "workflow_dispatch" ]] || fail "only workflow_dispatch is accepted"
[[ "${GITHUB_REF:-}" == "refs/heads/main" ]] || fail "dispatch must target refs/heads/main"

target="${TARGET_SHA:-}"
reason="${OPERATION_REASON:-}"
confirmation="${CONFIRM_PRODUCTION_OPERATION:-}"
expected="${EXPECTED_CONFIRMATION:-}"

[[ "$target" =~ ^[0-9a-f]{40}$ ]] || fail "target_sha must be exactly 40 lowercase hexadecimal characters"
[[ "${GITHUB_SHA:-}" =~ ^[0-9a-f]{40}$ ]] || fail "GitHub event SHA is invalid"
[[ "$target" == "$GITHUB_SHA" ]] || fail "target_sha must equal the dispatch event SHA"
[[ "$reason" =~ ^[A-Za-z0-9][A-Za-z0-9._:/#-]{7,127}$ ]] || fail "operation_reason must be 8-128 safe ASCII characters (letters, digits, dot, underscore, colon, slash, hash, or hyphen)"
[[ -n "$expected" && "$confirmation" == "$expected" ]] || fail "operation-specific confirmation does not match"

operation_confirmation="${OPERATION_CONFIRMATION:-}"
expected_operation_confirmation="${EXPECTED_OPERATION_CONFIRMATION:-}"
if [[ -n "$operation_confirmation" || -n "$expected_operation_confirmation" ]]; then
  [[ -n "$expected_operation_confirmation" && "$operation_confirmation" == "$expected_operation_confirmation" ]] || fail "dedicated operation confirmation does not match"
fi

git rev-parse --verify "$target^{commit}" >/dev/null 2>&1 || fail "target SHA is not available locally"
git fetch --quiet --no-tags origin main
main_sha="$(git rev-parse refs/remotes/origin/main)"
[[ "$target" == "$main_sha" ]] || fail "target SHA is no longer the current main revision"

printf 'production_dispatch_authorized=true\n'
printf 'operation_reason=%s\n' "$reason"
printf 'target_sha=%s\n' "$target"
printf 'authorization_mode=manual_dispatch_plus_protected_environment\n'
