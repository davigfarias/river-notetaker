
> ⛔ **DATABASE SAFETY (river-notetaker) — read `DATABASE_SAFETY.md`.** NEVER run `php artisan migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, or `migrate --seed`. NEVER drop, truncate, delete, or overwrite `database/database.sqlite` — it is gitignored, has **no backup**, and holds the user's real work; it has been wiped repeatedly and every wipe cost real data. Use `php artisan migrate` (additive) only. `php artisan test` uses in-memory sqlite and is safe. A full rebuild is the **user's** job, by hand — if one seems necessary, STOP and ask. Enforced by `.claude/hooks/block-db-wipe.sh` + `.claude/settings.json`.

# Checklists for Multi-Step Operations

Use these checklists when performing complex setups. Copy the relevant checklist and track progress as you go.

## Full environment setup (app + database + cache + domain)

```
- [ ] Create or select the application
- [ ] Create the environment
- [ ] Provision the database (`database-cluster:create` then `database:create`)
- [ ] Provision the cache (`cache:create`)
- [ ] Set environment variables (`environment:variables`)
- [ ] Create and attach any secrets (`secret:create` then `environment-secret:attach`)
- [ ] Attach custom domain (`domain:create` then `domain:verify`)
- [ ] Deploy (`deploy` then `deploy:monitor`)
- [ ] Verify the deployment is live
```

## New app from scratch

```
- [ ] Run `cloud ship -n` with required options
- [ ] Monitor deployment status
- [ ] Set environment variables if needed
- [ ] Verify the app is accessible
```

## Adding a database to an existing environment

```
- [ ] Check existing database clusters (`database-cluster:list --json -n`)
- [ ] Create a new cluster if needed (`database-cluster:create --json -n`)
- [ ] Create the database on the cluster (`database:create --json -n`)
- [ ] Update environment variables with connection details if needed
- [ ] Redeploy if the app needs to pick up new config
```

## Adding a cache to an existing environment

```
- [ ] Check existing caches (`cache:list --json -n`)
- [ ] Create the cache (`cache:create --json -n`)
- [ ] Update environment variables with cache connection details if needed
- [ ] Redeploy if the app needs to pick up new config
```

## Custom domain setup

```
- [ ] Create the domain (`domain:create --json -n`)
- [ ] Get DNS records to configure (`domain:get --json -n`)
- [ ] Tell the user to add the DNS records at their registrar
- [ ] Verify the domain (`domain:verify -n`)
- [ ] Confirm verification passed
```
