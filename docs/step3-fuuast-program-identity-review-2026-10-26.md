# Phase 2 Step 3 — FUUAST identity check (2026-10-26)

## Finding

The offering-gap register row `program_id=71, institution_id=11` is labeled **FUUAST, BS Accounting & Finance**, with candidate campus “Islamabad Main Campus”. That combination is not supported by the current official FUUAST programme sources reviewed.

Official FUUAST undergraduate programme page:
- https://fuuast.edu.pk/under-graduate-program/
- The listed business/commerce offerings include BBA, BS Commerce and BS Economics at Abdul Haq Campus, and BBA/BS Commerce at Gulshan Campus. The page does not list BS Accounting & Finance.
- Official FUUAST Islamabad undergraduate page: https://www.fuuastisb.edu.pk/Undergraduate.aspx. Its current list includes Economics & Finance and other undergraduate degrees, but does not list BS Accounting & Finance.

A separate institution, **Foundation University Islamabad (FUI)**, publishes a Bachelor of Accounting and Finance programme:
- https://fusst.fui.edu.pk/bachelor-accounting-and-finance
- This is a different institution and must not be treated as FUUAST without explicit confirmation of the intended canonical institution and program IDs.

## Disposition

- Keep program ID 71 blocked.
- Do not change institution_id, campus_id, programme identity, or the canonical database based on name similarity.
- The owner/reviewer must resolve whether this row is intended to represent FUUAST (in which case the listed degree/campus combination appears unsupported by reviewed official lists) or Foundation University Islamabad (in which case the institution identity and canonical program/campus IDs need separate verification).
- Add no guessed source URL to the register as though it validates the current row.

## Safety and next step

This is a source-review note only. No database writes, migrations, approvals, promotions, publication or deletions occurred. Resolve the institution/program identity first, then update the evidence register only with exact official sources. The last confirmed live audit remains Run #11; this note does not claim any blocker count changed.
