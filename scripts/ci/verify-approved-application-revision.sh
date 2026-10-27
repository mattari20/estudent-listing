#!/bin/sh
set -eu
PROJECT_PATH=${1:?project path required}
TARGET_REVISION=${2:?target SHA required}
VERIFY_REF=refs/remotes/origin/step1-verification-main
APP_CODE_PATHS="app bootstrap config database resources routes composer.json composer.lock public artisan"
cd "$PROJECT_PATH" || { echo "ERROR: unable to enter project path"; exit 4; }
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then echo "ERROR: Hostinger Git metadata unavailable"; exit 4; fi
if DEPLOYED_REVISION="$(git rev-parse HEAD 2>&1)"; then HEAD_STATUS=0; else HEAD_STATUS=$?; echo "deployed_head_status=$HEAD_STATUS"; echo "ERROR: unable to resolve deployed HEAD"; exit 4; fi
echo "deployed_head_status=$HEAD_STATUS"
echo "deployed_revision=$DEPLOYED_REVISION"
echo "expected_application_revision=$TARGET_REVISION"
case "$TARGET_REVISION" in *[!0-9a-f]*|'') echo "ERROR: expected revision is not a lowercase SHA"; exit 5;; esac
if [ "${#TARGET_REVISION}" -ne 40 ]; then echo "ERROR: expected revision SHA length is invalid"; exit 5; fi
if REMOTE_URL="$(git remote get-url origin 2>/dev/null)"; then REMOTE_STATUS=0; else REMOTE_STATUS=$?; REMOTE_URL=""; fi
if [ "$REMOTE_STATUS" -eq 0 ] && [ -n "$REMOTE_URL" ]; then echo "origin_remote_present=true"; else echo "origin_remote_present=false"; echo "ERROR: origin remote absent or unreadable"; exit 5; fi
unset REMOTE_URL
if git cat-file -e "$TARGET_REVISION^{commit}" 2>/dev/null; then TARGET_OBJECT_STATUS=0; else TARGET_OBJECT_STATUS=$?; fi
echo "target_object_initial_status=$TARGET_OBJECT_STATUS"
SHALLOW_PATH="$(git rev-parse --git-path shallow 2>/dev/null || true)"
if [ -n "$SHALLOW_PATH" ] && [ -f "$SHALLOW_PATH" ]; then
 echo "hostinger_git_shallow=true"
 if git fetch --unshallow --no-tags origin "main:$VERIFY_REF"; then FETCH_MAIN_STATUS=0; else FETCH_MAIN_STATUS=$?; fi
else
 echo "hostinger_git_shallow=false"
 if git fetch --no-tags origin "main:$VERIFY_REF"; then FETCH_MAIN_STATUS=0; else FETCH_MAIN_STATUS=$?; fi
fi
echo "fetch_main_status=$FETCH_MAIN_STATUS"
if [ "$FETCH_MAIN_STATUS" -ne 0 ]; then echo "ERROR: origin/main fetch failed; refusing database inspection"; exit 5; fi
if git cat-file -e "$TARGET_REVISION^{commit}" 2>/dev/null; then TARGET_OBJECT_STATUS=0; else TARGET_OBJECT_STATUS=$?; fi
echo "target_object_after_branch_fetch_status=$TARGET_OBJECT_STATUS"
if [ "$TARGET_OBJECT_STATUS" -ne 0 ]; then
 if git fetch --no-tags origin "$TARGET_REVISION"; then FETCH_TARGET_STATUS=0; else FETCH_TARGET_STATUS=$?; fi
 echo "fetch_exact_target_status=$FETCH_TARGET_STATUS"
 if [ "$FETCH_TARGET_STATUS" -ne 0 ]; then echo "ERROR: exact approved commit fetch failed"; exit 5; fi
else echo "fetch_exact_target_status=NOT_REQUIRED_TARGET_OBJECT_PRESENT"; fi
if git cat-file -e "$TARGET_REVISION^{commit}" 2>/dev/null; then
 TARGET_OBJECT_STATUS=0
 if TARGET_OBJECT_TYPE="$(git cat-file -t "$TARGET_REVISION" 2>/dev/null)"; then TARGET_TYPE_STATUS=0; else TARGET_TYPE_STATUS=$?; TARGET_OBJECT_TYPE=UNAVAILABLE; fi
