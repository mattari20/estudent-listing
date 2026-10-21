# Institution import deduplication review — 2026-10-21

## Outcome

- Canonical institution records checked: **10** (read-only production export from Hostinger diagnostic run).
- Recorded aliases checked: **10**. The current export contains one official-name alias per canonical record; no former-name aliases were returned.
- Candidate rows in original pack: **10**.
- Rows included in this single clean CSV: **9**.
- Rows held out for manual identity/hierarchy review: **1** (COMSATS University Islamabad).

## Canonical records considered

The read-only production export returned these active canonical institutions: Government College University Lahore; University of Engineering and Technology Lahore; University of the Punjab; University of Central Punjab; University of Veterinary and Animal Sciences Lahore; University of Education Lahore; Lahore College for Women University; Lahore University of Management Sciences; COMSATS University Islamabad Lahore Campus; University of Lahore.

None of the nine included candidates has an exact canonical name, official-name, short-name, slug, or exported alias match in that 10-record snapshot. This is a deterministic exact-match result, **not a guarantee that no semantic or historic duplicate exists**.

## Held-out candidate

**COMSATS University Islamabad** is held out rather than automatically accepted or rejected. Production already has canonical institution ID 9, COMSATS University Islamabad Lahore Campus. This may be a campus incorrectly represented as an institution, a related institution identity, or a hierarchy/data-model issue. Do not create a second canonical institution until this relationship is resolved through the application's identity-review process.

## Important limitations

- This CSV is the **only importer input** from this pack. Do not upload the source manifest or this report.
- Institution names and source leads were assembled from the official-site/HEC manifest, but the manifest itself labels these as research leads requiring source capture in the app. This deduplication step does not claim that every candidate's source evidence has already been captured or approved.
- institution_type and status remain blank intentionally; do not infer either field from the name. Follow the importer schema and existing workflow.
- A candidate's presence in this file means only that it passed exact-match comparison against the current export snapshot. It is **not approved**, and it must still go through import validation, source evidence capture, conflict review, and explicit approval.
- No production records were created, modified, approved, promoted, or published during this process.
