# Phase 5B.18 — Expanded Read-only Production Artisan Runtime Diagnosis

Status: diagnostic implementation prepared; production execution pending a reviewed merge and a later manual workflow dispatch.

## Failure evidence and correction (Phase 5B.21)

The 2026-10-31 Hostinger diagnostic run [#765](https://github.com/mattari20/estudent-listing/actions/runs/41171997967) connected to production, began the runtime diagnostic, and exited with report status 94 because the JSON report did not appear. The old workflow redirected PHP stderr into a file under a temporary directory and then removed that directory in its EXIT trap, so the parser/runtime error was not retained in the GitHub job log. The previous embedded PHP source also contained incorrectly doubled namespace separators in executable references.

The diagnostic is now a committed PHP script at `scripts/ops/phase-5b18-runtime-diagnostic.php`, linted before execution. It writes a bounded partial JSON report even when an inspection step fails, records step names and exception classes, and exits nonzero when required evidence is incomplete. Report creation uses an exclusive temporary file, mode 0600, and an atomic rename; an existing report is never overwritten. The SSH wrapper retains PHP stderr in a mode-700 temporary directory and emits a bounded sanitized excerpt on failure before cleanup. The temporary script is no longer generated inline. Temporary parity Git data remains isolated from the live production repository.

The regression contract lints and executes the PHP script against a controlled temporary fixture, checks partial report creation, failure evidence, mode 0600, the 256 KiB limit, and refusal to overwrite.

## Report

The intended report destination remains `storage/app/private/diagnostics/phase-5b18-artisan-runtime.json`. It records PHP/Laravel runtime identity, Artisan registration and reflected definition, Composer class resolution and hashes, bootstrap cache inventory, expected/deployed source hashes, missing/different files, unexpected extras, two-way parity, step results and exception classes.

## Safety boundaries

The diagnostic never invokes `institutes:qualify-official-sources`, never contacts GCU/HEC, does not run migrations or write database records, does not clear caches, and does not change production application files or production Git refs/index/working tree.

## Execution gate

No production rerun is part of Phase 5B.21 development. After this focused PR passes CI and is merged through normal review, perform exactly one manually triggered run of the existing Hostinger Read-only Diagnostics workflow. Preserve any existing report and do not rerun automatically to overwrite it. Do not dispatch GCU qualification until actual production Artisan evidence establishes the loaded command's `--source` option.
