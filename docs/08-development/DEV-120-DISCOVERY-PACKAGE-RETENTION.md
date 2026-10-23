# DEV-120 — Discovery review package retention

The `institutes:prune-discovery-review-packages` command manages old private discovery review packages.

- Default retention is 90 days.
- Default execution is dry-run and lists only eligible package directories.
- Only directories matching `institute-discovery-review-packages/snapshot-{positive integer}` with both the CSV and manifest present are considered.
- Age is based on the manifest file's last-modified timestamp.
- Deletion requires explicit `--apply`; `--days=N` can override retention, and N must be positive.
- The command does not alter database rows, snapshots, evidence, or canonical institute data.

Examples:

- `php artisan institutes:prune-discovery-review-packages` — report packages older than 90 days.
- `php artisan institutes:prune-discovery-review-packages --days=120` — report packages older than 120 days.
- `php artisan institutes:prune-discovery-review-packages --days=90 --apply` — delete eligible package directories.

Run the dry-run first and review the list before any deletion. No cleanup is scheduled automatically by this change.
