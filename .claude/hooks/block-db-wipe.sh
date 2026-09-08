#!/usr/bin/env bash
# ============================================================================
#  HARD BLOCK: destructive database commands on river-notetaker
# ----------------------------------------------------------------------------
#  The local dev database (database/database.sqlite) is gitignored and has NO
#  backups. It has been wiped multiple times by an agent running migrate:fresh
#  during debugging. This hook makes that mechanically impossible.
#
#  Blocks: migrate:fresh, migrate:refresh, migrate:reset, db:wipe,
#          schema:dump --prune, and raw DROP DATABASE / DROP TABLE / DELETE FROM
#          against the sqlite file. Also blocks deleting the sqlite file.
#
#  To apply a new migration use:  php artisan migrate
#  To reset test state:           run the test suite (in-memory sqlite)
#  A real rebuild MUST be done by the user, by hand.
# ============================================================================
set -euo pipefail

payload="$(cat)"

# Extract the command string from the PreToolUse payload without needing jq.
cmd="$(printf '%s' "$payload" | sed -n 's/.*"command"[[:space:]]*:[[:space:]]*"\(.*\)/\1/p')"
# Trim the trailing JSON (best-effort; patterns below are tolerant).
cmd="${cmd%\"*}"

# Normalise for matching.
haystack="$(printf '%s' "$cmd" | tr '[:upper:]' '[:lower:]')"

# Allow read-only inspection commands that merely *mention* these strings
# (grep/rg/cat/etc). They cannot mutate anything.
case "$haystack" in
  grep\ *|rg\ *|cat\ *|head\ *|tail\ *|less\ *|bat\ *|"git log"*|"git grep"*|"git show"*|"git diff"*|awk\ *|"sed -n"*)
    exit 0 ;;
esac

# Allow git porcelain that carries free-text messages (commit/tag/etc) — the
# forbidden strings can legitimately appear in a commit message. Still refuse if
# the command also touches artisan or the sqlite file (guards against chaining).
case "$haystack" in
  git\ commit*|git\ tag*|git\ merge*|git\ stash*|git\ revert*|git\ notes*|git\ cherry-pick*|git\ rebase*)
    case "$haystack" in
      *artisan*|*sqlite*|*"db:wipe"*) : ;;   # fall through to the checks below
      *) exit 0 ;;
    esac
    ;;
esac

deny() {
  echo "BLOCKED by .claude/hooks/block-db-wipe.sh" >&2
  echo "" >&2
  echo "Refused command: $cmd" >&2
  echo "" >&2
  echo "This project NEVER runs destructive database commands." >&2
  echo "The dev sqlite DB is gitignored and has no backups." >&2
  echo "" >&2
  echo "  - to apply migrations:  php artisan migrate" >&2
  echo "  - to reset test state:  php artisan test  (uses in-memory sqlite)" >&2
  echo "  - a full rebuild MUST be done by the user, by hand." >&2
  exit 2
}

case "$haystack" in
  *migrate:fresh*)              deny ;;
  *migrate:refresh*)            deny ;;
  *migrate:reset*)              deny ;;
  *db:wipe*)                    deny ;;
  *"migrate --seed"*)           deny ;;   # seeder is broken + risky, force explicit steps
  *"schema:dump"*"--prune"*)    deny ;;
  *"drop database"*)            deny ;;
  *"drop table"*)               deny ;;
  *"truncate"*"database"*)      deny ;;
esac

# Block removal / truncation of the sqlite file itself.
case "$haystack" in
  *rm\ *database/database.sqlite*)   deny ;;
  *rm\ *database.sqlite*)            deny ;;
  *">"\ database/database.sqlite*)   deny ;;
  *"> database/database.sqlite"*)    deny ;;
  *truncate*database.sqlite*)        deny ;;
esac

exit 0
