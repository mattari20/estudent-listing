# DEV-298 — API Data Access Hardening

**Status:** Implemented on branch; pending merge into `main`  
**Scope:** Institutes Directory API v1 public read access

## 1. Objective

Harden the existing versioned Institutes Directory API so that its public representations follow the same lifecycle and discovery boundaries already established for the public web directory.

The change is deliberately read-only. It does not add public write access, canonical mutation, ranking logic, or automatic data repair.

## 2. API Surface

The existing routes remain:

- `GET /api/v1/institutions`
- `GET /api/v1/institutions/{institution:slug}`

The existing JSON pagination shape and profile compatibility are preserved.

## 3. Search and Filter Boundary

The collection endpoint now reuses the governed directory search and filter services.

Supported query inputs are:

- `q`
- `type`
- `province`
- `district`
- `city`
- `program`
- `program_level`
- `delivery_mode`
- `admission_status`
- `per_page`

The page-size control remains bounded to 1–100 records.

This prevents the API from developing a separate, inconsistent interpretation of directory search filters.

## 4. Public Lifecycle Boundary

The profile endpoint exposes only:

- active institutions;
- active campuses;
- active campus geography;
- active programs;
- active program offerings attached to active campuses;
- admission intakes other than cancelled or suspended;
- eligibility, merit and scholarship records other than rejected or withdrawn;
- fee records other than superseded;
- accreditation records other than rejected or withdrawn.

Inactive or closed child records therefore do not become public merely because their parent institution is active.

## 5. Geography Safety

Inactive geography is not serialized as the current public campus geography.

The API does not perform geography repair, reassignment, activation, or deletion.

## 6. Compatibility

The implementation intentionally keeps the existing top-level institution JSON representation for the profile endpoint and the Laravel pagination representation for collection results.

This batch therefore improves data-access safety without requiring an API contract migration.

## 7. Testing

Regression coverage verifies:

1. inactive institutions are excluded from collection results;
2. governed institution aliases remain searchable;
3. program filtering uses the shared directory filter boundary;
4. inactive campuses are excluded;
5. campuses with inactive geography do not expose that geography;
6. inactive programs are excluded;
7. active offerings attached to inactive campuses are excluded;
8. active campus/program/offering data remains available.

## 8. Explicit Non-Goals

This batch does not implement:

- authentication for public reads;
- partner credentials;
- API keys;
- public write endpoints;
- bulk export;
- API version 2;
- ranking/recommendation algorithms;
- automatic data correction;
- new geospatial radius queries.

Those concerns remain separate governed work.

## 9. Governance

The implementation follows:

`docs/02-modules/INSTITUTES_DIRECTORY_API_AND_DATA_ACCESS_GOVERNANCE.md`

The API remains an access layer over canonical data rather than a second source of truth.