else TARGET_OBJECT_STATUS=$?; TARGET_TYPE_STATUS=$TARGET_OBJECT_STATUS; TARGET_OBJECT_TYPE=UNAVAILABLE; fi
echo "target_object_commit_status=$TARGET_OBJECT_STATUS"
echo "target_object_type_status=$TARGET_TYPE_STATUS"
echo "target_object_type=$TARGET_OBJECT_TYPE"
if [ "$TARGET_OBJECT_STATUS" -ne 0 ] || [ "$TARGET_TYPE_STATUS" -ne 0 ] || [ "$TARGET_OBJECT_TYPE" != commit ]; then echo "ERROR: approved target unavailable or not a commit"; exit 5; fi
if git fetch --no-tags origin "main:$VERIFY_REF"; then FETCH_VERIFIED_MAIN_STATUS=0; else FETCH_VERIFIED_MAIN_STATUS=$?; fi
echo "fetch_verified_main_status=$FETCH_VERIFIED_MAIN_STATUS"
if [ "$FETCH_VERIFIED_MAIN_STATUS" -ne 0 ]; then echo "ERROR: unable to refresh named verification ref"; exit 5; fi
if VERIFIED_MAIN_REVISION="$(git rev-parse "$VERIFY_REF" 2>&1)"; then VERIFIED_MAIN_STATUS=0; else VERIFIED_MAIN_STATUS=$?; VERIFIED_MAIN_REVISION=UNAVAILABLE; fi
echo "verified_main_revision_status=$VERIFIED_MAIN_STATUS"
echo "verified_main_revision=$VERIFIED_MAIN_REVISION"
if [ "$VERIFIED_MAIN_STATUS" -ne 0 ]; then echo "ERROR: named main ref cannot be resolved"; exit 5; fi
if git merge-base --is-ancestor "$TARGET_REVISION" "$VERIFY_REF"; then REACHABILITY_STATUS=0; else REACHABILITY_STATUS=$?; fi
echo "target_reachability_status=$REACHABILITY_STATUS"
if MERGE_BASE="$(git merge-base "$TARGET_REVISION" "$VERIFY_REF" 2>&1)"; then MERGE_BASE_STATUS=0; else MERGE_BASE_STATUS=$?; MERGE_BASE=UNAVAILABLE; fi
echo "merge_base_status=$MERGE_BASE_STATUS"
echo "merge_base_sha=$MERGE_BASE"
if [ "$REACHABILITY_STATUS" -ne 0 ] || [ "$MERGE_BASE_STATUS" -ne 0 ]; then echo "ERROR: approved target not proven reachable from current origin/main"; exit 5; fi
if git diff --quiet "$TARGET_REVISION" "$DEPLOYED_REVISION" -- $APP_CODE_PATHS; then APP_DIFF_STATUS=0; else APP_DIFF_STATUS=$?; fi
echo "target_vs_deployed_application_diff_status=$APP_DIFF_STATUS"
if [ "$APP_DIFF_STATUS" -eq 1 ]; then echo "ERROR: deployed application code differs from approved target"; exit 6; elif [ "$APP_DIFF_STATUS" -ne 0 ]; then echo "ERROR: unable to verify application parity"; exit 6; fi
if git diff --quiet HEAD -- $APP_CODE_PATHS; then WORKTREE_DIFF_STATUS=0; else WORKTREE_DIFF_STATUS=$?; fi
echo "tracked_worktree_application_drift_status=$WORKTREE_DIFF_STATUS"
if git diff --cached --quiet HEAD -- $APP_CODE_PATHS; then INDEX_DIFF_STATUS=0; else INDEX_DIFF_STATUS=$?; fi
echo "tracked_index_application_drift_status=$INDEX_DIFF_STATUS"
if [ "$WORKTREE_DIFF_STATUS" -ne 0 ] || [ "$INDEX_DIFF_STATUS" -ne 0 ]; then echo "ERROR: tracked application-code worktree/index drift detected"; exit 7; fi
if FINAL_HEAD="$(git rev-parse HEAD 2>&1)"; then FINAL_HEAD_STATUS=0; else FINAL_HEAD_STATUS=$?; FINAL_HEAD=UNAVAILABLE; fi
echo "final_deployed_head_status=$FINAL_HEAD_STATUS"
echo "final_deployed_head=$FINAL_HEAD"
if [ "$FINAL_HEAD_STATUS" -ne 0 ] || [ "$FINAL_HEAD" != "$DEPLOYED_REVISION" ]; then echo "ERROR: deployed HEAD changed during verification"; exit 7; fi
echo "deployed_application_code_status=APPLICATION_CODE_MATCH"
