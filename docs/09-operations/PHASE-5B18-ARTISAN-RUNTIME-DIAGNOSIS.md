# Phase 5B.18 — Expanded Read-only Production Artisan Runtime Diagnosis

Status: diagnostic implementation prepared; production execution pending workflow run.

Base main revision: `1ef5a78fcd06d1d723e09a0ba1274bb73e2b54d6`.

## Scope

The Hostinger Read-only Diagnostics workflow now includes a read-only runtime inspection before its pre-existing diagnostics. It uses `/opt/alt/php85/usr/bin/php`, fetches the expected source revision into an isolated temporary Git repository, and inspects runtime Composer resolution, Laravel bootstrap, command registry, reflection source/signature, bootstrap cache inventory, and expected-versus-actual application files. It records unexpected extra files without deleting or modifying them.

The bounded report destination is `storage/app/private/diagnostics/phase-5b18-artisan-runtime.json`. Existing reports are never overwritten. Temporary diagnostic files and the isolated parity repository are removed at exit. The report is installed with mode 600 and capped at 256 KiB.

## Strict non-goals

The diagnostic does not execute `institutes:qualify-official-sources`, perform HTTP requests, write database records, run migrations, clear caches, modify production application files, or mutate production Git refs/index/working tree. Existing database/schema diagnostic steps are retained.

## Execution gate

No Hostinger run ID or runtime diagnosis is asserted by this documentation. The workflow's read-only diagnostic step must execute and its JSON/logs must be inspected before assigning a production root cause. Do not dispatch the GCU qualification workflow until production Artisan help explicitly includes `--source`.