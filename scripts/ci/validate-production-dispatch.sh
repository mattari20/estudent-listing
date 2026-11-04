#!/usr/bin/env bash
# Fail-closed authorization gate for production read-only workflows.
set -euo pipefail
set +x

# The workflow checks this script out into a dedicated path; anchor Git commands
# to that checkout rather than the caller workspace.
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

fail() {
  printf 'PRODUCTION DISPATCH REJECTED: %s\n' "$1" >&2
  exit 1
}

[[ "${GITHUB_EVENT_NAME:-}" == "workflow_dispatch" ]] || fail "only workflow_dispatch is accepted"
[[ "${GITHUB_REF:-}" == "refs/heads/main" ]] || fail "dispatch must target refs/heads/main"

target="${TARGET_SHA:-}"
reason="${OPERATION_REASON:-}"
confirmation="${CONFIRM_PRODUCTION_OPERATION:-}"
expected="${EXPECTED_CONFIRMATION:-}"

[[ "$target" =~ ^[0-9a-f]{40}$ ]] || fail "target_sha must be exactly 40 lowercase hexadecimal characters"
[[ "${GITHUB_SHA:-}" =~ ^[0-9a-f]{40}$ ]] || fail "dispatch event SHA is invalid"
[[ "$target" == "$GITHUB_SHA" ]] || fail "target_sha must equal the immutable dispatch event SHA"
[[ "$reason" =~ ^[A-Za-z0-9][A-Za-z0-9._:/#-]{7,127}$ ]] || fail "operation_reason must be 8-128 safe ASCII characters"
[[ -n "$expected" && "$expected" != "AUTHORIZE-PRODUCTION-OPERATION" ]] || fail "an operation-specific confirmation phrase is required"
[[ "$confirmation" == "$expected" ]] || fail "operation-specific confirmation does not match"

git rev-parse --verify "$target^{commit}" >/dev/null 2>&1 || fail "target SHA is not available in the checked-out repository"
git fetch --quiet --no-tags origin main
main_sha="$(git rev-parse refs/remotes/origin/main)"
[[ "$target" == "$main_sha" ]] || fail "target SHA is no longer the current main revision"

printf 'production_dispatch_authorized=true\n'
printf 'target_sha=%s\n' "$target"
printf 'operation_reason=%s\n' "$reason"
printf 'authorization_mode=manual_dispatch_exact_main_sha_operation_confirmation\n'
