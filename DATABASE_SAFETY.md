# ⛔ DATABASE SAFETY — river-notetaker

> This file exists because the local development database has been wiped multiple
> times by an AI agent running destructive Artisan commands during debugging.
> Every wipe cost the user real, unrecoverable work. Read this before touching
> anything database-related.

## The rule

**NEVER run a command that drops, resets, or rebuilds the database.**
Not once. Not for debugging. Not "just to get a clean slate". Not silently as
part of a chained command.

### Forbidden — never run, never chain, never suggest

| Command | Why it's banned |
| --- | --- |
| `php artisan migrate:fresh` | drops every table |
| `php artisan migrate:refresh` | drops every table |
| `php artisan migrate:reset` | rolls back every migration |
| `php artisan db:wipe` | drops every table |
| `php artisan migrate --seed` | runs the (broken) seeder against the real DB |
| `php artisan schema:dump --prune` | deletes migration files |
| raw `DROP DATABASE` / `DROP TABLE` / `TRUNCATE` / `DELETE FROM` on the sqlite DB | destroys data |
| `rm database/database.sqlite`, `> database/database.sqlite`, `truncate` it | destroys the file |

### Allowed

| Command | Notes |
| --- | --- |
| `php artisan migrate` | additive only — applies pending migrations |
| `php artisan migrate:rollback` | **only** when the user explicitly asks |
| `php artisan test` / `php artisan test --compact` | tests use in-memory sqlite (`:memory:`), they never touch the dev DB |
| `php artisan make:migration` | fine |

## Why there is no recovery

- `database/database.sqlite` is **gitignored** (`database/.gitignore` → `*.sqlite*`).
- There is **no backup**: no git history, no stash, no Time Machine, no APFS
  user snapshot.
- `migrate:fresh` effectively vacuums the file, so deleted pages are not
  recoverable either.

## If a rebuild genuinely seems necessary

**Stop. Do not do it.** Tell the user what you think needs to happen and let them
run it by hand. The user maintains this database themselves.

## Notes

- Login is a 4-digit access-token code at `/entrar`
  (`AccessToken.token` = `hash('sha256', $code)`).
- The `DatabaseSeeder` is currently broken anyway: `NoteFactory` calls
  `Tags::pluck('title')` but there is no `tags` table (`tags` is a JSON column on
  `notes`). So `migrate:fresh --seed` would not even restore a usable state.

## Enforcement

This rule is enforced mechanically, not just by convention:

1. `.claude/hooks/block-db-wipe.sh` — a `PreToolUse` hook that inspects every
   Bash command and hard-blocks (exit 2) anything matching the forbidden list.
2. `.claude/settings.json` — `permissions.deny` entries for the same
   commands.
3. This file, `CLAUDE.md` (top and bottom), and a banner in every `SKILL.md` and
   rules file under `.claude/skills/`, `.agents/skills/`, `.ai/skills/`.

The redundancy is deliberate. Do not remove any of it.
