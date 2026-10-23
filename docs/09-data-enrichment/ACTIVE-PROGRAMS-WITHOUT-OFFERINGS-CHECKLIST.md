# Active programs without active offerings: investigation checklist

**Purpose:** Reconcile active canonical programs that do not have an active program offering.
**Mode:** Read-only diagnosis. This checklist does not authorize record changes or publication.

## Scope and interpretation

A program without an active offering is a discovery/readiness gap, but it is not automatically invalid. Some programs may be intentionally inactive for admissions, may have campus-specific delivery constraints, or may require verified intake information before an offering can be published.

## Read-only review

For each active program reported without an active offering, record:

- program ID, institution ID, normalized name, slug, and current status;
- whether any offering exists in any status, including inactive, draft, rejected, or expired;
- linked campus IDs and each campus's active status and geography readiness;
- source/evidence identifiers and most recent verification date;
- whether an admission intake exists and its date/status;
- whether the program is intentionally non-admitting or awaiting current intake data;
- whether an import/enrichment row or promotion attempt already exists for the same canonical identity;
- the public profile's current visibility and the reason no active offering is displayed.

Do not include private candidate payloads, credentials, or unrestricted exception messages in a public report.

## Classification

Assign one explicit classification per program:

1. **Expected no offering** — valid program but no current admissions or no offering model appropriate.
2. **Missing verified evidence** — offering cannot be created until a trustworthy source confirms delivery/intake details.
3. **Missing or inactive campus** — resolve the parent-campus relationship through the approved review process.
4. **Existing non-active offering** — determine why the offering is not active; do not simply reactivate it.
5. **Import/promotion failure** — correlate the candidate, decision, attempt, and canonical target.
6. **Identity conflict** — require human review; do not auto-merge.
7. **Unknown** — keep blocked from automated repair or publication.

## Reconciliation rules

- Count programs, offerings, and intakes separately.
- Distinguish “no offering row” from “no active offering.”
- Distinguish a missing offering from an offering that is hidden by publication or date filters.
- Compare canonical IDs and relationships, not just names.
- Record the check timestamp, deployed revision, and query/report version.
- Produce a row-level remediation manifest with evidence, risk, expected outcome, and proposed action.
- Re-run the read-only summary after any authorized test-only reproduction to verify that the expected counts change for the intended reason.

## Safety and exit criteria

No offering should be created, activated, or published solely to reduce a gap count. Complete the audit only when every program has a classification and a traceable next action. Live changes require a separately reviewed impact plan and explicit authorization; this checklist performs no writes.
