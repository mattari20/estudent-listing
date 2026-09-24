# DEV-294 — Public Search Result Lifecycle Safety

## Purpose

Ensure public institute search cards expose counts for current active campuses and active programs only.

## Rule

Search results are public discovery records. Historical or inactive campuses and programs must not inflate current public counts.

## Scope

The institution remains eligible for public search only when the institution itself is active. Its displayed campus and program counts are independently scoped to active child records.

## Safety

This batch changes only derived public result counts. It does not mutate canonical institution, campus, or program records.

## Testing

Regression coverage verifies that one active and one inactive campus/program produce public counts of one campus and one program.
