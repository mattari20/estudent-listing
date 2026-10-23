# DEV-121 — Operator discovery run history

The authenticated operator route `/institutes/operations/discovery-run-history` displays the latest 50 persisted discovery outcomes, newest first, with pagination.

It displays time, outcome, profile, official source link, snapshot ID, candidate count, and review package status. It does not display exception messages or candidate payloads and does not provide approval/promotion actions.

The route is inside the existing `auth` and `institute.operator` group. It depends on the discovery outcome migration having been applied. Hostinger deployment and authenticated browser verification remain separate acceptance steps.
