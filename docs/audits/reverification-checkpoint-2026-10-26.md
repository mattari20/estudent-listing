# Institutes Directory — Reverification Checkpoint

**Checked:** 2026-10-26. **Mode:** GitHub CI and read-only live diagnostic review. No production writes, migrations, imports, promotions, deletions, or discovery runs were performed.

## Executive status

**Phase 2 is not complete.** PR #821 (CSV BOM/duplicate-header validation) and PR #824 (safe import-failure diagnostics) are merged. Their relevant Test and Laravel CI workflows passed. Live verification after the latest main revision failed closed: Hostinger is still on an older checkout revision; the official-source refresh schema is missing a required table/migration; and the public listing page passed while the tested University of the Punjab detail URL returned HTTP 404.

## Code and CI

- PR #821 merged: `7d01c28775527d7a4cf6de6fd7331a72d3014fa2`.
- PR #824 merged: `69e29d4e17fefe8dbec7d28b805c6086caba5720`.
- PR #824 Test passed: https://github.com/mattari20/estudent-listing/actions/runs/40321367935
- PR #824 Laravel CI passed: https://github.com/mattari20/estudent-listing/actions/runs/40321367886
- Main Test passed: https://github.com/mattari20/estudent-listing/actions/runs/40321237926
- Main Laravel CI passed: https://github.com/mattari20/estudent-listing/actions/runs/40321237612

Passing CI validates only the tests run by those workflows, not production deployment or data health.

## Live verification blockers

### 1. Hostinger revision mismatch

- Read-only diagnostics: https://github.com/mattari20/estudent-listing/actions/runs/40321237621
- Automated live integrity audit: https://github.com/mattari20/estudent-listing/actions/runs/40321237725

Hostinger checkout revision was `59c4fd8a9cef402794588983049e53f41d9e0658`; expected application revision was `7d01c28775527d7a4cf6de6fd7331a72d3014fa2`. The checks stopped on mismatch. A zero-count summary from that blocked run must **not** be interpreted as a verified empty production database. No production data was changed.

### 2. Official-source refresh schema not ready

Read-only check: https://github.com/mattari20/estudent-listing/actions/runs/40321237681

The table `official_source_refresh_events` does not exist. Migration `2026_10_25_000002_create_official_source_refresh_events_table` exists in code but is not recorded as applied. The check reported `schema_ready=false` and intentionally ran no migration or production mutation. Review migration dependencies and schema compatibility, then use the approved deployment process; do not run an ad hoc migration.

### 3. Public detail URL returns 404

Public monitor: https://github.com/mattari20/estudent-listing/actions/runs/40321237708

- `https://estudent.pk/institutes/`: HTTP 200 and expected listing content.
- `https://estudent.pk/institutes/university-of-the-punjab`: HTTP 404 after retries.

The tested detail URL is failing. Root cause is not yet known; verify the actual detail link emitted by the live listing and its slug/route relationship before changing code or data.

## Completion table

| Workstream | Status |
|---|---|
| CSV header hardening | Merged; CI passed |
| Import audit status and safe failed-batch reporting | Merged; CI passed |
| Public listing smoke check | Passed, HTTP 200 |
| Punjab University detail smoke check | Failed, HTTP 404 for tested URL |
| Hostinger code revision parity | Failed; deployed checkout is behind expected revision |
| Official-source refresh schema | Not ready; migration not applied |
| Fresh pre-import baseline | Must be regenerated after deployment/revision match |
| Current failed-batch/duplicate/identity evidence review | Pending valid current-revision live diagnostics |
| Official verification of missing offerings | Pending |
| Full route/controller security, AI-agent, geography, search and pagination review | Pending |
| Phase 2 sign-off | Not authorized |

## Next steps in order

1. Deploy the reviewed main revision using the approved Hostinger deployment path, then verify the exact deployed revision.
2. Review migration `2026_10_25_000002_create_official_source_refresh_events_table`, dependencies and production schema compatibility. Apply only through an approved deployment process.
3. Re-run read-only integrity diagnostics and the public site monitor.
4. Extract the actual Punjab University detail URL from the live listing and verify the route/slug/data relationship.
5. Generate a timestamped `pre_fresh_import` baseline only after the revision guard passes.
6. Re-audit failed batches, duplicates, identity decisions and missing offerings using current data, then finish route/controller security, discovery, geography, search/pagination and schema tests.
7. Keep Phase 2 open until critical checks pass and data gaps have evidence-backed dispositions.

## Safety statement

No production records were modified and no migrations were run during this review. Do not delete dummy records, approve candidates, promote records, or retry failed batches based on historical IDs/counts. Historical counts are not fresh-import acceptance targets.
