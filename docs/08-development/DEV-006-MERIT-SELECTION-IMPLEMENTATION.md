# DEV-006 — Merit & Selection Implementation

Status: Implemented in branch; pending merge.

This slice implements the merit and selection boundary defined by MOD-011.

## Core distinction

Eligibility determines whether an applicant meets published requirements. Merit represents a score, cutoff, position, rank or related selection signal in a defined context. Selection outcome records published selection-stage results or thresholds.

## Included

- Merit records tied to a specific admission intake.
- Merit type and stage.
- Applicant category and campus context.
- Score, position and value representations.
- Methodology/context text.
- Merit list labels and list numbers.
- Historical/current status and effective dates.
- Selection outcome thresholds and published list context.

## Explicitly excluded

eStudent ranking methodology, predictions, recommendation scoring, applicant private records and AI-generated merit claims.
