#!/bin/sh
set -eu
ROOT="$(mktemp -d)"
cleanup() { rm -rf "$ROOT"; }
trap cleanup EXIT HUP INT TERM
SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
GUARD="$SCRIPT_DIR/verify-approved-application-revision.sh"
[ -f "$GUARD" ] || { echo "ERROR: guard script missing" >&2; exit 1; }
git config --global user.name "Approved Revision Guard Test"
git config --global user.email "approved-revision-guard@example.invalid"
make_origin() {
 name="$1"
 git init --bare "$ROOT/$name.git" >/dev/null
 git init "$ROOT/$name-work" >/dev/null
 git -C "$ROOT/$name-work" checkout -b main >/dev/null 2>&1
 git -C "$ROOT/$name-work" config user.name "Guard Fixture"
 git -C "$ROOT/$name-work" config user.email "guard-fixture@example.invalid"
 mkdir -p "$ROOT/$name-work/app" "$ROOT/$name-work/config" "$ROOT/$name-work/docs"
 printf '<?php return 1;\n' > "$ROOT/$name-work/app/example.php"
 printf '<?php return [];\n' > "$ROOT/$name-work/config/example.php"
 printf 'fixture\n' > "$ROOT/$name-work/docs/readme.md"
 git -C "$ROOT/$name-work" add app config docs
 git -C "$ROOT/$name-work" commit -m "fixture: initial approved app" >/dev/null
 git -C "$ROOT/$name-work" remote add origin "$ROOT/$name.git"
 git -C "$ROOT/$name-work" push -u origin main >/dev/null 2>&1
 # The bare remote must advertise main as its default HEAD for clean clones.
 git --git-dir="$ROOT/$name.git" symbolic-ref HEAD refs/heads/main
 printf '%s' "$(git -C "$ROOT/$name-work" rev-parse HEAD)" > "$ROOT/$name-target"
 printf 'documentation-only\n' >> "$ROOT/$name-work/docs/readme.md"
 git -C "$ROOT/$name-work" add docs/readme.md
 git -C "$ROOT/$name-work" commit -m "docs: approved app unchanged" >/dev/null
 git -C "$ROOT/$name-work" push origin main >/dev/null 2>&1
}
make_origin success
TARGET="$(cat "$ROOT/success-target")"
git clone "$ROOT/success.git" "$ROOT/success-deploy" >/dev/null 2>&1
if sh "$GUARD" "$ROOT/success-deploy" "$TARGET" >"$ROOT/success.log" 2>&1; then
 grep -q 'target_reachability_status=0' "$ROOT/success.log"
 grep -q 'target_vs_deployed_application_diff_status=0' "$ROOT/success.log"
 grep -q 'tracked_worktree_application_drift_status=0' "$ROOT/success.log"
 grep -q 'tracked_index_application_drift_status=0' "$ROOT/success.log"
 grep -q 'deployed_application_code_status=APPLICATION_CODE_MATCH' "$ROOT/success.log"
else cat "$ROOT/success.log" >&2; exit 1; fi

git clone --depth=1 --branch main "file://$ROOT/success.git" "$ROOT/shallow-deploy" >/dev/null 2>&1
test -f "$ROOT/shallow-deploy/.git/shallow"
if sh "$GUARD" "$ROOT/shallow-deploy" "$TARGET" >"$ROOT/missing-target.log" 2>&1; then
 grep -q 'hostinger_git_shallow=true' "$ROOT/missing-target.log"
 grep -q 'target_object_initial_status=128\|target_object_initial_status=1' "$ROOT/missing-target.log"
 grep -q 'deployed_application_code_status=APPLICATION_CODE_MATCH' "$ROOT/missing-target.log"
else cat "$ROOT/missing-target.log" >&2; exit 1; fi

git clone "$ROOT/success.git" "$ROOT/fetch-failure-deploy" >/dev/null 2>&1
git -C "$ROOT/fetch-failure-deploy" remote set-url origin "$ROOT/not-present.git"
if sh "$GUARD" "$ROOT/fetch-failure-deploy" "$TARGET" >"$ROOT/fetch-failure.log" 2>&1; then echo "FAIL: unreachable origin accepted" >&2; exit 1; else
 grep -q 'fetch_main_status=' "$ROOT/fetch-failure.log"
 grep -q 'ERROR: origin/main fetch failed' "$ROOT/fetch-failure.log"
 ! grep -q 'deployed_application_code_status=APPLICATION_CODE_MATCH' "$ROOT/fetch-failure.log"
