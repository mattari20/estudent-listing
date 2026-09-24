# DEV-089 — Automated Source Monitoring
Document ID: DEV-089
Status: Implemented

Adds a repeatable monitoring service and Artisan command. Monitoring evaluates governed evidence freshness and queues aging/stale evidence for reverification.

The monitor reports operational counts and never silently changes canonical records.