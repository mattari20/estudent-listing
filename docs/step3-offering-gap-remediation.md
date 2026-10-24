# Step 3: Offering gap remediation

The latest read-only live audit reported 36 active programmes with no offering rows:
https://github.com/mattari20/estudent-listing/actions/runs/40065220614

## Campus-aware evidence register

The register now records a candidate campus ID/name, candidate delivery mode, and a separate campus-source URL for each gap. These are review targets, not verified facts. Candidate mappings were carried forward from the current audit context and must be checked against the live campus table and current official institutional evidence before any offering is proposed.

Programme existence alone is not campus evidence. Do not map a Lahore programme to an Islamabad campus, and do not assume a virtual programme's support campus is its teaching location. The Virtual University rows are marked online as a review hypothesis; confirm the delivery model before using it.

## Automated validation

Run:

```bash
python scripts/validate_step3_offering_gap_register.py
```

The validator checks the 36-record count, required columns, unique programme IDs, numeric IDs, allowed delivery modes, HTTPS source URLs, and safety gates for missing evidence, unconfirmed campuses, and wrong-campus records. GitHub Actions runs the validator on relevant pull requests and main-branch changes.

## Live database preflight (read-only)

After the new command is deployed with the approved application revision, run:

\`\`\`bash
php artisan institutes:offering-gap-preflight --json --no-ansi
\`\`\`

The command compares every register row to live programme and campus IDs, verifies both records belong to the expected institution, compares the candidate campus name with the live campus name, returns available live campus slug/address/geography/type metadata, checks for existing programme/campus offering rows (including active rows), and applies the official programme/campus evidence gate. It reports blockers per record and always declares zero database mutations. A successful workflow run means the preflight executed; inspect `preflight_status` and per-row blockers to determine whether the evidence gap is actually resolved. It does not insert, update, activate, deactivate, or publish any offering. A `READY_FOR_MANUAL_REVIEW_ONLY` result is not production-write authorization.

The register file must be present at the path supplied by `--register`; otherwise the command fails closed. Run it only after the command and evidence register are deployed at the same approved revision.

## Apply gate

No production database writes are authorized by this register or PR. Before any apply workflow is proposed, it must:
1. Default to dry-run and print exact proposed rows.
2. Resolve programme and campus IDs against the live database; verify both belong to the same institution.
3. Require current official programme evidence and explicit campus/delivery evidence for each row.
4. Reject duplicate programme/campus/delivery-mode offerings and refuse ambiguous rows.
5. Require an explicit apply switch, log every affected ID, and remain idempotent.
6. Re-run the read-only live audit and compare before/after counts.

The `ready-for-validation` label means only that a row can enter a deeper preflight; it is not approval for a production insert. Any row still lacking campus-specific evidence must remain blocked from application.


## Evidence audit update (24 October 2026)

The evidence register was refreshed with separate official campus-source URLs where reliable institutional pages were found:

- **UVAS (program IDs 77, 78, 79, 85):** the university's 2026 undergraduate orientation page explicitly labels a section “Degree Programs at UVAS Main Campus, Lahore” and lists Biochemistry, Environmental Sciences, Human Nutrition and Dietetics, and BBA. The register now marks the campus as `main-campus-confirmed` for these four candidate rows and records that official page. Programme rows remain `review`; campus evidence alone does not establish that the current database mapping is otherwise ready to write.
- **COMSATS Lahore (program ID 1):** the official CUI Lahore BSCS page confirms the BS Computer Science programme at the Lahore campus. The register records it as campus-confirmed and retains the row at `review` pending the full live preflight.
- **Punjab University (program IDs 3, 35, 36, 37):** the official Quaid-e-Azam Campus page is now recorded as campus context. The current admission notice distinguishes FCIT New Campus and FCIT Old Campus, so the generic candidate “Quaid-e-Azam Campus” is not treated as a sufficiently specific mapping; these rows remain unverified and under review.
- **UCP (program ID 21):** the official Faculty of Languages & Literature page, including the Johar Town address, is recorded as campus context. The register does not infer that this proves the database's “Main Campus” label; the row remains under review.
  
These source updates improve traceability, but they do not change the read-only safety gate. No migrations or production data writes are part of this change. The live preflight must be rerun after deployment, and every remaining blocker must be resolved from record-level evidence before any apply workflow is considered.


## Evidence audit update (24 October 2026 — NUML, NTU and Air University)

- **NUML (programme IDs 11, 43, 44, 45):** the official undergraduate programme directory explicitly separates offerings by campus, including Main Campus and regional campuses; the Spring 2026 official admission advertisement also separates Islamabad, Rawalpindi, Lahore, and other campus programmes. The register now records the campus-specific official pages and marks the candidate Main Campus association as confirmed for these four programmes. The rows remain `review`, not production-ready.

- **Federal Urdu University (programme ID 71):** the previous register entry incorrectly used a NUML admission advertisement for this different institution. That source and the campus-confirmed status have been removed; the row is now `not-verified` / `blocked` until a genuine Federal Urdu University programme and campus source is established.
- **National Textile University (programme IDs 30, 31):** the official NTU admissions page lists BS Polymer Engineering and Bachelor of Fashion Design, and identifies the institution as National Textile University Faisalabad. The register now records that page as campus evidence and marks the candidate Faisalabad Main Campus association as confirmed. Both rows remain `review` because the full live preflight still must validate current IDs, names, statuses, duplicates, and evidence.
- **Air University (programme IDs 50, 51):** an official Air University bachelor-programmes page lists BS Cyber Security and BS Information Technology. That page is recorded as a separate source, but the register deliberately keeps campus status at `verify-Islamabad-campus`: a programme list alone is not treated as proof of a specific campus assignment.
- **Remaining unknowns:** no source has been invented for NCA Bachelor of Design, Air University BS Artificial Intelligence, or KIU BS Computer Science; those rows remain blocked. Virtual University rows remain under review because an online delivery mode must not be confused with a physical teaching campus.

This is evidence-register and documentation work only. It does not create or activate any `program_offerings` rows and does not authorize production writes.


## Evidence audit update (24 October 2026 — University of Education and University of Lahore)

- **University of Education Township Campus (programme IDs 60, 61, 62, 63):** added the official Township Campus page as campus-context evidence for each row. That page explicitly lists BS English among Township Campus programmes but does not list BS Computer Science, BS Information Technology, or BS Business Analytics. The register therefore retains all four rows at `review` and keeps the campus mapping unconfirmed; the three unlisted programmes must not be assumed to belong to Township Campus from a university-wide degree list.
- **University of Lahore BS Game Design (programme ID 69):** replaced the general prospectus URL with the official School of Creative Arts programme page, which confirms the current BS Game Design programme and its department. Campus mapping remains `verify-current-program-and-campus` because the programme page alone does not explicitly connect the programme to the database's candidate campus record.

No production data was changed. These sources improve auditability but do not clear the live preflight evidence gate or authorize any offering inserts.


## Evidence audit update (24 October 2026 — KIU and National College of Arts)

- **Karakoram International University (programme ID 8):** the official Fall 2025 admissions page lists BS Computer Science and identifies the admissions location as KIU Main Campus, University Road, Gilgit. The register now records this official source for both programme and campus context. The row remains `review` pending live ID/name/status and duplicate-offering checks.
- **National College of Arts (programme ID 74):** the official prospectus lists specific design qualifications (including Visual Communication Design, Textile Design, Fashion Design, Product Design and Ceramic Design), rather than establishing that the database's generic label `Bachelor of Design` is an exact current programme name. The row therefore remains `blocked / not-verified` until the exact programme identity and campus are reconciled. No generic degree title is inferred from related design programmes.
- **Federal Urdu University (programme ID 71):** no reliable official programme/campus source has yet been added. The prior unrelated NUML source remains removed and this row remains blocked.

These are evidence-register and documentation changes only. No database writes or migrations are included.


## Evidence audit update (24 October 2026 — GCU, UCP and Punjab University)

- **GC University Lahore (programme IDs 33 and 34):** the official admissions page lists BS Biotechnology and BS English; the official contact page identifies the university campus at Katchery Road, Lahore. Campus source URLs have been added, but the register remains `review` / `verify-current-campus` until the live candidate campus ID/name is reconciled against the current database.
- **University of Central Punjab (programme ID 21):** replaced the general Fall 2026 list with the official BS English Language & Literature programme page. The official faculty page gives the Johar Town, Lahore address. The record remains under review until the candidate campus ID/name is checked live.
- **University of the Punjab FCIT (programme IDs 3, 35, 36 and 37):** the official 2026–27 admission notice explicitly distinguishes FCIT New Campus from FCIT Old Campus and lists programmes differently across them. The campus evidence URL now points to this campus-specific notice, not a generic campus page. The existing candidate campus label `Quaid-e-Azam Campus` has **not** been assumed to equal either FCIT campus; all four rows remain `review` pending resolution of the actual campus ID/name and exact programme mapping.

No database writes or migrations were performed. These evidence improvements do not authorize offering inserts.
