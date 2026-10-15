# Institutes Automation — Public Site Monitor

| Field | Value |
| --- | --- |
| Status | Initial automation layer |
| Workflow | `.github/workflows/institutes-public-monitor.yml` |
| Schedule | Daily at 08:30 Pakistan Standard Time (03:30 UTC), plus manual dispatch |
| Scope | Public HTTP availability only |
| Database access | None |
| Production writes | None |

## What it checks

The GitHub Actions workflow requests these public pages and expects HTTP 200:

- `https://estudent.pk/institutes/`
- `https://estudent.pk/institutes/university-of-the-punjab`

Each run writes a concise result to the Actions step summary and saves a sanitized text artifact for 14 days. A failed HTTP check fails the workflow so it is visible in GitHub Actions.

## What this does not prove

A successful HTTP response does not prove database health, completeness of institution/campus/program data, search correctness, or Hostinger deployment state. This workflow does not connect to Hostinger SSH or the production database and does not run Laravel Artisan commands.

## Next automation layer

After this monitor is verified, separately configure an approved least-privilege production diagnostic connection. Before enabling it, verify Hostinger SSH restrictions, key handling, host-key verification, secret storage, and how sanitized reports will be made available. Never expose `.env`, database credentials, personal data, or raw production reports in public artifacts/issues. Do not modify the existing Hostinger Git deployment integration or enable Remote MySQL.

Production database checks must remain read-only. No migrations, seeders, imports, promotions, activation, publishing, or repairs are authorized by this workflow.
