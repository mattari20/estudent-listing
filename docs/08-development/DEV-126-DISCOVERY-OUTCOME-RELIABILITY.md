# DEV-126 — Discovery outcome reliability

This batch adds regression coverage for two non-mutating outcome branches:
- An unchanged observation records `no_change` without invoking candidate discovery.
- A changed observation with no usable snapshot records `empty_snapshot`.

The hourly monitoring schedule is configured in `bootstrap/app.php`. The monitoring pipeline only calls discovery when the observation state is `changed`; unchanged source checks do not currently enter `SourceChangeDiscoveryService` through that pipeline. The test calls the service directly to lock down its outcome behavior.

Live Hostinger scheduler execution, deployed migration state, and operator download headers remain external acceptance checks. This batch does not invoke production commands or modify canonical records.
