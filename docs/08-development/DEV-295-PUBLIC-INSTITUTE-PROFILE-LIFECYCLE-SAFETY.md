# DEV-295 — Public Institute Profile Lifecycle Safety

## Purpose

Keep inactive or superseded canonical child records out of the public institute profile.

## Public lifecycle rules

The profile exposes:

- active campuses;
- active programs;
- active program offerings;
- non-cancelled/non-suspended admission intakes;
- non-rejected/non-withdrawn scholarships;
- non-superseded fee records;
- non-rejected/non-withdrawn accreditation records;
- non-rejected/non-withdrawn eligibility and merit records.

Inactive or historical records remain canonical data but are not presented as current public information.

## Safety

This is a read-only presentation boundary. It does not delete, deactivate, rewrite, or otherwise mutate canonical records.

## Testing

Regression coverage verifies that current records remain visible while inactive, withdrawn, suspended, and superseded records are excluded from the public profile.
