/**
 * Rastreia edições não salvas na página de criar nota — 100% client-side,
 * sem nenhuma requisição extra ao servidor.
 *
 * Tira um snapshot de `$wire.notes` no carregamento e recalcula "dirty" sempre
 * que `notes` muda. Isso cobre tanto a digitação deferida (o proxy reativo do
 * Alpine muda na hora) quanto as ações `wire:click` (o merge da resposta do
 * servidor também dispara o watch) — que o `$wire.$dirty()` nativo perde depois
 * do round-trip.
 */
const original = JSON.stringify($wire.notes)

const recheck = () => {
    Alpine.store('noteDraft').dirty = JSON.stringify($wire.notes) !== original
}

recheck()
$wire.$watch('notes', recheck)

if (! window.__noteBeforeUnloadBound) {
    window.__noteBeforeUnloadBound = true

    window.addEventListener('beforeunload', (e) => {
        if (Alpine.store('noteDraft').dirty) {
            e.preventDefault()
            e.returnValue = ''
        }
    })
}
