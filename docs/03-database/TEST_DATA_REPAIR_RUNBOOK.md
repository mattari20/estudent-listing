# Test Data Repair Runbook

**Purpose:** Repair the disposable Institutes Directory test dataset and verify the resulting canonical relationships before replacing test data with verified data.

**Authorization:** The operator has confirmed that the currently configured dataset is disposable dummy test data and has authorized create/edit/delete operations for this repair. This authorization does not grant the assistant a direct Hostinger terminal or database connection.

## Important execution boundary

Run these commands only in the existing Laravel application directory for `public_html/institutes` using Hostinger's Terminal (or an already configured SSH terminal). Do not paste `.env`, passwords, tokens, or connection secrets into chat.

The previous audit labelled its environment `production`. The operator has confirmed that the *data* is dummy test data. Before any write, confirm the configured application/database target is the intended test dataset. Do not run these commands against a different database.

## 1. Confirm application and capture a fresh read-only baseline

From the Laravel project root:

```bash
pwd
php artisan env
php artisan institutes:data-gap-report --json
php artisan institutes:data-gap-record-audit --json
php artisan institutes:geography-seed-preflight --json
```

Save the JSON output locally before continuing. The record-level audit can contain record names and IDs; share it only in this conversation if needed, and never include secrets.

Stop and report the output if a command is missing, the database connection fails, the geography preflight reports conflicts/duplicates, or the application points to the wrong dataset.

## 2. Seed the eight canonical top-level geography records

The existing command is transactional and is designed to create missing records without modifying existing records. It fails closed if duplicate identities or mismatched existing records are found.

Only after the preflight is reviewed and confirms no conflicts:

```bash
php artisan institutes:seed-pakistan-geography
```

Expected result on a completely empty geography table: **8 records created**. If it reports a different count, do not rerun repeatedly; capture the output and rerun the read-only preflight.

## 3. Verify geography and relationships

```bash
php artisan institutes:geography-seed-preflight --json
php artisan institutes:data-gap-report --json
php artisan institutes:data-gap-record-audit --json
```

Expected geography outcome: 8 canonical records, no duplicate identities, no mismatch conflicts, Pakistan as the parent of the seven first-level regions, and valid integrity checks.

This seed does **not** create campus records. Campus creation must use the application's existing structured enrichment/import workflow with an explicit parent institution and valid geography. The last available report showed zero enrichment import rows, so there is no verified campus candidate queue to promote yet.

## 4. What to send back for the next repair pass

Paste the following command outputs as text, or upload a text file containing them:

1. `php artisan env`
2. The complete output of `institutes:geography-seed-preflight --json` before the seed.
3. The seed command's result.
4. The complete output of the three verification commands after the seed.

Redact database credentials, application keys, tokens, private URLs containing credentials, and any personal information. Do not share `.env`.

## Current known data gaps

The latest available report is dated 2026-10-16 and is not a fresh post-repair report. It showed 10 active institutions, 0 campuses, 1 active program, 0 program offerings, 0 admission intakes, 0 geography rows, 24 import rows (11 promoted, 6 candidates, 7 rejected), and 0 enrichment import rows. Treat these as a historical baseline only; refresh the diagnostics before deciding any record-level cleanup.

## Repair order after geography

1. Refresh and inspect the private record-level audit.
2. Trace test-labelled promoted rows to their canonical records and promotion decisions; classify them before deleting or reversing anything.
3. Determine the intended campus coverage policy (one primary campus per institution vs. all actual campuses).
4. Prepare and validate campus candidates with parent institution and geography links; use the existing promotion flow rather than direct SQL.
5. Review the existing program and decide whether it is test data; do not fabricate programs, offerings, or admissions information.
6. Re-run diagnostics and record expected vs. actual counts.

Never mark a source as verified merely because it is active, and never claim national coverage from the current small test dataset.
