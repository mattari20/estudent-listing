# DEV-316 — Production Validation Checklist

Use this checklist during the final release validation. It records procedure only; completion requires execution in the target environment.

- [ ] Environment configuration verified
- [ ] Composer dependencies installed
- [ ] Lint passes
- [ ] Full PHPUnit suite passes
- [ ] Database migrations complete
- [ ] Foreign keys and required constraints verified
- [ ] Selected import batch passes verification
- [ ] Source readiness passes
- [ ] Provenance readiness passes
- [ ] Promotion readiness passes
- [ ] Final readiness passes
- [ ] Deployment readiness passes
- [ ] Readiness contract passes
- [ ] End-to-end readiness passes
- [ ] Public institute listing smoke test passes
- [ ] Public search smoke test passes
- [ ] Institution profile smoke test passes
- [ ] Sitemap and robots checks pass
- [ ] Public API checks pass
- [ ] Nearby discovery smoke test passes
- [ ] Controlled promotion completed
- [ ] Post-release monitoring checks pass
- [ ] Rollback path confirmed

**Important:** Do not mark a checkbox complete based on code inspection alone. It must be verified in the target environment.
