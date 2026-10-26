# Hostinger read-only audit access constraints

Date checked: 2026-10-26

## Hosting limitation

Hostinger's support documentation states that each MySQL database on web hosting has one database user; multiple users may require a VPS plan. Source: https://www.hostinger.com/support/1583542-how-to-create-a-new-mysql-database-in-hostinger/

The current shared-hosting setup therefore cannot assume a second user can be attached to the existing application database through hPanel. Do not claim that the application's existing DB user is read-only unless grants are independently inspected and documented.

## Required safe verification steps

1. Configure `HOSTINGER_SSH_FINGERPRINT` with a fingerprint obtained and verified through a trusted channel. Do not trust an unverified `ssh-keyscan` result as the source of truth.
2. Run the read-only audit workflow after the secret is configured.
3. Inspect the uploaded artifact and verify its exact commit, intended database, 36-row count, and zero-mutation declaration.
4. Independently verify effective database grants using an available trusted Hostinger-supported method. If grants cannot be verified, record the check as blocked/skipped rather than asserting least privilege.
5. Review every row before preparing any future record-level repair plan.

The GitHub connector cannot create repository secrets or dispatch this workflow in the current tool setup. Those steps must not be reported as completed until a run proves them.

A successful workflow confirms process execution only; it does not prove every row is correct or that the database account is SELECT-only. No production writes, migrations, approvals, promotions, or deletions are authorized by this document.
