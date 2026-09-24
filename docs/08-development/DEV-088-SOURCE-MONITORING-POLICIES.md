# DEV-088 — Source Monitoring Policies
Document ID: DEV-088
Status: Implemented

Introduces versionable operational policy records for source monitoring. Policies define scope, freshness windows, priority and source restrictions without making the policy itself a canonical truth rule.

Policies are replaceable and may later become attribute/source-specific. No external crawler or AI provider is required.