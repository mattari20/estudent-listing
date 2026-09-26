# GitHub → Hostinger Deployment and Autonomous Bootstrap

## Deployment model

The institute directory uses GitHub as the source of truth for application code.

1. Changes are committed and merged to `main`.
2. Hostinger Git deployment pulls the `main` branch automatically.
3. Hostinger runs its configured Laravel scheduler.
4. The scheduler runs `institutes:bootstrap-agent` and `institutes:monitor-changes`.
5. The first bootstrap run registers configured official sources and creates the first governed discovery batch when no snapshot exists.
6. Later monitoring runs retrieve due sources and create governed candidate batches only when source content changes.
7. Canonical institute data is never overwritten by discovery.

## Hostinger setup

Hostinger's Git deployment can connect to GitHub through OAuth and automatically deploy updates merged into the selected branch.

Configure the Laravel application in the Hostinger Git deployment target and keep the production `.env` outside GitHub.

Create one Hostinger Cron Job for the Laravel scheduler. Use Hostinger's PHP/custom cron configuration to execute:

```text
/usr/bin/php /home/USER/domains/DOMAIN/public_html/artisan schedule:run
```

Replace `USER` and `DOMAIN` with the values for the actual deployment path.

The repository already defines the Laravel schedule. The cron job only needs to invoke `schedule:run`; no discovery-specific Bash command is required.

## First deployment

After the application and database are deployed, the first scheduler execution will:

- register the configured CUI Lahore official source;
- create its automated-discovery monitoring policy;
- fetch and snapshot the source if no snapshot exists;
- create governed candidate program rows;
- leave canonical program data unchanged.

If the parent institution does not exist yet, bootstrap stops safely and reports the missing parent requirement.

## Safety

- Secrets stay in Hostinger environment configuration and are never committed.
- Discovery remains non-canonical.
- Existing canonical records are protected by duplicate detection and promotion guards.
- Bootstrap is idempotent: it does not create a second initial discovery when a source snapshot already exists.
- The normal operating path is GitHub → Hostinger deployment → Hostinger scheduler → governed database workflow.
