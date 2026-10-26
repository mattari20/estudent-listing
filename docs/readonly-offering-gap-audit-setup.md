# Read-only offering-gap audit setup

The Hostinger read-only offering-gap workflow intentionally refuses to use the application's normal database account. Configure a dedicated database user in Hostinger with **SELECT-only** access to the Institutes database before running the workflow.

## GitHub Actions secrets

Add these repository Actions secrets (Settings → Secrets and variables → Actions → Repository secrets):

- `OFFERING_GAP_READONLY_DB_USERNAME`: dedicated audit database username
- `OFFERING_GAP_READONLY_DB_PASSWORD`: password for that user

Do not put credentials in workflow inputs, source files, logs, or chat. Do not reuse the application's database password.

## Hostinger database permissions

In hPanel → Databases → Management, create a dedicated database user and assign it only the SELECT/read permission for the Institutes database and the tables required by the preflight/reconciliation commands. Do not grant INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, TRIGGER, EVENT, EXECUTE, GRANT OPTION, or global privileges. If hPanel only offers broad permissions and cannot configure SELECT-only access, stop and contact Hostinger support for the supported least-privilege configuration; do not loosen the application's user.

The SSH workflow reads the Laravel database host/database settings from the existing app configuration but overrides the username/password for its own PHP processes. It does not edit `.env` or persist the audit credentials on Hostinger.

## Run and verify

Open [Hostinger Read-only Offering Gap Audit](../actions/workflows/hostinger-readonly-offering-gap-audit.yml), choose **Run workflow** on `main`, and keep the default project path. The workflow must report `READ_ONLY_CONFIRMED`, 36 register rows, zero mutations, and `ROW_LEVEL_REPORT_CREATED_REVIEW_REQUIRED`. Any failure is a stop condition; it does not authorize data changes.