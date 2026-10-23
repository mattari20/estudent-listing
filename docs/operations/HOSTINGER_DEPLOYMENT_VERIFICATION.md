# Hostinger Deployment Verification Runbook

## Purpose

Verify that the application code on Hostinger matches the latest application-code revision on GitHub `main`. A successful HTTP response alone does not prove that the latest code is deployed.

## Automated source of truth

Use the **Hostinger Read-only Diagnostics** workflow in GitHub Actions:

- Workflow: `.github/workflows/hostinger-readonly-diagnostics.yml`
- Result fields: `deployed_revision`, `expected_revision`, and `deployment_revision_status`
- The expected revision is derived from the latest commit affecting application/deployment paths, so documentation-only and workflow-only commits do not create false drift alarms.

## Interpret the result

| Status | Meaning | Safe response |
|---|---|---|
| `MATCH` | Hostinger and GitHub agree on the application-code revision. | Continue with the public-site smoke check and read-only data audit. |
| `DRIFT` | The deployed checkout does not match the expected application revision. | Wait for the configured Hostinger Git deployment to finish, then rerun the read-only diagnostics. If drift persists, investigate deployment configuration/logs before changing data. |
| `UNVERIFIABLE` | Git metadata was not available on the deployed checkout. | Treat deployment identity as unconfirmed. Repair metadata/configuration through the documented deployment workflow; do not assume that HTTP 200 proves deployment. |

## Required checks after application changes

1. Test and Laravel CI pass on the candidate commit.
2. The public monitor returns HTTP 200 and expected content for `/institutes/` and a representative active profile.
3. Hostinger diagnostics report `deployment_revision_status=MATCH`.
4. The read-only data-gap report has been reviewed for schema health, canonical relationship gaps, import-row review dispositions, and source-evidence availability.
5. Any promotion, publication, cleanup, or import write is separately reviewed and authorized. Diagnostics do not authorize mutations.

## Safety rules

- Do not bypass a drift failure by disabling the gate.
- Do not promote/retry failed import rows merely because the website is reachable.
- Do not modify production records as part of deployment verification.
- After an application-code merge, a temporary `DRIFT` result is expected until Hostinger completes deployment. Rerun verification rather than treating the first failure as proof of a broken deployment.
- Workflow/docs-only commits should not trigger false application-revision drift when the deployed application code is unchanged.

## Recent verified baseline

On 2026-10-23, the diagnostics rerun after PR #353 reported `MATCH` with deployed and expected application revision `5e260e4daafd387ef72b397a7038d321c01a5e47`. This is a historical verification point, not a permanent assertion about the current deployment.
