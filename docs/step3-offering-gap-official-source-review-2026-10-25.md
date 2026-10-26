# Official Source Review — Offering Gaps (2026-10-25)

## Scope

This is a public-source review of the committed Step 3 evidence register. It is not a live database audit and does not prove that the current canonical campus/offering relationships are correct. No canonical data was changed.

## Evidence links added to the static register

- Programme IDs 39 and 40 (LUMS BSc Management Science and BSc Economics): added LUMS's official Contact Us page as campus/location evidence: https://www.lums.edu.pk/contact-us. It states the university's address as DHA, Lahore Cantt. This supports the institution's Lahore location only; it does not prove the current offering-to-campus relationship, so both rows remain in review.
- Programme ID 10 (NUST BS Computer Science): added the official NUST undergraduate programmes-and-institutions page as campus evidence: https://nust.edu.pk/admissions/undergraduates/list-of-ug-programmes-and-institutions/. The page lists BS Computer Science under the School of Electrical Engineering and Computer Science, Islamabad, and also lists other campuses for this programme. The existing “Islamabad Main Campus” label must therefore be checked against the canonical campus model; do not assume the programme is exclusive to one campus.

## Unresolved source gaps — intentionally not guessed

- Programme ID 71 — “BS Accounting & Finance”: both programme and campus source URLs remain blank because the evidence register's institution_id is 11 and the public-source search did not establish a reliable mapping from that ID to an institution. Similar programme names at unrelated institutions must not be attached to this row.
- Programme ID 74 — “Bachelor of Design”: both URLs remain blank. NCA's official 2026 prospectus lists specific design awards (including Visual Communication Design, Textile Design, Fashion Design, Product Design and Ceramic Design), but the evidence reviewed did not establish that the canonical label “Bachelor of Design” is the exact current award. Normalize the official programme name before assigning a source URL.
- Programme IDs 69, 7 and 55: no campus source was added in this pass. UOL's official course page supports a programme candidate, but the row still needs direct current campus evidence. Virtual University's official programme list confirms BS Biotechnology and Computer Science as programmes, while its official virtual-campus list shows many distinct campuses; that list does not establish that the current “Lahore Main Campus” candidate is the correct campus relationship for either online programme.

## Next verification actions

1. Retrieve the canonical institution record for institution_id 11 and verify ID 71 against that institution's own official programme page.
2. Confirm NCA's exact current award name and source the specific programme page/prospectus section.
3. Verify the current campus relationship for each remaining row against the institution's official campus/programme source and the live duplicate audit.
4. Keep all rows in review/blocked state until those checks are complete; do not promote from source-link presence alone.

## Official public sources reviewed

- LUMS contact page: https://www.lums.edu.pk/contact-us
- NUST undergraduate programmes and institutions: https://nust.edu.pk/admissions/undergraduates/list-of-ug-programmes-and-institutions/
- Virtual University BS programmes: https://vu.edu.pk/AboutUs/ProgramsOffered?Degree=BS
- Virtual University campuses: https://vu.edu.pk/PVCs/Default?p=punjab
- NCA official prospectus: https://www.nca.edu.pk/prospectus/bfa-prospectus-27-7-25.pdf
- University of Lahore course page: https://soca.uol.edu.pk/course/bs-in-game-design/

## Status

**PARTIAL — evidence links added for 3 rows; no canonical changes.** The 36-row static register still requires live duplicate checks and row-level verification. This public-source pass is not a substitute for the fresh live audit required by issue #585.


## Additional official-source verification — 26 October 2026

This follow-up pass checked the remaining ambiguous entries against current official pages. It updates only evidence metadata; it does not authorize an offering insert or change canonical data.

- **Programme ID 62 — University of Education BS English:** the official [Township Campus page](https://www.ue.edu.pk/campus.php?id=40) explicitly lists BS English among the campus's existing programmes. The register now points to this page and records campus/programme evidence as confirmed. The row remains `review`, not ready-to-write, pending live identity/status/duplicate checks.
- **Programme ID 69 — University of Lahore BS Game Design:** the official [programme page](https://soca.uol.edu.pk/course/bs-in-game-design/) confirms the programme and lists a September 2026 start date. The university's [Fall 2026 scholarship flyer](https://uol.edu.pk/wp-content/uploads/2026/06/Scholarship-Flyer.pdf) lists BS Game Design and identifies a Lahore Main Campus, but the evidence reviewed does not explicitly connect this programme to the candidate campus ID. Therefore the campus-source cell is intentionally left blank and the row remains in review.
- **Programme ID 74 — National College of Arts “Bachelor of Design”:** the official [NCA 2026 prospectus](https://www.nca.edu.pk/prospectus/bfa-prospectus-27-7-25.pdf) lists specific undergraduate design majors and describes a Bachelor of Design award under Ceramic Design. It does not establish that the generic canonical label “Bachelor of Design” is the exact programme identity intended by this row. The row remains blocked until the programme identity is resolved; no generic source link was used to imply a match.
- **Programme IDs 7 and 55 — Virtual University:** the official [Virtual University campus directory](https://vu.edu.pk/PVCs/Default?p=punjab) lists multiple Lahore campuses. It does not prove that either online programme should be attached to the candidate “Lahore Main Campus” record. Both campus-source cells remain blank until the data model's intended delivery/support-campus relationship is established.
- **Programme ID 71 — BS Accounting & Finance:** no source was added. Institution ID 11 is still not reliably mapped to a named canonical institution from available repository evidence, so attaching a similarly named programme from another institution would be unsafe.
- **University of Education campus distinction:** the official [Township Campus page](https://www.ue.edu.pk/campus.php?id=40) lists BS English, but does not list BS Computer Science or BS Information Technology. The existing rows 60 and 61 remain blocked because their register notes a wrong-campus mapping; this pass does not reassign them to another campus.

### Updated outcome

- One register row (programme ID 62) gained direct official campus/programme evidence.
- No programme row was promoted to `ready-for-validation` or authorized for production writes.
- The remaining uncertain campus mappings and unresolved programme identity remain explicitly blocked/review.
- The live preflight and reconciliation still need their per-row reports reviewed; a green workflow run alone does not establish that all 36 offering gaps are resolved.
- No migrations, canonical data writes, promotions, or deletions were performed.
