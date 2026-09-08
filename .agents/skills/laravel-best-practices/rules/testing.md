
> ⛔ **DATABASE SAFETY (river-notetaker) — read `DATABASE_SAFETY.md`.** NEVER run `php artisan migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, or `migrate --seed`. NEVER drop, truncate, delete, or overwrite `database/database.sqlite` — it is gitignored, has **no backup**, and holds the user's real work; it has been wiped repeatedly and every wipe cost real data. Use `php artisan migrate` (additive) only. `php artisan test` uses in-memory sqlite and is safe. A full rebuild is the **user's** job, by hand — if one seems necessary, STOP and ask. Enforced by `.claude/hooks/block-db-wipe.sh` + `.claude/settings.json`.

# Testing Best Practices

## Use `LazilyRefreshDatabase` Over `RefreshDatabase`

`RefreshDatabase` migrates once per process and wraps each test in a rolled-back transaction. `LazilyRefreshDatabase` skips even that first migration if the schema is already up to date.

## Use Model Assertions Over Raw Database Assertions

Incorrect: `$this->assertDatabaseHas('users', ['id' => $user->id]);`

Correct: `$this->assertModelExists($user);`

More expressive, type-safe, and fails with clearer messages.

## Use Factory States and Sequences

Named states make tests self-documenting. Sequences eliminate repetitive setup.

Incorrect: `User::factory()->create(['email_verified_at' => null]);`

Correct: `User::factory()->unverified()->create();`

## Use `Exceptions::fake()` to Assert Exception Reporting

Instead of `withoutExceptionHandling()`, use `Exceptions::fake()` to assert the correct exception was reported while the request completes normally.

## Call `Event::fake()` After Factory Setup

Model factories rely on model events (e.g., `creating` to generate UUIDs). Calling `Event::fake()` before factory calls silences those events, producing broken models.

Incorrect: `Event::fake(); $user = User::factory()->create();`

Correct: `$user = User::factory()->create(); Event::fake();`

## Use `recycle()` to Share Relationship Instances Across Factories

Without `recycle()`, nested factories create separate instances of the same conceptual entity.

```php
Ticket::factory()
    ->recycle(Airline::factory()->create())
    ->create();
```
