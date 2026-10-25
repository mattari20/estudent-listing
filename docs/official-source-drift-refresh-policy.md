# Official-source drift detection and authoritative refresh policy

Status: required product behavior for the Institutes Directory discovery agent. This document defines the acceptance contract; it does not claim that every stage is already implemented or enabled in production.

## Product rule

For a verified institute, campus, programme, offering, or admissions record, the institution's verified official source is the authority for facts it publishes about itself. If a fresh, successfully retrieved official source contradicts a stored field, the agent must treat the stored value as stale and create an evidence-backed correction. The agent must not prefer the database value merely because it is already stored, older, manually entered, or previously reviewed.

After the source identity and extraction are validated, the system should apply the confirmed official value to the corresponding canonical field through the governed update path, rather than leaving an outdated value pending indefinitely. The review/report step is for traceability and exception handling, not a requirement to repeatedly re-decide whether a verified official source is more correct than stale local data.

## Required workflow

1. **Verify source identity.** Use an allow-listed/verified official domain or a previously approved source profile associated with the correct institution and record. Do not treat search snippets, third-party directories, social posts, or lookalike domains as the institution's official source.
2. **Fetch successfully.** Record canonical URL, final URL after redirects, HTTP/fetch outcome, retrieval timestamp, content fingerprint, and parser/extractor version. A timeout, access-denied response, empty response, parse failure, or unexplained redirect is not evidence that a value was removed.
3. **Extract field-level evidence.** For each relevant field, retain the extracted value, source URL, retrieval time, source section/selector or excerpt, and normalized value. Compare the normalized official value with the stored canonical value.
4. **Detect drift promptly.** Any material difference becomes a source-drift finding with entity identity, field name, old value, new official value, evidence, first/last observed timestamps, and a stable fingerprint so the same change does not create duplicate review items every run.
5. **Refresh from official evidence.** When source identity, fetch integrity, entity identity, and field extraction all pass deterministic checks, update the affected canonical field(s) from the official value through one transaction and record before/after audit data. Do not block a verified factual correction solely because it conflicts with the existing database value or because an AI confidence score is lower than an arbitrary threshold.
6. **Report exceptions.** If entity matching is ambiguous, a source is not verified, extraction conflicts within the official site, or a sensitive field cannot be confidently mapped, create a prioritized exception with evidence and keep the affected field unchanged until resolved. This is a failure of proof/identity, not a vote that the old database value is more trustworthy.
7. **Handle removals carefully.** A field missing from one page is not proof that the fact has been withdrawn. Only clear, successfully observed official removal or a verified replacement can trigger a field clear/deactivation policy; failed/empty fetches must never delete, deactivate, or overwrite canonical values.
8. **Audit and notify.** Store source snapshot/fingerprint, field-level old/new values, URL, timestamps, update result, actor/agent version, and reason. Expose a report for changes applied, exceptions awaiting resolution, and repeated source failures. Notify operators about material changes and unresolved blockers.
9. **Retry and avoid stale queues.** Retry transient fetch failures with bounded backoff. Re-check evidence immediately before applying a queued correction; supersede an older proposal when a newer official snapshot exists. Do not leave a proven correction indefinitely in a generic manual-review queue.
10. **Operate safely.** Start with tests and staging. Production updates must use the controlled deployment and authorized write path. Preserve auditability and avoid broad destructive updates when only specific fields changed.

## Source-of-truth precedence

- Verified official institutional source, successfully fetched and tied to the correct entity: authoritative for the facts it publishes.
- A second verified official source from the same institution may be used to resolve conflicting official pages; record both pieces of evidence and apply a documented precedence rule.
- Third-party listings, search snippets, AI guesses, and local database history are discovery hints only; they cannot override contradictory verified official evidence.
- If the official source is unavailable or evidence cannot be tied to the correct entity, do not infer that the stored value is correct. Record the source-health/identity blocker and retry or request resolution.

## Minimum acceptance tests

- An official name/address/programme/intake field changes while the local row remains old: the drift is detected, old/new values and evidence are recorded, and the verified new value is applied by the governed path.
- An unchanged official source creates no duplicate drift event or repeated review package.
- A failed, blocked, empty, or malformed fetch never clears or overwrites canonical data.
- A lookalike or unverified domain cannot trigger canonical updates.
- A stale queued proposal is superseded when the source changes again before promotion.
- A successful correction records before/after values and source evidence atomically with the canonical write.
- Ambiguous identity or conflicting official evidence creates a visible exception rather than updating the wrong institute.
- Repeated source changes are processed on the next scheduled check and do not remain indefinitely pending solely for re-approval of source authority.

## Implementation boundary

This is the target behavior for the agent. It must be wired into source monitoring, field-level diffing, review/promotion policy, transactional canonical updates, and operator reporting. Documentation alone does not prove that source monitoring is scheduled, that automatic corrections are enabled, or that the live Hostinger deployment contains the implementation. Keep those as separate acceptance gates until CI and live evidence confirm them.
