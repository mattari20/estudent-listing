# Institutes Directory — Deep Technical and Architecture Audit

**Audit date:** 2026-10-31 (PKT)  
**Repository:** \`mattari20/estudent-listing\`  
**Audited branch:** \`main\`  
**Exact audited main SHA:** \`b878416826bdf62c26ad27d040f254461d3cf126\`  
**Step 1 reference:** \`docs/audits/PROJECT_BASELINE_AND_INVENTORY.md\` on \`audit/step1-project-baseline-2026-10-31\` (PR #954 remains open)  
**Audit type:** Repository-source and available CI/PR evidence review; no local checkout or production access  
**Step status:** Step 2 documentation audit only; Step 3 NOT STARTED

> This report separates source-code observations, tests present in the repository, CI results attached to exact PR heads, historical production reports, and current production facts. No production state is inferred from migration files or from old diagnostics.

## 1. Verified audit revision and starting point

### 1.1 Main and PR #954

GitHub repository metadata reports default branch \`main\`. The latest commit returned by the repository's descending commit search is:

\`\`\`text
b878416826bdf62c26ad27d040f254461d3cf126
\`\`\`

The commit is the merge of PR #952, titled \`fix: isolate Phase 5B.18 runtime diagnostic cleanup\`.

PR #954 remains **OPEN**, not merged:
- Head branch: \`audit/step1-project-baseline-2026-10-31\`
- Head SHA: \`c23c52807cf881a17206721315789a7db98096e5\`
- Base branch: \`main\`
- Base SHA at inspection: \`b878416826bdf62c26ad27d040f254461d3cf126\`
- Mergeability: GitHub reports mergeable
- Changed files: only \`docs/audits/PROJECT_BASELINE_AND_INVENTORY.md\`

The Step 1 report is therefore available as branch reference material, not as a document already present on main.

### 1.2 Open PRs and inclusion status

| PR | State | Head SHA | Base SHA at inspection | Technical disposition |
|---|---|---|---|---|
| #954 Step 1 inventory | OPEN | \`c23c52807cf881a17206721315789a7db98096e5\` | \`b878416826bdf62c26ad27d040f254461d3cf126\` | Documentation-only baseline; not included in main |
| #953 Phase 5B.29 runtime evidence repair | OPEN | \`f0031ae38c7e3650ef2c449316450be666c465f1\` | \`b878416826bdf62c26ad27d040f254461d3cf126\` | Fix is not included in main; exact workflow change reviewed below |
| #913 Hostinger diagnostic blocker documentation | OPEN | \`20325bc49c6166ae8de529846230465770967475\` | \`1ce7830881adc82b055d4e388fffc10c178c69a8\` | Documentation-only; stale base, not included in main |
| #911 Hostinger parity fetch isolation | OPEN | \`28f4a14a1d441436512ea3e3a32402588b9bbbc8\` | \`c5838e3d87574ca9761f07e0ca9755727f9aa4d7\` | Its exact patch is not present verbatim; main contains a later, different temporary-repository parity implementation |
| #899 empty-directory diagnostic documentation | OPEN | \`40e6c67aec8c975dc538d47d3889ab47669726f3\` | \`a54f728f31ce8ca92854d2adf4ee777150e0a710\` | Documentation-only; stale base, not included in main |

No PR was merged, closed, modified or rebased during this audit.

### 1.3 CI evidence available

The GitHub connector's combined status query returned an empty status list for exact main SHA \`b8784168...\`; its commit-workflow-run wrapper returned no PR-triggered runs for that SHA. This does **not** prove that main has no CI runs; the wrapper only returns PR-triggered runs and is not a repository-wide Actions history API.

Exact PR-head CI evidence returned:

| PR head | Workflow run | Result |
|---|---|---|
| #954 \`c23c5280...\` | [Laravel CI #41180827573](https://github.com/mattari20/estudent-listing/actions/runs/41180827573) | completed / success |
| #954 \`c23c5280...\` | [Test #41180827589](https://github.com/mattari20/estudent-listing/actions/runs/41180827589) | completed / success |
| #953 \`f0031ae3...\` | [Laravel CI #41179406552](https://github.com/mattari20/estudent-listing/actions/runs/41179406552) | completed / success |
| #953 \`f0031ae3...\` | [Test #41179406503](https://github.com/mattari20/estudent-listing/actions/runs/41179406503) | completed / success |
| #911 \`28f4a14a...\` | [Laravel CI #40836582833](https://github.com/mattari20/estudent-listing/actions/runs/40836582833) | completed / success |
| #911 \`28f4a14a...\` | [Test #40836582666](https://github.com/mattari20/estudent-listing/actions/runs/40836582666) | completed / success |

These results apply to the listed PR head SHAs, not to current main or to live production. No test suite was run by this audit.

### 1.4 Audit limitations

- GitHub remote APIs were used; local current branch, local working-tree changes and untracked files are not available.
- The connected GitHub tools did not expose a repository-wide recursive tree listing. File-by-file inspection is broad but not an exhaustive proof that every file/job/event/test has been reviewed.
- Workflow-run enumeration is limited to PR runs attached to a supplied commit; the current main push/schedule/manual run ledger is not available through that action.
- No Hostinger SSH, hPanel, production database or private diagnostic report was accessed.
- No fresh public HTTP request was made in this Step 2 audit.

## 2. Executive summary

The application is a Laravel 13 / PHP 8.5 monolith with Blade-rendered public pages, JSON API routes, Eloquent domain models, service-layer import/discovery workflows, and a large operator/admin surface. The existing architecture is viable and should be completed through focused repairs rather than replaced.

The strongest parts of the implementation are:
- Explicit Laravel route and middleware separation for public, operator and admin functionality.
- A meaningful domain model for institutions, campuses, programmes, offerings, intakes, geography, source evidence and staged imports.
- Deterministic extraction and a separate opt-in AI provider for programme discovery.
- Source URL/DNS/redirect protections in the official-source retrieval path.
- Evidence-bound staging, source snapshots, identity duplicate detection and explicit human identity decisions.
- A separate publication step for newly promoted institutions.
- Persisted field-level approval for Institution description refresh, including evidence binding, stale-value checks, reviewer separation and audit events.
- Targeted tests for empty API responses, public API filters, nearby search, source URL safety, discovery idempotency and field approval.

The audit also identifies material gaps:

1. **HIGH — first-admin setup gate omits \`master_admin\`.** The public setup route only checks for existing \`operator\` or \`admin\` users. The user model explicitly recognizes \`master_admin\` as an operator/admin, but setup does not count it. If a database contains only a master-admin account, an unauthenticated visitor can still reach setup and create an additional admin.
2. **HIGH — stored script-context XSS risk in institution profile JSON-LD.** The profile view inserts \`json_encode()\` output through raw Blade output inside a script element without \`JSON_HEX_TAG\`/equivalent safe encoding. Institution-controlled strings can contain a script-closing sequence.
3. **MEDIUM — public academic status filtering is inconsistent.** The HTML profile and API profile use different admission-intake status exclusions. Accreditation and related child records use blacklist exclusions that can include draft/candidate states. Search filter catalog can expose non-public admission statuses.
4. **MEDIUM — evidence capture accepts caller-selected assertion verification states and subject references.** The operator evidence endpoint validates basic shapes but does not constrain verification-state values or bind every subject reference to an allowed owned record. The evidence service persists those values without additional authorization/authority checks.
5. **MEDIUM — CSV review package has formula-injection exposure.** Candidate-derived fields are written to a downloadable CSV with \`fputcsv()\` but no spreadsheet-formula neutralization.
6. **MEDIUM — PHPUnit configuration excludes the Unit test directory.** \`phpunit.xml\` declares only \`tests/Feature\`; \`composer test\` therefore does not run \`tests/Unit\` unless invoked separately.
7. **MEDIUM — scheduled discovery has two execution planes.** GitHub scheduled workflow and Laravel scheduler can both invoke source retrieval/staging. GitHub's concurrency group does not coordinate with Hostinger's scheduler lock. The GitHub workflow also fetches/unshallows Git history inside the live Hostinger checkout, mutating shared \`.git\` metadata.
8. **MEDIUM — migration reconciliation checks differ in strength.** The official-source refresh migration's existing-table path checks required column names and indexes, not full column type/nullability equivalence, despite its schema-equivalence wording. The duplicate_rows migration itself only checks column existence; its separate controlled workflow is the stronger gate.
9. **MEDIUM — public search and nearby search have scalability limits.** Substring \`LIKE '%term%'\` queries span several related tables; nearby search loads the entire bounding-box campus result into PHP before distance filtering and result limiting.
10. **MEDIUM — the opt-in AI path does not verify that model-supplied evidence excerpts occur in the source snapshot.** The prompt requests exact excerpts, but the service only requires a non-empty excerpt for AI candidates.
11. **Production acceptance remains unverified.** Source code and exact PR-head CI do not prove current Hostinger parity, live schema, scheduler execution or live public behavior.

The minimum completion approach is to fix the identified security and publication-state defects, strengthen tests and schema contracts, and reconcile scheduler ownership. No rewrite is justified by the evidence reviewed.

## 3. Application architecture map

### 3.1 Framework and bootstrap

Evidence:
- \`composer.json\`: PHP \`^8.5\`, Laravel \`^13.0\`, PHPUnit \`^12.5\`, Pint \`^1.24\`.
- \`bootstrap/app.php\`: registers web routes, API routes, console routes, health endpoint \`/up\`, command directory and middleware aliases.
- API route prefix is configured as \`institutes/api\`.
- Middleware aliases: \`institute.operator\` and \`institute.admin\`.
- Guest redirect target: \`institutes.login\`.
- Laravel scheduler registers \`institutes:bootstrap-agent\` and \`institutes:monitor-changes\` hourly, each with \`withoutOverlapping(55)\`.

The app is a Laravel subfolder application under \`/institutes\`; it is not a WordPress plugin.

### 3.2 Architecture flow

\`\`\`mermaid
flowchart TD
    Browser[Public browser] --> Web[Blade web routes]
    Client[API client] --> API[Versioned JSON API]
    Web --> Controllers[Public controllers]
    API --> ApiControllers[API controllers]
    Controllers --> Search[Search / nearby / SEO services]
    ApiControllers --> Search
    Controllers --> Eloquent[Eloquent domain models]
    ApiControllers --> Eloquent

    Operator[Authenticated operator/admin] --> Ops[Operations routes]
    Ops --> Import[CSV / enrichment import]
    Ops --> Evidence[Evidence capture and review]
    Ops --> Identity[Identity resolution and decisions]
    Ops --> Promotion[Canonical promotion]
    Ops --> Publication[Separate publication step]
    Ops --> Approval[Field approval / canonical refresh]
    Ops --> Monitoring[Monitoring dashboard and alerts]

    Scheduler[Laravel scheduler] --> Bootstrap[BootstrapInstituteAgent]
    Scheduler --> Monitor[MonitorSourceChanges]
    Actions[GitHub scheduled workflow] --> Bootstrap
    Actions --> Monitor

    Bootstrap --> SafeHTTP[SafeSourceHttpRetriever + SourceUrlGuard]
    Monitor --> Pipeline[SourceMonitoringBatchService]
    SafeHTTP --> Snapshot[Source snapshots]
    Snapshot --> Extract[Deterministic extractor / optional AI programme extractor]
    Extract --> Staging[ImportBatch / ImportRow]
    Pipeline --> Snapshot
    Staging --> Evidence
    Evidence --> Review[Private review package]
    Review --> Identity
    Identity --> Promotion
    Promotion --> Publication
    Approval --> CanonicalRefresh[Evidence-bound canonical field refresh]
\`\`\`

### 3.3 Architectural style and coupling

**Assessment: a modular monolith with a service layer, not a separate microservice architecture.**

- Controllers are generally thin-to-moderate orchestration layers, but some controllers (notably \`InstituteImportPromotionController\`) contain many state transitions and should be decomposed only if the existing service boundary cannot remain clear.
- Business logic is distributed across dedicated services for source retrieval, source monitoring, discovery, identity resolution, promotion, approval and readiness.
- Eloquent models expose relationships but generally do not own complex business behavior.
- The public API directly serializes Eloquent models instead of using explicit API Resource/DTO allowlists.
- Public HTML and API detail controllers independently maintain large nested eager-load and status-filter definitions. Their current filters already differ, creating drift risk.
- There are many specialized readiness controllers and workflow contracts. This is operationally complex but not, by itself, evidence that the application needs a rewrite.
- Some files use compressed one-line style while newer files use conventional PSR-12 style. This harms maintainability but is not a runtime defect.
- Some imports and helper paths appear redundant in inspected files; no broad dead-code claim is made without repository-wide reference analysis.

## 4. Public directory audit

### 4.1 Listing page

**Status: PARTIALLY COMPLETE; implementation and empty-state behavior are present.**

Files:
- \`routes/web.php\`
- \`app/Http/Controllers/InstitutionController.php\`
- \`resources/views/institutes/index.blade.php\`
- \`resources/views/layouts/app.blade.php\`
- \`tests/Feature/InstitutesDirectoryTest.php\`

Implementation:
- Queries only institutions with \`status = active\`.
- Sorts by name and paginates.
- Supports page sizes 9, 12, 24 and 48; unsupported values fall back to 24.
- Preserves query strings in pagination.
- Uses \`@forelse\` and renders an explicit empty state when no active institutions exist.
- Shows a result count and page-size selector.

Tests:
- Route returns HTTP 200.
- Active institution pagination and inactive exclusion.
- Page-size allowlist and query preservation.

Gaps:
- The inspected tests do not explicitly assert the exact empty-state copy or all zero-row paginator metadata for the HTML page.
- Sorting uses only name; equal-name rows do not have a deterministic secondary key. Add \`id\` as a tie-breaker.
- No current live production check was run.

Acceptance:
- Empty canonical dataset returns HTTP 200, visible empty state, zero count and no broken links.
- Populated page sizes and pagination links remain stable with duplicate institution names.
- Candidate/inactive/closed institutions never appear in public listing.

### 4.2 Institution detail page

**Status: PARTIALLY COMPLETE; high-priority security and publication-state tests are missing.**

Files:
- \`routes/web.php\`
- \`app/Http/Controllers/InstitutionController.php\`
- \`resources/views/institutes/show.blade.php\`
- \`app/Services/InstituteSeoService.php\`

Implementation:
- Route-model binding uses institution slug.
- Controller returns 404 unless institution status is active.
- Loads active campuses and active geography.
- Loads active programmes and active offerings.
- Loads admissions, eligibility, merit, scholarships, fees and accreditations through nested relations.
- Produces EducationalOrganization and BreadcrumbList JSON-LD.
- View has campus/programme/recognition sections and per-section empty states.
- Meta title, description, canonical and Open Graph fields are generated.

Defects:
- **SEC-02:** raw JSON-LD is inserted into a script element without HTML-sensitive JSON escaping. See security section.
- Admission and accreditation child-state filters use inconsistent blacklists rather than one explicit public-state policy.
- The HTML detail page loads active offerings without constraining their campus to active status/geography. The API constrains the campus to active status but does not require active geography for an offering's campus, even though its top-level campus collection does. This can make the same profile show different offerings/campuses between HTML and JSON.
- Institution HTML and API detail filters differ; the API excludes fewer admission statuses.
- No dedicated detail-page behavior test was found in the specifically inspected test paths. The repository tree could not be enumerated, so this is not a claim that no other test exists.

Acceptance:
- Candidate/draft/rejected/withdrawn child records do not appear publicly.
- Offerings must be attached to an eligible campus belonging to the same institution.
- XSS payloads in name, official name, description and nested labels render as inert text.
- Empty detail sections render informative empty states.
- Slug lookup returns 404 for non-active institution.

### 4.3 Search and filters

**Status: PARTIALLY COMPLETE.**

Files:
- \`routes/web.php\`
- \`app/Http/Controllers/InstituteSearchController.php\`
- \`app/Services/InstituteSearchQueryService.php\`
- \`app/Services/InstituteSearchFilterService.php\`
- \`app/Services/InstituteSearchCatalogService.php\`
- \`resources/views/institutes/search.blade.php\`

Implementation:
- Search across institution name, official name, short name, aliases, active programme names/discipline/level, active campus names and active geography names.
- Filters for institution type, province, district, city, programme, programme level, delivery mode and admission status.
- Paginated results and clear-filter control.
- Filter catalog cached for ten minutes.
- Filtered search pages use \`noindex,follow\`.

Defects and risks:
- Query and filter strings are trimmed but no explicit maximum length is applied in the inspected controller.
- Substring LIKE patterns use leading and trailing wildcards and span related tables; ordinary B-tree indexes will not make every such search efficient at scale.
- Search catalog exposes admission status values except cancelled/suspended; it can include draft/candidate/rejected/withdrawn statuses.
- The filter service accepts a requested admission status and only excludes cancelled/suspended before exact matching. A public request can therefore search by a non-public status.
- No inspected search tests cover every filter combination, maliciously long inputs, empty results, status visibility or stable pagination.

Acceptance:
- Define a single allowlist of public admission/academic states and reuse it in HTML, API and filter catalogs.
- Apply the same active-geography predicate to API offerings as to the top-level campus collection; apply campus/geography eligibility to HTML offerings too.
- Bound all query/filter lengths and test 422/normalization behavior.
- Add performance tests or query plans against representative institution/campus/program counts.
- Preserve query parameters through pagination and ensure deterministic ordering.

### 4.4 Sitemap, robots and SEO

**Status: PARTIALLY COMPLETE.**

Files:
- \`app/Http/Controllers/InstituteSitemapController.php\`
- \`app/Http/Controllers/InstituteRobotsController.php\`
- \`app/Services/InstituteSeoService.php\`
- \`resources/views/institutes/robots.blade.php\` (view path referenced by controller; not separately fetched)
- \`resources/views/institutes/sitemap.blade.php\` (view path referenced by controller; not separately fetched)

Implementation:
- Sitemap includes active institutions only and emits last-modified timestamps.
- XML response uses correct content type, public cache header and nosniff.
- Robots response uses text/plain and nosniff.
- Profile SEO schema describes EducationalOrganization and campus locations.
- Filtered search pages are noindex.

Gaps:
- Sitemap loads all active institutions into memory; no sitemap chunking/index is visible.
- The exact robots/sitemap view templates were not inspected in this pass.
- No live crawler/indexing verification.

Acceptance:
- Validate XML escaping and URL canonicalization.
- Split sitemap at protocol size/count thresholds when coverage grows.
- Verify robots rules do not accidentally block canonical listing/detail pages.
- Validate structured data with malicious and non-ASCII strings.

## 5. API audit

**Status: PARTIALLY COMPLETE; core routes and useful tests exist, but response allowlisting and status parity need work.**

Files:
- \`routes/api.php\`
- \`bootstrap/app.php\`
- \`app/Http/Controllers/Api/InstitutionApiController.php\`
- \`app/Services/InstituteSearchQueryService.php\`
- \`app/Services/InstituteSearchFilterService.php\`
- \`app/Services/InstituteNearbySearchService.php\`
- \`tests/Feature/InstitutionApiTest.php\`
- \`tests/Feature/InstituteNearbySearchTest.php\`
- \`tests/Feature/ApiRankingRecommendationTest.php\`

### 5.1 Listing API

- \`GET /institutes/api/v1/institutions\`
- Reuses the search query/filter services.
- Supports q, type, province, district, city, program, program_level, delivery_mode and admission_status.
- Per-page value is clamped to 1–100.
- Uses Laravel paginator JSON.

The empty-dataset test explicitly verifies:
- HTTP 200.
- \`application/json\`.
- \`data = []\`.
- \`total = 0\`.
- \`current_page = 1\`.
- \`last_page = 1\`.

This is a good regression test for the earlier malformed/empty JSON concern.

### 5.2 Detail API

- \`GET /institutes/api/v1/institutions/{slug}\`
- Returns 404 for inactive/candidate institution.
- Eager-loads campuses, programmes, offerings, admissions and related academic records.

Defects:
- API detail admission filter excludes only cancelled/suspended, while HTML detail also excludes candidate/draft/rejected/withdrawn.
- Accreditation, eligibility, merit and scholarship filters exclude only selected terminal states, potentially leaving draft/candidate states in public JSON.
- Controller returns raw Eloquent models and nested relations instead of explicit API Resources. This makes the public response contract dependent on fillable/hidden/cast/relationship changes in models and can expose newly added columns unintentionally.
- No versioned response DTO/schema is visible in the inspected controller.

Acceptance:
- Centralize public-state predicates.
- Add tests that seed every internal state and assert only approved public states appear.
- Add explicit response Resources with field allowlists and stable API contract tests.

### 5.3 Nearby API

- \`GET /institutes/api/v1/institutions/nearby\`
- Validates latitude/longitude, radius and result limit.
- Radius max 100 km; result limit max 100.
- Filters active campuses, active institutions, active geography and non-null coordinates.
- Uses bounding-box SQL filtering, Haversine distance calculation, nearest-first sort and institution de-duplication.

Tests cover distance ordering, inactive entities, invalid/missing coordinates, radius limits and multiple campuses.

Performance concern:
- The service calls \`get()\` on every campus in the bounding box, then calculates distances and applies the result limit in PHP. No SQL limit or spatial index query is visible in this service.
- As the directory grows nationwide, a large bounding box can load many rows into memory per request.

Acceptance:
- Benchmark with realistic national campus counts.
- Add geospatial/spatial-index strategy or database-side distance filtering if query volume warrants it.
- Preserve exact distance ordering and closest-campus deduplication.

## 6. Academic data model audit

**Status: PARTIALLY COMPLETE — core relationships exist; complete migration-level constraints are not verified.**

### 6.1 Logical entity map

| Entity | Core relationship | Observed fields / role |
|---|---|---|
| Institution | Parent of campuses and programmes | name, official_name, short_name, slug, institution_type, status, description, verified_at |
| InstitutionName | Belongs to Institution | official names, aliases/acronyms, current flag |
| Campus | Belongs to Institution and Geography | name, slug, campus_type, status, address, postal code, coordinates |
| Geography | Self-referential parent/children | country/province/district/city hierarchy via parent_id and type |
| Program | Belongs to Institution | name, slug, level, award, discipline, status, description |
| ProgramOffering | Belongs to Program and Campus | delivery mode, status, intake label, duration, availability, effective dates |
| AdmissionIntake | Belongs to ProgramOffering | dates, status, seats, application URL, notes |
| EligibilityRule | Belongs to AdmissionIntake | eligibility requirements |
| MeritRecord | Belongs to AdmissionIntake | merit/closing data |
| SelectionOutcome | Belongs to AdmissionIntake | selection outcomes |
| FeeRecord | Belongs to offering/intake according to relationships and enrichment paths | fee type, amount/range, currency, frequency |
| Scholarship | Belongs to offering/intake according to relationships and enrichment paths | award/benefit information |
| Accreditation | Optional institution/program/offering/campus references | recognition type, authority, scope, validity |
| ExternalRanking | Institution or programme ranking | publisher, edition, scope, year, rank, score, status |
| Recommendation | Institution/campus/program recommendation | contributor, rating, verification state, status, comment |
| Source | Parent of snapshots/evidence/policies | source type, name, URL, authority scope, status |
| SourceSnapshot | Belongs to Source | raw content, hash, content type/length, capture timestamp, metadata |
| Evidence | Belongs to Source and optional snapshot | reference, excerpt, content hash, observed/published/expiry times, status |
| Assertion | Belongs to Evidence | subject type/id, attribute, value, verification state, confidence |
| ImportBatch | Belongs to Source; parent of rows | entity/source type, batch key, counts, status and timestamps |
| ImportRow | Belongs to batch | payloads, normalized payload, validation errors, match state, parent/canonical subject |
| IdentityMatchCandidate | Links import row to candidate canonical institution | match type, confidence, status, reason |
| IdentityResolutionDecision | Links row to explicit operator decision | approved_new / approved_match / rejected / conflict |
| InstitutionFieldApproval | Institution description approval record | evidence-bound proposal, reviewer, decision, application outcome |
| OfficialSourceRefreshEvent | Polymorphic canonical field history | before/after, source observation, evidence and fingerprint |

The list above is based on inspected models, controllers and service references. It is not a full database table inventory.

### 6.2 Parent-child integrity

Verified service-level controls:
- Campus and programme enrichment requires an Institution parent.
- Program offering enrichment requires a Program parent.
- Admission intake requires a ProgramOffering parent.
- Eligibility, merit and selection records require an AdmissionIntake parent.
- Fee/scholarship require an offering or intake parent.
- Structured offering promotion checks that the selected campus belongs to the same institution as the parent programme.
- Campus promotion requires an active geography when a geography ID is supplied.
- Top-level institution discovery can stage a parentless identity candidate; it does not create a canonical institution.

Remaining risk:
- \`ProgramOffering\` has independent \`program_id\` and \`campus_id\` foreign relationships; the cross-institution match is enforced in \`StructuredEnrichmentService\`, not proven as a database composite constraint.
- Direct database writes or alternate services can bypass service-level validation.
- Complete foreign-key, unique-index and nullability definitions for all academic tables were not enumerated in this audit.

Acceptance:
- Add database-level or centralized service invariants for programme/campus institution consistency.
- Add negative tests for cross-institution offerings, invalid parent types and parent deletion.
- Confirm slug uniqueness at database level for institutions, campuses, programmes and geography.
- Define explicit status enums/constants and allowed transitions.

### 6.3 Publication state consistency

Observed good controls:
- Public listing filters active institutions.
- Public detail returns 404 for non-active institution.
- Public campus and programme queries require active status.
- Public offerings require active status.
- Recommendations are filtered to published in the inspected detail controller.

Inconsistent controls:
- Admission intake status filters differ between web and API.
- Accreditation filters exclude rejected/withdrawn but do not explicitly require a public status.
- Search catalog can list draft/candidate/rejected/withdrawn admission statuses.
- Related records are often filtered with \`whereNotIn\` rather than a shared explicit allowlist.

This is a publication-governance defect, not just a presentation preference. Internal states must not be exposed through public pages or APIs.

### 6.4 Deletion and archival

The inspected core models use status fields; no \`SoftDeletes\` trait was present in the specific Institution, Campus, Program, ProgramOffering and Geography model files reviewed. Some approval migration foreign keys use \`restrictOnDelete()\`. Full deletion behavior for all academic tables cannot be certified without the full migration tree.

## 7. Import, evidence and review architecture

**Status: PARTIALLY COMPLETE; the governed pipeline is real, but manual evidence trust and import transaction boundaries need tightening.**

### 7.1 Automated discovery sequence

The inspected code path is:

1. \`BootstrapInstituteAgent\` loads configured profiles.
2. Profile monitoring configuration determines enabled/disabled behavior.
3. \`Source\` is registered/upserted.
4. \`DiscoveryMonitoringPolicyService\` creates or updates the profile policy.
5. Source retrieval goes through \`SafeSourceHttpRetriever\` and \`SourceUrlGuard\`.
6. Successful body is hashed and stored as a \`SourceSnapshot\`.
7. Deterministic extraction runs for institution/campus/program; optional AI extraction is only available for programme profiles.
8. Evidence and assertion records are created.
9. \`ImportBatch\` and \`ImportRow\` candidate rows are persisted.
10. Candidate quality blockers and duplicate detection classify rows.
11. Identity resolution creates candidate matches/conflicts.
12. \`InstituteDiscoveryReviewPackageService\` creates a private CSV and manifest with SHA-256 checksum.
13. Human operator reviews candidate and evidence.
14. Explicit identity/promotion decision is recorded.
15. Promotion creates or links canonical data.
16. Publication is a separate action for a new institution.

The institution identity service explicitly reports \`canonical_mutations_performed=false\` and \`automatic_approval_performed=false\`.

### 7.2 Transaction boundaries

| Operation | Transaction / locking observed | Remaining risk |
|---|---|---|
| Generic discovery candidate persistence | DB transaction and source row \`lockForUpdate()\` around candidate persistence | Source retrieval/snapshot capture occurs before the staging transaction; review-package file writes are not part of a transactional filesystem |
| Top-level institution identity staging | DB transaction and source row lock | File package writes can remain if later DB/storage operations fail |
| Manual institution CSV import | Chunked row inserts; no single transaction around the entire import lifecycle | Batch can be partially processed if a later chunk, resolver or evidence capture fails |
| Evidence capture service | No transaction inside service itself | Callers sometimes wrap it; direct controller path can leave partial evidence/assertion state on failure |
| Institution promotion | DB transaction; inspected promoter does not lock the ImportRow or decision row explicitly | Concurrent requests require a dedicated idempotency/locking regression |
| Institution field approval | DB transaction; proposal and institution rows locked | Stronger boundary; retain |
| Canonical description refresh | DB transaction; approval and institution locked; evidence revalidated | Strongest inspected workflow; retain |

### 7.3 Manual import and evidence trust

Files:
- \`app/Http/Controllers/InstituteImportController.php\`
- \`app/Services/InstituteImportControllerSupport.php\`
- \`app/Services/InstituteSourceEvidenceCaptureService.php\`
- \`app/Http/Controllers/InstituteSourceEvidenceCaptureController.php\`
- \`app/Services/InstituteImportPromoter.php\`

Manual CSV import:
- Requires source name/type, institution entity type, schema version and CSV file.
- CSV max size is 5 MiB; mimes are CSV/TXT.
- Header validation and row normalization are performed.
- Duplicate slugs within the same file are treated as row validation errors.
- Rows are inserted in chunks of up to 500.
- Candidate identity matching runs after row insertion.
- Optional official_source_url + source_excerpt values are captured as operator-supplied evidence.
- This process does not automatically approve, promote or publish.

Finding:
- \`InstituteSourceEvidenceCaptureService\` verifies that the Source is active and that an optional snapshot belongs to that source, but does not itself verify the reference URL's official-source identity or enforce an allowlist of assertion verification states.
- \`InstituteSourceEvidenceCaptureController\` accepts caller-supplied \`verification_state\`, \`subject_type\`, \`subject_id\`, \`attribute\` and \`value\` after basic shape validation. The service persists these values directly.
- The Institution field approval path later performs much stronger source identity and evidence validation, but the general evidence endpoint is broader.
- Manual import's official URL check is HTTPS/URL syntax only; it is not proof that the host belongs to the claimed institution or authority.

This should be tightened so operator-entered observations cannot be labelled verified/confirmed without a separately authorized verification action and valid subject ownership.

### 7.4 Duplicate and identity resolution

Verified:
- Canonical and previous staged duplicate checks exist.
- Institution identity duplicate detection compares normalized official names where both are present, and uses slug/name fallbacks conservatively.
- PR #929 added source-row locking for top-level institution identity staging.
- PR #930 added changed-snapshot/conflicting-name regression coverage.
- Identity decisions are explicit; new institution promotion refuses unresolved identity candidates.
- Institution candidates cannot use quick approval.
- New institution promotion checks canonical names, official names, short names, aliases and campus-like identity signals.
- A new canonical institution is created with status \`candidate\`, not active.
- Publication is a separate operation requiring active evidence and no open import conflicts.

Remaining:
- No current live queue or candidate row state was inspected.
- No all-batch identity reconciliation was run.
- Concurrency/idempotency coverage for all promotion endpoints remains unverified.

### 7.5 Review package

Files:
- \`app/Services/InstituteDiscoveryReviewPackageService.php\`
- \`app/Http/Controllers/InstituteDiscoveryReviewPackageController.php\`

Good controls:
- Only automated_discovery/ai_extraction batches are accepted.
- Packages are private on the local storage disk.
- Manifest binds batch ID, snapshot ID and checksum.
- Download validates the manifest/checksum and uses no-store/private cache headers.
- Route is under authenticated operator middleware.

Finding:
- Candidate data is written to CSV without neutralizing spreadsheet formula prefixes. A source-controlled value beginning with \`=\`, \`+\`, \`-\` or \`@\` can be interpreted as a formula by spreadsheet software.

Acceptance:
- Escape spreadsheet formula-leading values and test every exported field.
- Add checksum/tamper tests and private-cache tests.
- Test partial filesystem failure and cleanup/recovery behavior.

## 8. Candidate approval, promotion and canonical refresh

**Status: PARTIALLY COMPLETE; core governance gates are present and comparatively strong.**

### 8.1 Identity promotion

Files:
- \`app/Http/Controllers/InstituteImportPromotionController.php\`
- \`app/Services/InstituteImportPromoter.php\`
- \`app/Services/InstituteIdentityResolver.php\`
- \`app/Services/ImportPromotionAuditService.php\`

Observed sequence:
- Only candidate rows can be promoted.
- Batch must be validated.
- Source must be active.
- Active evidence linked through assertions is required.
- Explicit approved_new or approved_match identity decision is required.
- Open import conflicts block promotion.
- Existing canonical match is linked, not overwritten.
- New institution is created as candidate.
- Audit event records promotion outcome.
- Separate publish action changes institution to active after evidence and conflict checks.

Risk:
- \`publishInstitution()\` is under the operator route group rather than the admin-only subgroup. The code does require a prior identity decision, active evidence and no open conflicts. Whether operator-level publication is intended governance policy is not documented by the inspected route/controller alone; confirm the required role before release.
- Promoter wraps writes in a transaction but does not explicitly lock the ImportRow/decision in the inspected implementation. Add concurrent duplicate-submit tests and row-level lock/idempotency if they are not already guaranteed by constraints.

### 8.2 Institution field approval

Files:
- \`app/Models/InstitutionFieldApproval.php\`
- \`app/Services/InstitutionFieldApprovalService.php\`
- \`app/Services/OfficialSourceCanonicalRefreshService.php\`
- \`app/Http/Controllers/InstitutionFieldApprovalController.php\`
- \`database/migrations/2026_10_29_000001_create_institution_field_approvals_table.php\`

Good controls:
- Only operator can create pending description proposal.
- Only admin can decide.
- Creator cannot review own proposal.
- Rejection requires a reason.
- Proposal hash binds exact proposal attributes.
- Source, observation, snapshot and evidence relationships are revalidated.
- Latest snapshot must still be current.
- Snapshot and evidence hashes must match.
- Evidence URL host must match the verified source host and pass SourceUrlGuard.
- Conflicting verified assertions block approval.
- Expected canonical value is checked before approval and again before apply.
- Institution writes are limited to description through persisted approved proposal ID.
- Generic Institution evidence-array refresh path fails closed.
- Refresh event records before/after, source, observation, evidence and fingerprint.

This is a strong pattern and should be reused rather than replaced.

## 9. Admin and operator audit

**Status: PARTIALLY COMPLETE; a first-admin bootstrap defect is HIGH severity.**

Files:
- \`routes/web.php\`
- \`app/Http/Middleware/EnsureInstituteOperator.php\`
- \`app/Http/Middleware/EnsureInstituteAdmin.php\`
- \`app/Models/User.php\`
- \`app/Http/Controllers/InstituteSetupController.php\`
- \`app/Http/Controllers/InstituteAuthController.php\`
- \`app/Http/Controllers/InstitutePasswordResetController.php\`

### 9.1 Roles and route groups

- \`User::isOperator()\` returns true for operator, admin and master_admin.
- \`User::isAdmin()\` returns true for admin and master_admin.
- Operator middleware redirects unauthorized users to login and forces temporary-password change.
- Admin middleware protects account management and field-approval decisions.
- Operations routes are under auth + operator.
- User-management routes and field-approval decision/apply endpoints add admin middleware, except apply is operator-scoped with service-level approved-state checks.
- Login attempts are throttled at five attempts per 60 seconds per email/IP key.
- Successful login regenerates the session.

### 9.2 HIGH finding — setup gate ignores master_admin

In \`InstituteSetupController::create()\` and \`store()\`, the existence check is:

\`\`\`php
User::whereIn('role', ['operator', 'admin'])->exists()
\`\`\`

But \`User::isOperator()\` and \`isAdmin()\` explicitly include \`master_admin\`.

If the users table contains a \`master_admin\` account but no user whose role is exactly \`operator\` or \`admin\`, the public \`/institutes/setup\` route remains available. An unauthenticated visitor can submit the form and create an \`admin\` account.

Severity: **HIGH**.

Required fix:
- Treat any existing privileged role, including master_admin, as a completed bootstrap.
- Prefer a dedicated one-time setup state/lock or a robust \`User::query()->exists()\` policy if no users of any role should exist before bootstrap.
- Add tests for database states: empty users, operator only, admin only, master_admin only, mixed roles, concurrent setup submissions.
- Confirm setup is disabled after the first privileged account exists.

### 9.3 Password recovery

- Forgot-password POST returns a message that email recovery is not configured.
- Reset form/action exists and uses Laravel Password broker.
- No email issuance occurs in the inspected sendLink action.
- Therefore self-service recovery is NOT IMPLEMENTED end-to-end; admin-assisted reset is the actual recovery path.
- Do not represent the forgot-password form as a functioning email workflow.

### 9.4 Evidence and publication permissions

- Evidence capture endpoint is operator-protected, but its verification-state and subject-reference validation is too permissive (SEC-03).
- Publication is operator-scoped after an explicit identity decision. Product owner should confirm whether final publication requires admin.
- No production authorization test or penetration test was performed.

## 10. Database architecture and migration audit

**Status: PARTIALLY COMPLETE at source level; production schema UNVERIFIED.**

### 10.1 Specific migrations inspected

#### \`2026_10_23_000001_add_duplicate_rows_to_import_batches_table.php\`

- Adds \`duplicate_rows\` as unsigned integer, default 0, after \`candidate_rows\`, only if the column does not exist.
- Down migration drops the column if present.
- The migration itself checks column existence only; it does not verify existing type, nullability, default or column position before treating the column as present.
- PR #894 added a separate controlled workflow guard that refuses partial/ambiguous column-present/ledger-absent state and adds read-only column diagnostics.
- Historical PR #887 reports a production state where the column was missing and migration ledger entry absent; later PR #895 reports the physical column present but ledger absent. These are historical production observations, not current schema proof.

Finding DB-01: the guarded workflow is stronger than the migration's own idempotency check. Ensure ordinary deployment/migration paths cannot bypass the controlled preflight for this known partial state.

#### \`2026_10_25_000002_create_official_source_refresh_events_table.php\`

- Table: \`official_source_refresh_events\`.
- Required columns include source/observation IDs, polymorphic canonical type/id, field name, before/after JSON values, evidence URL/excerpt, content fingerprint, observed/occurred timestamps and timestamps.
- Required indexes:
  - \`official_refresh_source_idx\` on source_id.
  - \`official_refresh_observation_idx\` on source_change_observation_id.
  - \`official_refresh_canonical_field_idx\` on canonical_type/canonical_id/field_name.
- When table already exists, migration checks required column names and indexes (including equivalent ordered-column indexes under another name), then returns without schema mutation so Laravel can record the migration.
- The existing-table check does **not** verify every column type, nullability, default or precision. The comment describes schema equivalence more strongly than the actual checks.

Finding DB-02: either strengthen the equivalence verifier to check types/nullability/defaults or narrow the migration comment/contract so it does not imply a full schema match.

#### \`2026_10_29_000001_create_institution_field_approvals_table.php\`

- Table: \`institution_field_approvals\`.
- Includes Institution, source, source-change observation, snapshot, evidence, creator, reviewer and applied-refresh-event references.
- Foreign keys use restrictive delete behavior.
- Unique \`proposal_hash\`.
- Secondary indexes on institution/field/status, source/observation and status/decision time.
- Stores expected and proposed values, evidence URL/excerpt/fingerprint, source/evidence timestamps, status, reviewer, reason and application outcome.
- PR #917 added the migration and service/controller.
- PR #919/#921/#922 added read-only production schema/prerequisite checks.
- PR #923 added the explicitly authorized one-time migration workflow.
- PR #925 corrected post-migration schema verification.
- The existence of these files/PRs does not independently prove the current live table and migration ledger; no production query was run here.

### 10.2 Logical table and integrity coverage

Inspected models imply tables for:
- institutions, institution_names, campuses, geographies, programs, program_offerings, admission_intakes, eligibility_rules, merit_records, selection_outcomes, fee_records, scholarships, accreditations, external_rankings, recommendations;
- sources, source_snapshots, evidence, assertions, source_change_observations, source_monitoring_policies, source monitoring runs/alerts and related links;
- import_batches, import_rows, import_conflicts, import_promotion_decisions/events, identity_match_candidates, identity_resolution_decisions;
- institution_field_approvals, official_source_refresh_events;
- users and operational/readiness records.

This is a logical model inventory, not an exhaustive migration/table list.

### 10.3 Production schema separation

Current production state of these items is **UNVERIFIED** in this audit:
- \`institution_field_approvals\` existence, exact column definitions, indexes, foreign keys and migration ledger.
- \`import_batches.duplicate_rows\` exact physical definition and ledger entry.
- \`official_source_refresh_events\` exact types/indexes and migration ledger.
- Other official source refresh/observation tables and indexes.
- All academic foreign keys, unique constraints, index definitions and storage engines.

No production SQL, migration, DDL or DML was executed.

## 11. Security and reliability findings

Severity definitions:
- CRITICAL: immediate broad compromise or data loss with no meaningful gate.
- HIGH: realistic privilege escalation, code execution or serious integrity exposure.
- MEDIUM: meaningful data integrity, confidentiality, reliability or abuse risk requiring remediation before full production scale.
- LOW: limited risk or maintainability concern.
- INFORMATIONAL: design note or acceptance item.

### SEC-01 — HIGH: first-admin setup bypass for master_admin

**Files:** \`app/Http/Controllers/InstituteSetupController.php\`, \`app/Models/User.php\`, \`routes/web.php\`.

Public setup checks only \`operator\` and \`admin\`, while model recognizes \`master_admin\`. A master-admin-only installation can be bootstrapped again by an unauthenticated visitor.

Impact: unauthorized admin account creation and administrative access.

Fix/acceptance: include master_admin in setup guard or implement a one-time bootstrap lock; test master_admin-only and concurrent submissions.

### SEC-02 — HIGH: raw JSON-LD script-context injection

**File:** \`resources/views/institutes/show.blade.php\`.

The view emits:

\`\`\`blade
<script type="application/ld+json">{!! json_encode($institutionSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
\`\`\`

The schema includes institution name, official name, description and campus-derived values. JSON encoding without HTML-sensitive escaping can leave a literal \`</script>\` sequence, allowing data to terminate the script element.

Impact: stored XSS in public institution profiles if attacker-controlled or compromised source data reaches canonical fields.

Fix/acceptance:
- Use \`JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT\` or Laravel's safe JSON directive.
- Add a feature test with \`</script><script>...</script>\` in name/description and assert the payload is encoded, not executable markup.
- Review raw \`@yield\` use in title/meta attributes for equivalent escaping risk.

### SEC-03 — MEDIUM: evidence assertion state and subject are caller-controlled

**Files:** \`routes/web.php\`, \`app/Http/Controllers/InstituteSourceEvidenceCaptureController.php\`, \`app/Services/InstituteSourceEvidenceCaptureService.php\`.

The operator-only endpoint accepts arbitrary subject_type/subject_id/attribute/value and arbitrary verification_state strings (max length only). The service persists those assertion values directly. It does not verify subject ownership/type or require a separate privileged verification action for states such as verified/confirmed. The reference is not checked by SourceUrlGuard in this general evidence-capture path.

Impact: an operator can create misleading verification assertions or attach evidence to an unrelated subject. This can poison downstream evidence review and block or influence approval decisions.

Fix/acceptance:
- Restrict subject types to an explicit allowlist and verify subject exists and belongs to the selected import/source context.
- Restrict verification_state to safe observed/unverified states on operator capture.
- Require admin/independent reviewer action to set verified/confirmed.
- Validate evidence URL as HTTPS and bind source/evidence host to a qualified source where applicable.
- Add authorization and tampering tests.

### SEC-04 — MEDIUM: public child records can expose non-public states

**Files:** \`app/Http/Controllers/InstitutionController.php\`, \`app/Http/Controllers/Api/InstitutionApiController.php\`, \`app/Services/InstituteSearchCatalogService.php\`, \`app/Services/InstituteSearchFilterService.php\`.

HTML detail, API detail and search catalog use inconsistent blacklist filters. API admissions exclude only cancelled/suspended, while HTML also excludes candidate/draft/rejected/withdrawn. Accreditation/eligibility/merit/scholarship filters also use blacklists rather than a clear public-state allowlist. Search catalog can list draft/candidate/rejected/withdrawn intake statuses.

Impact: internal or unapproved academic records may appear in public API/search/profile responses.

Fix/acceptance:
- Define a central public-state policy for every academic entity.
- Reuse it in HTML, API, search catalog and exports.
- Test all candidate/draft/pending/rejected/withdrawn/cancelled/suspended/active/published states.
- Confirm only intended public statuses are visible.

### SEC-05 — MEDIUM: CSV formula injection in private review package

**File:** \`app/Services/InstituteDiscoveryReviewPackageService.php\`.

Candidate-derived fields are written to CSV with \`fputcsv()\` without neutralizing spreadsheet formula prefixes.

Impact: an operator opening a package in Excel/LibreOffice could execute a formula from a malicious source-controlled candidate value.

Fix/acceptance:
- Prefix formula-leading text values with a safe apostrophe or other spreadsheet-safe encoding.
- Test name, slug, source URL, validation errors and all exported string columns with \`=\`, \`+\`, \`-\`, \`@\`, tabs and leading whitespace.

### SEC-06 — MEDIUM: manual source evidence is not independently qualified

**Files:** \`app/Http/Controllers/InstituteImportController.php\`, \`app/Services/InstituteImportControllerSupport.php\`, \`app/Services/InstituteSourceEvidenceCaptureService.php\`, \`app/Services/InstituteImportPromoter.php\`.

Manual CSV validation checks URL syntax/HTTPS and excerpt length but does not establish authority ownership. Promotion requires active source and active evidence, but the generic promoter does not independently verify the official-source identity. Human decisions still exist, so this is not an automatic canonical bypass.

Impact: a reviewer may mistake operator-supplied arbitrary HTTPS content for verified official evidence.

Fix/acceptance:
- Preserve operator-supplied versus independently verified evidence states.
- Require authority/source identity qualification for official-source claims.
- Make promotion readiness explicitly require the evidence trust level intended by policy.

### REL-01 — MEDIUM: two independent scheduled execution planes

**Files:** \`bootstrap/app.php\`, \`.github/workflows/scheduled-institute-discovery.yml\`, \`app/Console/Commands/BootstrapInstituteAgent.php\`, \`app/Console/Commands/MonitorSourceChanges.php\`, \`app/Services/SourceMonitoringBatchService.php\`.

Laravel scheduler runs bootstrap and monitoring hourly with scheduler overlap locks. GitHub Actions also schedules hourly at minute 15 and directly invokes the same Artisan commands through Hostinger SSH. GitHub concurrency protects only that workflow; it does not coordinate with hPanel/Laravel scheduler locks.

Impact: overlapping source retrieval, duplicate work, contention and confusing run attribution. Source-row locks reduce some staging races but do not create a shared lock across both execution planes.

Fix/acceptance:
- Choose one authoritative scheduler or add a shared database/Redis lock across both invocation paths.
- Give each run a correlation ID and record invoker/run ID.
- Test simultaneous manual, GitHub and Laravel scheduler invocations.

### REL-02 — MEDIUM: scheduled workflow mutates deployed Git metadata

**File:** \`.github/workflows/scheduled-institute-discovery.yml\`.

The Hostinger SSH script runs \`git fetch\` and \`git fetch --unshallow origin main\` in the live deployed checkout to validate parity. This changes refs/objects and the shallow metadata in the shared production \`.git\` directory.

Impact: concurrent Hostinger diagnostics/deployment workflows can contend over the same Git metadata; previous project history records failures involving shallow metadata and repository lock/state. This is not a PHP source-code deployment, but it is a production filesystem mutation.

Fix/acceptance:
- Perform parity fetches in a unique disposable temporary Git repository outside the deployed checkout.
- Do not rewrite or unshallow the live deployment's \`.git\`.
- Test concurrent diagnostic/scheduled runs and cleanup behavior.

### REL-03 — MEDIUM: public search and nearby query scaling

**Files:** \`app/Services/InstituteSearchQueryService.php\`, \`app/Services/InstituteSearchFilterService.php\`, \`app/Services/InstituteNearbySearchService.php\`.

- Search uses leading-wildcard substring LIKE across multiple columns/relationships.
- Nearby search loads all bounding-box campuses before PHP distance filtering and result limiting.
- No representative-scale query benchmark or database execution plan was available.

Impact: latency and memory use may rise sharply as nationwide campus/program data grows.

Fix/acceptance:
- Benchmark with representative data.
- Add appropriate full-text/trigram or normalized search strategy if needed.
- Use spatial indexing/database distance filters or a bounded candidate query for nearby search.

### REL-04 — MEDIUM: AI candidate excerpt is not programmatically source-verified

**Files:** \`app/Services/OpenAiCompatibleInstituteAiExtractionProvider.php\`, \`app/Services/InstituteAiExtractionService.php\`, \`app/Services/InstituteDiscoveryCandidateQualityService.php\`.

The prompt asks the model to return a short exact excerpt from source content. The quality service checks that AI excerpt is non-empty, but does not verify it is an exact substring or otherwise grounded in the captured snapshot.

Impact: a model can return a plausible but unsupported excerpt; human review still prevents direct canonical publication, but evidence quality and reviewer trust are weakened.

Fix/acceptance:
- Verify excerpts against normalized source text or require deterministic locator/quote validation.
- Mark model-generated evidence as model-proposed, not verified.
- Test fabricated excerpts, hallucinated names and unsupported confidence values.

### DB-01 — MEDIUM: duplicate_rows migration checks existence, not equivalence

**File:** \`database/migrations/2026_10_23_000001_add_duplicate_rows_to_import_batches_table.php\`.

Migration's \`up()\` adds the column only if absent. It does not validate type/nullability/default/position if already present. A separate controlled workflow performs stronger checks; normal migration invocation should not silently bless an incompatible pre-existing column.

### DB-02 — MEDIUM: official refresh migration overstates existing schema verification

**File:** \`database/migrations/2026_10_25_000002_create_official_source_refresh_events_table.php\`.

Existing-table reconciliation verifies column names and index column order, but not full column type/nullability/default equivalence. Align the code and the documented contract.

### TEST-01 — MEDIUM: Unit suite omitted by configured PHPUnit suite

**Files:** \`phpunit.xml\`, \`composer.json\`, \`tests/Unit/InstituteDiscoveryCandidateExtractorTest.php\`.

- \`composer test\` runs \`vendor/bin/phpunit\`.
- \`phpunit.xml\` defines only the Feature suite directory \`tests/Feature\`.
- At least one unit test file exists under \`tests/Unit\`.
- Therefore that Unit directory is not included by the configured suite unless separately invoked.

Fix/acceptance:
- Add a Unit suite to phpunit.xml or use a default test-suite configuration that includes both.
- Confirm CI executes the extractor unit tests by test name/count.

### TEST-02 — LOW/MEDIUM: Pint CI step autoformats before checking

**File:** \`.github/workflows/laravel-ci.yml\`.

Workflow runs \`vendor/bin/pint\` on a fixed list of files, then runs \`pint --test\` on those files. The first command modifies the runner checkout, so the later check can pass after silently correcting formatting rather than failing on the submitted diff. The \`composer lint\` script is not directly invoked in the inspected workflows.

Fix/acceptance:
- Run \`pint --test\` directly against changed files (or the agreed scope).
- Do not autoformat the CI checkout before the gate.

### REL-05 — LOW/MEDIUM: unbounded public query inputs

**Files:** \`app/Http/Controllers/InstituteSearchController.php\`, \`app/Http/Controllers/Api/InstitutionApiController.php\`, \`app/Services/InstituteSearchQueryService.php\`.

Search strings and most filter values are trimmed but have no explicit max length in the inspected controllers. Query parameter binding protects SQL injection, but very long strings and broad wildcard searches can increase resource use. Add input length limits and rate-limit acceptance tests.

### INFO-01 — INFORMATIONAL: password recovery is not end-to-end

**File:** \`app/Http/Controllers/InstitutePasswordResetController.php\`.

The forgot-password handler explicitly says email recovery is not configured. Admin-assisted reset exists. Treat self-service email recovery as not implemented until notification delivery and token issuance are verified.

### INFO-02 — INFORMATIONAL: optional AI configuration is not proven operational

**Files:** \`config/institute_ai.php\`, \`app/Services/OpenAiCompatibleInstituteAiExtractionProvider.php\`, \`app/Services/InstituteAiExtractionService.php\`, \`app/Services/InstituteDiscoveryAgentService.php\`.

- AI is disabled by default (\`INSTITUTE_AI_ENABLED=false\`).
- Provider is OpenAI-compatible Chat Completions.
- Default model config string is \`gpt-5.6-luna\`; successful provider compatibility is not established by source inspection.
- Generic AI extraction is limited to programme profiles.
- Top-level institution identity extraction explicitly rejects AI.
- Scheduled discovery refuses configured profiles with \`use_ai=true\` to keep scheduled runs zero-cost.
- No live provider request was performed.

Do not describe the nationwide institution identity pipeline as AI-driven. The existing capability is optional programme extraction and deterministic institution identity discovery.

## 12. Testing quality assessment

### 12.1 Verified test infrastructure

- \`composer.json\` defines \`composer test\` as \`vendor/bin/phpunit\`.
- \`phpunit.xml\` defines a Feature suite only.
- \`.github/workflows/test.yml\` provisions PHP 8.5 and Node 22, installs dependencies, builds Vite assets, prepares SQLite, runs migration fresh, runs Python/Ruby workflow contracts, and executes \`composer test\`.
- \`.github/workflows/laravel-ci.yml\` also provisions SQLite and runs \`composer test\`; it then autoformats a fixed list with Pint and checks formatting.
- PR #920 introduced an isolated MySQL 8.4 approval-migration validation workflow (exact current run result not inspected here).

### 12.2 Strong test areas inspected

- Public listing pagination and active-status exclusion: \`tests/Feature/InstitutesDirectoryTest.php\`.
- Empty JSON paginator, API listing/detail and alias/program filters: \`tests/Feature/InstitutionApiTest.php\`.
- Nearby distance, validation, active status and de-duplication: \`tests/Feature/InstituteNearbySearchTest.php\`.
- Discovery bootstrap, profile failure isolation, idempotency, empty-batch retry and parentless institution staging: \`tests/Feature/InstituteAgentBootstrapTest.php\`.
- Deterministic extraction and identity corroboration: \`tests/Unit/InstituteDiscoveryCandidateExtractorTest.php\` (present but omitted from configured PHPUnit suite unless invoked separately).
- Source URL SSRF controls and redirect protections: \`tests/Feature/SourceUrlGuardTest.php\`, \`tests/Feature/SafeSourceHttpRetrieverTest.php\`.
- Persisted field approval, reviewer separation, stale value/evidence handling and audit event: \`tests/Feature/InstitutionFieldApprovalServiceTest.php\`.
- Institution refresh fail-closed behavior: \`tests/Feature/OfficialSourceCanonicalRefreshServiceTest.php\`.
- Hostinger diagnostics workflow contract: \`tests/Feature/HostingerDirectoryVisibilityDiagnosticsContractTest.php\`.

### 12.3 Prioritized test gaps

| Priority | Missing or weak test | Why |
|---|---|---|
| P0 | Setup route with master_admin-only database | Prevent unauthenticated admin bootstrap |
| P0 | Stored XSS in JSON-LD and head metadata | Prevent public stored script injection |
| P1 | Public status allowlist across HTML/API/search | Prevent candidate/draft/rejected academic data leakage |
| P1 | Evidence capture subject/verification-state authorization | Prevent forged trusted assertions |
| P1 | CSV formula injection | Protect operators opening review packages |
| P1 | Concurrent scheduled/manual discovery | Validate shared lock/idempotency across execution planes |
| P1 | Cross-institution program offering rejection at DB/service boundaries | Preserve academic relationship integrity |
| P1 | Manual CSV import partial failure/recovery | Prevent stranded partially processed batches |
| P1 | AI excerpt source-grounding | Prevent fabricated evidence excerpts |
| P2 | Nearby search national-scale benchmark | Prevent memory/latency regression |
| P2 | Search max input length and abusive request tests | Bound public query cost |
| P2 | Detail-page empty state, metadata and SEO tests | Confirm public rendering behavior |
| P2 | Sitemap XML escaping/chunking tests | Protect crawler correctness |
| P2 | Production MySQL schema verifier fixtures for wrong types/defaults | Test actual schema equivalence, not only names |
| P2 | CI test discovery includes Unit suite | Ensure unit tests actually run |
| P2 | Pint gate fails on submitted unformatted code | Prevent CI autoformat masking |

### 12.4 Exact CI interpretation

The following exact PR-head runs were successful:
- PR #954 head: Test #41180827589 and Laravel CI #41180827573.
- PR #953 head: Test #41179406503 and Laravel CI #41179406552.
- PR #911 head: Test #40836582666 and Laravel CI #40836582833.

No current-main exact-head status was returned by the connector. Do not report current main as passing based solely on these PR-head results.

## 13. Technical dependency and completion matrix

| Module | Status | What exists | Missing / dependency |
|---|---|---|---|
| Laravel foundation/bootstrap | COMPLETE at source level | Laravel 13, PHP 8.5, route/command registration, middleware aliases | Current production parity/runtime |
| Public listing | PARTIALLY COMPLETE | Active-only listing, pagination, empty state | Explicit empty-state test, stable tie-breaker, fresh live check |
| Public detail | PARTIALLY COMPLETE | Nested academic profile and SEO schema | XSS fix, unified publication states, inactive campus offering guard |
| Public search | PARTIALLY COMPLETE | Query and multi-filter services, cached catalog | Status allowlist, input limits, performance plan |
| Public API | PARTIALLY COMPLETE | Versioned listing/detail/nearby endpoints and tests | API Resources, HTML/API status parity, fresh live check |
| Academic relationships | PARTIALLY COMPLETE | Institution/campus/program/offering/intake/geography models | Full migration constraints, public-state rules, cross-parent DB invariant |
| Source retrieval | COMPLETE at source-code level | DNS validation/pinning, redirects, HTTPS downgrade block, size/time limits | Current production runtime and provider tests at scale |
| Deterministic extraction | PARTIALLY COMPLETE | Institution/program/campus extractors and tests | Official source coverage, source drift acceptance |
| AI programme extraction | PARTIALLY COMPLETE / DISABLED BY DEFAULT | Provider contract, OpenAI-compatible implementation, normalization | Provider/model compatibility, excerpt grounding, live opt-in evidence |
| Institution identity staging | PARTIALLY COMPLETE | Parentless identity staging, source evidence, duplicate checks, locking | Human decisions and nationwide profile qualification |
| Import/evidence | PARTIALLY COMPLETE | CSV import, source snapshots, evidence/assertions, review packages | Evidence trust controls, transaction recovery, formula-safe exports |
| Duplicate/identity resolution | PARTIALLY COMPLETE | Canonical/staged duplicate checks, match candidates, decisions | Whole-queue review and concurrency acceptance |
| Human approval | PARTIALLY COMPLETE | Persisted field approval, admin reviewer separation, stale proposal handling | Current production schema and role policy confirmation |
| Canonical promotion/publication | PARTIALLY COMPLETE | Explicit decisions, evidence/conflict gates, separate publication | Role confirmation, row locking/idempotency tests |
| Source monitoring | PARTIALLY COMPLETE | Policies, snapshots, observations, alerts, discovery outcomes | Scheduler ownership and shared concurrency lock |
| Admin/operator | PARTIALLY COMPLETE | Auth, role middleware, queues, user management, operations | Fix bootstrap gate; confirm publication role and evidence permissions |
| Database/migrations | BLOCKED / UNVERIFIED for production | Migrations and guarded schema diagnostics | Fresh production schema/ledger report |
| GitHub/Laravel automation | PARTIALLY COMPLETE | CI, Project Controller, Orchestrator, scheduled discovery, Hostinger workflows | Exact-main run ledger, shared scheduler lock, isolate live Git metadata |
| Security | PARTIALLY COMPLETE | Auth throttle, role middleware, SSRF controls, private package routes | SEC-01 to SEC-05 remediation and regression tests |
| Testing | PARTIALLY COMPLETE | Extensive feature/workflow tests | Unit suite inclusion, security/state/performance/concurrency gaps |
| Production hosting | BLOCKED / UNVERIFIED | Controlled deploy and read-only diagnostics | Fresh revision parity, API/page, schema and scheduler evidence |
| Nationwide coverage | NOT COMPLETE | HEC profile inventory and source qualification tooling | Qualified sources, enabled profiles, validated identity and data review |

## 14. Minimum technical completion sequence

This is a proposed technical order, not authorization to begin Step 3 or production work.

### Priority 1 — Security and public data integrity

1. Fix SEC-01 setup bootstrap guard.
2. Fix SEC-02 safe JSON-LD and metadata output.
3. Centralize public academic status policy and fix SEC-04.
4. Constrain evidence capture states/subjects and qualify source identity.
5. Neutralize CSV formula injection.

Acceptance: regression tests pass for all five findings; no public endpoint exposes candidate/draft/rejected records.

### Priority 2 — Test and migration contract correctness

1. Include \`tests/Unit\` in PHPUnit discovery.
2. Make Pint CI fail on unformatted submitted code rather than autoformatting first.
3. Strengthen official-source refresh existing-table schema equivalence checks.
4. Ensure duplicate_rows guarded workflow is the only authorized production reconciliation path.
5. Add negative fixtures for wrong type/nullability/default/index and partial ledger states.

Acceptance: exact PR-head Test and Laravel CI runs pass; schema verifier catches intentionally incompatible fixtures.

### Priority 3 — Automation concurrency and production safety

1. Decide whether Laravel scheduler or GitHub Actions is the authoritative production discovery scheduler.
2. If both must remain, implement a shared database/Redis lock and run correlation IDs.
3. Move scheduled workflow parity Git operations to a disposable temporary repository.
4. Ensure cleanup is run-scoped and cannot touch deployed checkout or diagnostic evidence.
5. Verify one scheduled run with read-only before/after canonical counts and expected staged/evidence outcome counts.

Acceptance: simultaneous invocations cannot duplicate source work or conflict over production Git metadata; no canonical mutation occurs in discovery.

### Priority 4 — Performance and API contract

1. Add deterministic tie-breaker to listing pagination.
2. Bound search inputs.
3. Benchmark wildcard search.
4. Bound nearby candidate retrieval and consider spatial indexes.
5. Add explicit API Resources and stable response schema tests.
6. Ensure HTML/API status and relationship visibility are identical.

Acceptance: tests cover realistic scale and public contract.

### Priority 5 — Data and source qualification

1. Obtain fresh read-only Hostinger parity/runtime evidence.
2. Verify production schema and migration ledger.
3. Qualify sources through the read-only qualification path.
4. Keep disabled profiles disabled until explicit source acceptance.
5. Human-review candidate identity and evidence.
6. Only later, under separate approval, decide whether any data import/promotion/publication is authorized.

## 15. Acceptance criteria for future completion

The existing architecture should be considered technically ready only when all applicable criteria below are evidenced:

### Public application
- Listing returns HTTP 200 for empty and populated data.
- Empty state is explicit; API returns valid JSON paginator with total zero.
- Detail route renders only active/published institution and approved related records.
- Search and filters do not reveal non-public statuses.
- HTML and API response rules are aligned.
- Sitemap contains active canonical institutions only and valid XML.
- SEO/JSON-LD is safely encoded.

### Data model
- Every program offering references a program and, if campus-specific, a campus belonging to the same institution.
- Campus geography is valid and active for public visibility.
- Slugs have database uniqueness guarantees.
- Parent-child foreign keys and delete restrictions are documented.
- Status values and transitions are centrally defined.

### Discovery and evidence
- All outbound source requests use the guarded retriever.
- Source identity/authority is independently qualified.
- Snapshot hash and evidence linkage are verifiable.
- AI-generated excerpts are checked against the source.
- Discovery stages candidates only; it cannot approve/promote/publish.
- Duplicate/identity decisions are auditable and idempotent.
- Review package is private, checksummed and formula-safe.

### Approval and publication
- Proposal creator cannot approve own proposal.
- Only authorized reviewer can approve/reject.
- Stale canonical value or evidence invalidates proposal.
- Promotion requires explicit decision, active evidence and no unresolved conflicts.
- New institution remains candidate until separate publication.
- Publication role is explicitly decided and enforced.
- Reversal preserves audit history.

### Automation and production
- One authoritative scheduler or a shared cross-runner lock exists.
- Scheduled workflow never fetches/rewrites live deployment Git metadata.
- Current deployed application paths match approved main application paths.
- Production schema and migration ledger match verified migration definitions.
- Exact current-main CI and controlled read-only diagnostics pass.
- Production write workflows remain manual, narrowly scoped and explicitly authorized.

## 16. Evidence links and source files reviewed

### Main source files

- \`composer.json\`
- \`phpunit.xml\`
- \`bootstrap/app.php\`
- \`routes/web.php\`
- \`routes/api.php\`
- \`routes/console.php\`
- \`app/Http/Controllers/InstitutionController.php\`
- \`app/Http/Controllers/Api/InstitutionApiController.php\`
- \`app/Http/Controllers/InstituteSearchController.php\`
- \`app/Http/Controllers/InstituteSitemapController.php\`
- \`app/Http/Controllers/InstituteRobotsController.php\`
- \`app/Http/Controllers/InstituteSetupController.php\`
- \`app/Http/Controllers/InstituteAuthController.php\`
- \`app/Http/Controllers/InstitutePasswordResetController.php\`
- \`app/Http/Controllers/InstituteImportController.php\`
- \`app/Http/Controllers/InstituteSourceEvidenceCaptureController.php\`
- \`app/Http/Controllers/InstituteDiscoveryReviewPackageController.php\`
- \`app/Http/Controllers/InstitutionFieldApprovalController.php\`
- \`app/Http/Controllers/InstituteImportPromotionController.php\`
- \`app/Http/Middleware/EnsureInstituteOperator.php\`
- \`app/Http/Middleware/EnsureInstituteAdmin.php\`
- \`app/Models/User.php\`
- \`app/Models/Institution.php\`
- \`app/Models/InstitutionName.php\`
- \`app/Models/Campus.php\`
- \`app/Models/Geography.php\`
- \`app/Models/Program.php\`
- \`app/Models/ProgramOffering.php\`
- \`app/Models/AdmissionIntake.php\`
- \`app/Models/Accreditation.php\`
- \`app/Models/ExternalRanking.php\`
- \`app/Models/Recommendation.php\`
- \`app/Models/Source.php\`
- \`app/Models/SourceSnapshot.php\`
- \`app/Models/SourceChangeObservation.php\`
- \`app/Models/Evidence.php\`
- \`app/Models/ImportBatch.php\`
- \`app/Models/ImportRow.php\`
- \`app/Models/InstitutionFieldApproval.php\`
- \`app/Models/OfficialSourceRefreshEvent.php\`
- \`app/Services/InstituteSearchQueryService.php\`
- \`app/Services/InstituteSearchFilterService.php\`
- \`app/Services/InstituteSearchCatalogService.php\`
- \`app/Services/InstituteNearbySearchService.php\`
- \`app/Services/InstituteSeoService.php\`
- \`app/Services/SafeSourceHttpRetriever.php\`
- \`app/Services/SourceUrlGuard.php\`
- \`app/Services/InstituteDiscoveryAgentService.php\`
- \`app/Services/InstituteIdentityDiscoveryService.php\`
- \`app/Services/InstituteDiscoveryCandidateExtractor.php\`
- \`app/Services/InstituteDiscoveryCandidateQualityService.php\`
- \`app/Services/InstituteAiExtractionService.php\`
- \`app/Services/OpenAiCompatibleInstituteAiExtractionProvider.php\`
- \`app/Services/InstituteSourceEvidenceCaptureService.php\`
- \`app/Services/InstituteDiscoveryReviewPackageService.php\`
- \`app/Services/ImportDuplicateDetectorService.php\`
- \`app/Services/InstituteIdentityResolver.php\`
- \`app/Services/InstituteImportPromoter.php\`
- \`app/Services/EnrichmentPromotionService.php\`
- \`app/Services/StructuredEnrichmentService.php\`
- \`app/Services/InstitutionFieldApprovalService.php\`
- \`app/Services/OfficialSourceCanonicalRefreshService.php\`
- \`app/Services/SourceMonitoringBatchService.php\`
- \`app/Services/SourceMonitoringPipeline.php\`
- \`app/Services/DiscoveryMonitoringPolicyService.php\`
- \`app/Console/Commands/BootstrapInstituteAgent.php\`
- \`app/Console/Commands/MonitorSourceChanges.php\`
- \`app/Console/Commands/MonitorInstituteSources.php\`
- \`app/Console/Commands/QualifyOfficialInstituteSources.php\`
- \`config/institute_ai.php\`
- \`config/institute_agent.php\`
- \`resources/views/institutes/index.blade.php\`
- \`resources/views/institutes/show.blade.php\`
- \`resources/views/institutes/search.blade.php\`
- \`resources/views/layouts/app.blade.php\`
- \`resources/js/app.js\`
- \`resources/css/app.css\`
- \`package.json\`
- \`vite.config.js\`

### Migration and automation files

- \`database/migrations/2026_10_23_000001_add_duplicate_rows_to_import_batches_table.php\`
- \`database/migrations/2026_10_25_000002_create_official_source_refresh_events_table.php\`
- \`database/migrations/2026_10_29_000001_create_institution_field_approvals_table.php\`
- \`.github/workflows/test.yml\`
- \`.github/workflows/laravel-ci.yml\`
- \`.github/workflows/scheduled-institute-discovery.yml\`
- \`.github/workflows/hostinger-readonly-diagnostics.yml\`
- \`.github/workflows/hostinger-artisan-runtime-only.yml\`

### Tests inspected

- \`tests/Feature/InstitutesDirectoryTest.php\`
- \`tests/Feature/InstitutionApiTest.php\`
- \`tests/Feature/InstituteNearbySearchTest.php\`
- \`tests/Feature/InstituteAgentBootstrapTest.php\`
- \`tests/Unit/InstituteDiscoveryCandidateExtractorTest.php\`
- \`tests/Feature/SourceUrlGuardTest.php\`
- \`tests/Feature/SafeSourceHttpRetrieverTest.php\`
- \`tests/Feature/InstitutionFieldApprovalServiceTest.php\`
- \`tests/Feature/OfficialSourceCanonicalRefreshServiceTest.php\`
- \`tests/Feature/HostingerDirectoryVisibilityDiagnosticsContractTest.php\`

### PRs and runs

- PR #954 Step 1 baseline (open): https://github.com/mattari20/estudent-listing/pull/954
- PR #953 runtime failure repair (open): https://github.com/mattari20/estudent-listing/pull/953
- PR #911 Hostinger parity isolation (open): https://github.com/mattari20/estudent-listing/pull/911
- PR #917 persisted approval workflow (merged)
- PR #918 disabled HEC identity profiles (merged)
- PR #920 isolated MySQL approval migration validation (merged)
- PR #923 controlled approval migration (merged)
- PR #925 approval schema verifier correction (merged)
- PR #929 identity staging serialization (merged)
- PR #930 changed-snapshot duplicate tests (merged)
- PR #894 duplicate_rows partial-state guard (merged)
- PR #896 duplicate_rows diagnostics (merged)
- PR #848 official source refresh schema handoff (merged)
- PR #902 API subfolder route registration (merged)

## 17. Safety confirmation and Step 2 status

During this Step 2 audit:
- No production SSH command was run.
- No production database connection/query/write was run.
- No migration was run.
- No deployment or cache clear was run.
- No imports, source retrieval, discovery runs, candidate decisions, approvals, promotions, reversals or publications were executed.
- No HEC profile was enabled.
- No GitHub Actions workflow was dispatched, rerun or disabled.
- No pre-existing project PR was merged, closed or modified. This audit's own documentation PR #955 was created and its report commit updated once to clarify the API offering/geography finding.
- No existing diagnostic report was opened, overwritten or deleted.
- No application code or production configuration was changed.

Only one new documentation file is proposed on a dedicated audit branch.

**Step 2 report status:** DOCUMENTED; awaiting review.  
**Step 3 status:** NOT STARTED.  
**Next action:** user review and approval. Do not proceed to Step 3 until explicitly approved.
