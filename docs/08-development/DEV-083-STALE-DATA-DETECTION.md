# DEV-083 — Stale Data Detection
Document ID: DEV-083
Status: Implemented

Stale and aging evidence produces DataQualityEvent records. Explicit expiry takes precedence over generic age thresholds.

Staleness does not mean falsehood. It means the evidence should be reconsidered for current use.