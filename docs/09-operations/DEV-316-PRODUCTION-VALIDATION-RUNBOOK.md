# DEV-316 — Production Validation Runbook

## Purpose

Define the final operator validation sequence before production data publication.

This runbook is separate from application readiness logic. Readiness endpoints provide machine-readable evidence; this document defines the human-controlled release sequence.

## 1. Environment validation

Confirm:
- PHP 8.5 is available.
- Laravel 13 dependencies are installed.
- Production database configuration is present through environment variables.
- Application key and required secrets are configured outside source control.
- .env is not committed.
- Storage, cache, queue, and logs are writable where required.
- HTTPS is active for the public directory.

## 2. Code validation

Run from the project root:

    composer install --no-interaction --prefer-dist --optimize-autoloader
    composer lint
    composer test

Do not treat a release as validated when either lint or the test suite fails.

## 3. Database validation

Run migrations in the intended deployment environment and verify:
- migrations complete without error;
- foreign keys are enabled;
- source/evidence/snapshot lineage tables exist;
- geography and canonical relationship constraints are present;
- no destructive migration is pending.

## 4. Readiness validation

For a selected import batch, inspect these operator endpoints in order:
1. /institutes/operations/imports/{batch}/verification
2. /institutes/operations/imports/{batch}/source-readiness
3. /institutes/operations/imports/{batch}/provenance-readiness
4. /institutes/operations/imports/{batch}/promotion-readiness
5. /institutes/operations/imports/{batch}/final-readiness
6. /institutes/operations/imports/{batch}/deployment-readiness
7. /institutes/operations/imports/{batch}/readiness-contract
8. /institutes/operations/imports/{batch}/end-to-end-readiness

All blocking findings must be zero before controlled promotion.

## 5. Public smoke validation

Check:
- /institutes/
- /institutes/search
- one canonical institution profile;
- sitemap;
- robots endpoint;
- public API endpoints;
- nearby discovery with valid coordinates;
- invalid/inactive records are not exposed.

## 6. Controlled promotion

Only after all readiness gates are clear:
- resolve remaining identity decisions;
- resolve conflicts;
- confirm geography;
- confirm evidence lineage;
- perform controlled promotion;
- re-run readiness checks;
- verify the resulting public record.

Do not bulk-promote merely because an import exists.

## 7. Post-release verification

Immediately after publication:
- check application logs;
- check database error logs;
- verify public pages;
- verify sitemap response;
- verify API responses;
- verify canonical URLs and structured data;
- verify monitoring policies and reverification queues.

## 8. Rollback principle

If a validation step fails, stop promotion and correct the underlying issue. Use the existing controlled reversal/reverification workflow where applicable. Do not repair production data by direct ad-hoc SQL unless an explicit emergency procedure has been approved.

## Release sign-off

A release is considered operationally validated only when:
- code validation passes;
- database validation passes;
- all readiness gates are clear for the selected batch;
- public smoke checks pass;
- controlled promotion is complete;
- post-release checks show no blocking issue.

This document does not claim that these checks have been executed; it defines the required final validation procedure.

## 8. Deployment evidence and read-only audit gate

A passing GitHub Actions run proves only the checks represented by that workflow; it does not prove Hostinger deployment, production database contents, backup recoverability, or external source correctness. Record the deployed commit from Hostinger's existing deployment history and compare it with the reviewed `main` commit before production checks. Do not change the existing Git integration to obtain this evidence.

For the protected data-gap audit, confirm the newest successful private diagnostics workflow, report timestamp, all expected sections, and read-only safety flags. Keep raw record-level output private. If deployment identity or report freshness is unknown, mark production validation blocked and do not proceed to controlled promotion.