fi

make_origin unreachable
git -C "$ROOT/unreachable-work" checkout -b unapproved >/dev/null 2>&1
printf '<?php return 99;\n' > "$ROOT/unreachable-work/app/unapproved.php"
git -C "$ROOT/unreachable-work" add app/unapproved.php
git -C "$ROOT/unreachable-work" commit -m "fixture: unapproved side branch" >/dev/null
UNREACHABLE_TARGET="$(git -C "$ROOT/unreachable-work" rev-parse HEAD)"
git -C "$ROOT/unreachable-work" push origin unapproved >/dev/null 2>&1
git clone "$ROOT/unreachable.git" "$ROOT/unreachable-deploy" >/dev/null 2>&1
git -C "$ROOT/unreachable-deploy" fetch origin unapproved:refs/remotes/origin/unapproved >/dev/null 2>&1
if sh "$GUARD" "$ROOT/unreachable-deploy" "$UNREACHABLE_TARGET" >"$ROOT/unreachable.log" 2>&1; then echo "FAIL: unreachable target accepted" >&2; exit 1; else
 grep -q 'target_object_commit_status=0' "$ROOT/unreachable.log"
 grep -q 'target_reachability_status=' "$ROOT/unreachable.log"
 grep -q 'ERROR: approved target not proven reachable' "$ROOT/unreachable.log"
fi

make_origin drift
git -C "$ROOT/drift-work" checkout -b app-change main >/dev/null 2>&1
printf '<?php return 2;\n' > "$ROOT/drift-work/app/example.php"
git -C "$ROOT/drift-work" add app/example.php
git -C "$ROOT/drift-work" commit -m "app: deployment drift fixture" >/dev/null
git -C "$ROOT/drift-work" checkout main >/dev/null 2>&1
git -C "$ROOT/drift-work" merge --no-edit app-change >/dev/null
git -C "$ROOT/drift-work" push origin main >/dev/null 2>&1
git clone "$ROOT/drift.git" "$ROOT/drift-deploy" >/dev/null 2>&1
APPROVED_TARGET="$(cat "$ROOT/drift-target")"
if sh "$GUARD" "$ROOT/drift-deploy" "$APPROVED_TARGET" >"$ROOT/drift.log" 2>&1; then echo "FAIL: code drift accepted" >&2; exit 1; else
 grep -q 'target_vs_deployed_application_diff_status=1' "$ROOT/drift.log"
 grep -q 'ERROR: deployed application code differs from approved target' "$ROOT/drift.log"
fi

git clone "$ROOT/success.git" "$ROOT/dirty-deploy" >/dev/null 2>&1
printf '<?php return 4;\n' > "$ROOT/dirty-deploy/app/example.php"
if sh "$GUARD" "$ROOT/dirty-deploy" "$TARGET" >"$ROOT/dirty-worktree.log" 2>&1; then echo "FAIL: dirty worktree accepted" >&2; exit 1; else
 grep -q 'tracked_worktree_application_drift_status=1' "$ROOT/dirty-worktree.log"
 grep -q 'ERROR: tracked application-code worktree/index drift detected' "$ROOT/dirty-worktree.log"
fi
git clone "$ROOT/success.git" "$ROOT/dirty-index-deploy" >/dev/null 2>&1
printf '<?php return 5;\n' > "$ROOT/dirty-index-deploy/app/example.php"
git -C "$ROOT/dirty-index-deploy" add app/example.php
if sh "$GUARD" "$ROOT/dirty-index-deploy" "$TARGET" >"$ROOT/dirty-index.log" 2>&1; then echo "FAIL: dirty index accepted" >&2; exit 1; else
 grep -q 'tracked_index_application_drift_status=1' "$ROOT/dirty-index.log"
 grep -q 'ERROR: tracked application-code worktree/index drift detected' "$ROOT/dirty-index.log"
fi
echo "PASS: clean parity, shallow/missing object recovery, fetch failure, unreachable target, committed app drift, dirty worktree, dirty index"
