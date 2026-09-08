
> ⛔ **DATABASE SAFETY (river-notetaker) — read `DATABASE_SAFETY.md`.** NEVER run `php artisan migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, or `migrate --seed`. NEVER drop, truncate, delete, or overwrite `database/database.sqlite` — it is gitignored, has **no backup**, and holds the user's real work; it has been wiped repeatedly and every wipe cost real data. Use `php artisan migrate` (additive) only. `php artisan test` uses in-memory sqlite and is safe. A full rebuild is the **user's** job, by hand — if one seems necessary, STOP and ask. Enforced by `.claude/hooks/block-db-wipe.sh` + `.claude/settings.json`.

# Mail Best Practices

## Implement `ShouldQueue` on the Mailable Class

Makes queueing the default regardless of how the mailable is dispatched. No need to remember `Mail::queue()` at every call site — `Mail::send()` also queues it.

## Use `afterCommit()` on Mailables Inside Transactions

A queued mailable dispatched inside a transaction may process before the commit. Use `$this->afterCommit()` in the constructor.

## Use `assertQueued()` Not `assertSent()` for Queued Mailables

`Mail::assertSent()` only catches synchronous mail. Queued mailables fail `assertSent` with a "Did you mean to use assertQueued()?" hint.

Incorrect: `Mail::assertSent(OrderShipped::class);` when mailable implements `ShouldQueue`.

Correct: `Mail::assertQueued(OrderShipped::class);`

## Use Markdown Mailables for Transactional Emails

Markdown mailables auto-generate both HTML and plain-text versions, use responsive components, and allow global style customization. Generate with `--markdown` flag.

## Separate Content Tests from Sending Tests

Content tests: instantiate the mailable directly, call `assertSeeInHtml()`.
Sending tests: use `Mail::fake()` and `assertSent()`/`assertQueued()`.
Don't mix them — it conflates concerns and makes tests brittle.
