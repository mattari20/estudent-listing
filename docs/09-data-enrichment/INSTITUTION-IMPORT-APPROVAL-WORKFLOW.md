# Institution import review workflow

## Why the queue's Approve button was misleading

Institution identity rows must not use the generic quick-approval endpoint. That endpoint intentionally rejects institution rows because they require an explicit identity-resolution decision. The queue now routes institution candidates to **Review identity** instead of showing the generic Approve button. Bulk approval is labelled for enrichment rows, since institution rows are skipped by that bulk-decision endpoint.

## Correct process for an institution candidate

1. Open the batch and choose **Review identity** / **Review details**.
2. Check the normalized institution name, source URL/evidence, identity candidates, and open conflicts.
3. If it is genuinely new, select **Approve new institution** and enter a reason.
4. If it matches an existing canonical institution, select **Approve existing match**, provide the canonical institution ID, and enter a reason.
5. If it is a duplicate or unsupported candidate, reject it with a reason or mark the conflict for investigation.
6. After an approved identity decision, use **Promote approved institution** as a separate action. Promotion creates/links the canonical record and is the step that makes it eligible to appear in the public directory.
7. Verify the canonical institution page and the public directory after promotion.

## Important status interpretation

An approved decision can still be counted in the review queue until promotion. This is intentional: approval and publication are separate states. Do not repeatedly submit approval because the pending queue count did not fall. Check the row's latest identity decision and promotion history instead.

## Safety

This UI change does not approve, promote, or alter any existing production import row. It changes which action is presented in the queue. Operators must still review identity and source evidence before deciding. Do not use the bulk approve-and-promote action on an unreviewed batch.
