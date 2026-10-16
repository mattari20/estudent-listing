# Private production diagnostics report delivery

## Purpose

The workflow `.github/workflows/institutes-private-diagnostics-report.yml` retrieves the protected read-only Institutes data-gap report and stores it in the separate private repository `mattari20/institutes-private-reports`.

The current workflow supports both manual dispatch and a daily scheduled run (04:00 UTC / 09:00 Pakistan time). It does not run on pushes. Confirm the schedule in the deployed `main` workflow before relying on freshness; a successful run is not proof that the latest expected scheduled run occurred.

## Required Actions secrets in `mattari20/estudent-listing`

- `INSTITUTES_DIAGNOSTICS_TOKEN`: the same strong token configured in the production application's environment.
- `INSTITUTES_PRIVATE_REPORTS_TOKEN`: a fine-grained GitHub personal access token scoped only to the private `institutes-private-reports` repository, with **Contents: Read and write** permission. Metadata read access is required by GitHub for repository access.

Do not place either token in source code, workflow files, issues, pull requests, screenshots, or chat messages. Do not use a classic token with broad account-wide permissions when a narrowly scoped fine-grained token is available.

## Create the private-repository token

1. In GitHub, open Settings → Developer settings → Personal access tokens → Fine-grained tokens.
2. Generate a token with a short, purpose-specific name and an expiration.
3. Select the owner `mattari20`.
4. Under repository access, select **Only select repositories** and choose `institutes-private-reports`.
5. Grant **Contents: Read and write** under repository permissions. Keep all other permissions at no access unless GitHub requires metadata read.
6. Generate and copy the token once. Store it in a password manager; never paste it into chat or a screenshot.
7. In the public `estudent-listing` repository, open Settings → Secrets and variables → Actions → New repository secret.
8. Set the name to `INSTITUTES_PRIVATE_REPORTS_TOKEN` and the value to the token. Save it.

A repository administrator with write access to the public source repository could alter workflows that consume repository secrets. Keep write access restricted to trusted maintainers, review workflow changes carefully, and revoke this token if it is no longer needed.

## Run it manually

1. Open Actions in `mattari20/estudent-listing`.
2. Select **Retrieve Institutes Production Diagnostics (Private)**.
3. Click **Run workflow** on branch `main`.

The workflow checks that unauthenticated and invalid bearer tokens are rejected, retrieves the authenticated JSON report over validated HTTPS, verifies the report contract and `mutation_performed: false`, then stores the report only in the private repository.

The workflow intentionally does not print report contents, counts, tokens, API response bodies, or the private report path; it does not create a public Actions artifact or include report data in the job summary.

## Safety boundaries and limitations

- This workflow is read-only against the production diagnostics endpoint; it does not run imports, migrations, seeders, or database writes.
- A successful run proves retrieval and private-repository storage. It does not mean the production data is complete or correct.
- The report is retained as a JSON file in the private repository's Git history. Limit repository collaborators and use the repository's retention and access controls appropriately.
- Keep this workflow manual-only until reviewed and explicitly approved.
