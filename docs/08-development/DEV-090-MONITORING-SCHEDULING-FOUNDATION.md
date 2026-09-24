# DEV-090 — Monitoring Scheduling Foundation
Document ID: DEV-090
Status: Implemented

The monitoring command is designed for scheduler execution and supports single-policy execution for controlled operations. Scheduling cadence is intentionally not hard-coded as a business truth; deployment may invoke the command through the Laravel scheduler or an external scheduler.

A future scheduling layer can add policy-specific cadence without changing canonical data governance.