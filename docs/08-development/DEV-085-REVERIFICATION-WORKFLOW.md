# DEV-085 — Reverification Workflow
Document ID: DEV-085
Status: Implemented

Open freshness items can enter an authenticated operator review state and later be resolved or superseded. Every transition is durable and records actor, reason and timestamps.

Reverification does not imply that the underlying assertion is false; it identifies work required to establish current evidence.