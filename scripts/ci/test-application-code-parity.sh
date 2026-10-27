#!/usr/bin/env bash
set -euo pipefail

# Regression test for the application-code parity primitive used by Hostinger
# diagnostics, controlled deployment, and governed discovery.
ROOT="$(mktemp -d)"
cleanup() { rm -rf "$ROOT"; }
trap cleanup EXIT

git config --global user.name "Parity Test"
git config --global user.email "parity-test@example.invalid"
git init --bare "$ROOT/origin.git" >/dev/null
git clone "$ROOT/origin.git" "$ROOT/work" >/dev/null 2>&1
cd "$ROOT/work"
git checkout -b main >/dev/null 2>&1
mkdir -p app docs config
printf '<?php return 1;\n' > app/parity-fixture.php
printf '<?php return [];\n' > config/institute_agent.php
printf 'fixture\n' > docs/README.md
git add app config docs
git commit -m 'fixture: initial application revision' >/dev/null
TARGET="$(git rev-parse HEAD)"
git push -u origin main >/dev/null 2>&1

# Matching code after a docs-only commit must pass despite different commit IDs.
printf 'docs-only update\n' >> docs/README.md
git add docs/README.md
git commit -m 'docs: harmless deployment-independent change' >/dev/null
git push origin main >/dev/null 2>&1
DEPLOYED="$(git rev-parse HEAD)"
test "$TARGET" != "$DEPLOYED"
git diff --quiet "$TARGET" "$DEPLOYED" -- app bootstrap config database resources routes composer.json composer.lock public artisan

# A stale deployment with a changed app file must fail parity.
printf '<?php return 2;\n' > app/parity-fixture.php
git add app/parity-fixture.php
git commit -m 'app: changed application code' >/dev/null
STALE_TARGET="$(git rev-parse HEAD)"
if git diff --quiet "$TARGET" "$STALE_TARGET" -- app bootstrap config database resources routes composer.json composer.lock public artisan; then
  echo 'FAIL: stale deployed code was accepted' >&2
  exit 1
fi

# A partial deployment / local application edit must fail the clean-tree check.
if git diff --quiet HEAD -- app bootstrap config database resources routes composer.json composer.lock public artisan; then
  : # clean before creating the partial-deployment simulation
fi
printf '<?php return 3;\n' > app/parity-fixture.php
if git diff --quiet HEAD -- app bootstrap config database resources routes composer.json composer.lock public artisan; then
  echo 'FAIL: partial application-code change was accepted' >&2
  exit 1
fi
git checkout -- app/parity-fixture.php
printf '<?php return 4;\\n' > app/untracked-parity-fixture.php
if [ -z "$(git status --porcelain --untracked-files=all)" ]; then
  echo 'FAIL: untracked application code was not reported' >&2
  exit 1
fi
rm app/untracked-parity-fixture.php

# A shallow checkout must not derive an application revision with path-limited
# git log; fetch full history, then compare code content. The test asserts the
# documented repair path works for a workflow/docs-only tip.
git clone --depth=1 --branch main "file://$ROOT/origin.git" "$ROOT/shallow" >/dev/null 2>&1
cd "$ROOT/shallow"
TIP="$(git rev-parse HEAD)"
test -f .git/shallow
git fetch --quiet --no-tags --unshallow origin main
git cat-file -e "$TARGET^{commit}"
git diff --quiet "$TARGET" "$TIP" -- app bootstrap config database resources routes composer.json composer.lock public artisan

echo 'PASS: docs-only head, stale code, partial application deployment, and shallow-history parity cases'
