# Step 3 blocked-record identity review

Checked: 2026-10-26

This note preserves the reasons two offering-gap rows remain blocked. It is not a canonical data repair plan and does not authorize production changes.

## Row 71 — BS Accounting & Finance

- Current register status: `not-verified` / `blocked`.
- Institution ID: `11`; the current evidence available in the register does not reliably establish which canonical institution this ID represents.
- Risk: adding a programme URL based only on a matching programme title could attach evidence to the wrong institution or campus.
- Required evidence: verify the canonical institution ID against its official name and official domain, then find the institution's official programme page and a campus-specific source.
- Decision: do not fill the missing source URL or change the blocked status until identity is established.

## Row 74 — Bachelor of Design

- Current register status: `not-verified` / `blocked`.
- An official NCA prospectus is available at https://www.nca.edu.pk/prospectus/bfa-propspectus-25-8-24rv.pdf.
- The prospectus lists specific design programmes/majors; this alone does not prove that the generic canonical label “Bachelor of Design” is an exact award title or an appropriate match for the candidate institution/campus.
- Required evidence: map the canonical programme to the exact NCA award/major and verify the campus relationship.
- Decision: keep blocked until the award and campus mapping are explicit.

## Safety and exit criteria

- Do not infer institution identity from a programme-name match.
- Do not approve, promote, delete, or rewrite canonical production rows based on this note.
- Close each blocker only when official sources prove institution identity, exact award, and campus linkage, with duplicate/identity checks documented.
