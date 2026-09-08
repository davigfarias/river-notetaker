
> ⛔ **DATABASE SAFETY (river-notetaker) — read `DATABASE_SAFETY.md`.** NEVER run `php artisan migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, or `migrate --seed`. NEVER drop, truncate, delete, or overwrite `database/database.sqlite` — it is gitignored, has **no backup**, and holds the user's real work; it has been wiped repeatedly and every wipe cost real data. Use `php artisan migrate` (additive) only. `php artisan test` uses in-memory sqlite and is safe. A full rebuild is the **user's** job, by hand — if one seems necessary, STOP and ask. Enforced by `.claude/hooks/block-db-wipe.sh` + `.claude/settings.json`.

# Livewire 4 JavaScript Integration

## Interceptor System (v4)

### Intercept Messages

```js
Livewire.interceptMessage(({ component, message, onFinish, onSuccess, onError }) => {
    onFinish(() => { /* After response, before processing */ });
    onSuccess(({ payload }) => { /* payload.snapshot, payload.effects */ });
    onError(() => { /* Server errors */ });
});
```

### Intercept Requests

```js
Livewire.interceptRequest(({ request, onResponse, onSuccess, onError, onFailure }) => {
    onResponse(({ response }) => { /* When received */ });
    onSuccess(({ response, responseJson }) => { /* Success */ });
    onError(({ response, responseBody, preventDefault }) => { /* 4xx/5xx */ });
    onFailure(({ error }) => { /* Network failures */ });
});
```

### Component-Scoped Interceptors

```blade
<script>
    this.$intercept('save', ({ component, onSuccess }) => {
        onSuccess(() => console.log('Saved!'));
    });
</script>
```

## Magic Properties

- `$errors` - Access validation errors from JavaScript
- `$intercept` - Component-scoped interceptors
