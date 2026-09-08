
> ⛔ **DATABASE SAFETY (river-notetaker) — read `DATABASE_SAFETY.md`.** NEVER run `php artisan migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, or `migrate --seed`. NEVER drop, truncate, delete, or overwrite `database/database.sqlite` — it is gitignored, has **no backup**, and holds the user's real work; it has been wiped repeatedly and every wipe cost real data. Use `php artisan migrate` (additive) only. `php artisan test` uses in-memory sqlite and is safe. A full rebuild is the **user's** job, by hand — if one seems necessary, STOP and ask. Enforced by `.claude/hooks/block-db-wipe.sh` + `.claude/settings.json`.

# Events & Notifications Best Practices

## Rely on Event Discovery

Laravel auto-discovers listeners by reading `handle(EventType $event)` type-hints. No manual registration needed in `AppServiceProvider`.

## Run `event:cache` in Production Deploy

Event discovery scans the filesystem per-request in dev. Cache it in production: `php artisan optimize` or `php artisan event:cache`.

## Use `ShouldDispatchAfterCommit` Inside Transactions

Without it, a queued listener may process before the DB transaction commits, reading data that doesn't exist yet.

```php
class OrderShipped implements ShouldDispatchAfterCommit {}
```

## Always Queue Notifications

Notifications often hit external APIs (email, SMS, Slack). Without `ShouldQueue`, they block the HTTP response.

```php
class InvoicePaid extends Notification implements ShouldQueue
{
    use Queueable;
}
```

## Use `afterCommit()` on Notifications in Transactions

Same race condition as events — call `afterCommit()` to delay dispatch until the transaction commits.

```php
$user->notify((new InvoicePaid($invoice))->afterCommit());
```

## Route Notification Channels to Dedicated Queues

Mail and database notifications have different priorities. Use `viaQueues()` to route them to separate queues.

## Use On-Demand Notifications for Non-User Recipients

Avoid creating dummy models to send notifications to arbitrary addresses.

```php
Notification::route('mail', 'admin@example.com')->notify(new SystemAlert());
```

## Implement `HasLocalePreference` on Notifiable Models

Laravel automatically uses the user's preferred locale for all notifications and mailables — no per-call `locale()` needed.
