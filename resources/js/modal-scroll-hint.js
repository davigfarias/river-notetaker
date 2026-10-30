// Liga/desliga o indicador "role para baixo" conforme sobra conteúdo escondido embaixo. Vale para o
// <dialog> do Flux quando ele rola (flux/modal/index.blade.php) e para qualquer container marcado com
// data-scroll-hint-host que contenha <x-scroll-hint />.
const THRESHOLD = 8;

const update = (dialog) => {
    const hint = [...dialog.querySelectorAll('[data-modal-scroll-hint]')].find((el) => el.closest('dialog, [data-scroll-hint-host]') === dialog);

    if (!hint) {
        return;
    }

    const hiddenBelow = dialog.scrollHeight - dialog.scrollTop - dialog.clientHeight;

    hint.hidden = !((dialog.open ?? dialog.checkVisibility()) && hiddenBelow > THRESHOLD);
};

const watched = new WeakSet();

const watch = (dialog) => {
    if (watched.has(dialog)) {
        return;
    }

    watched.add(dialog);

    const refresh = () => update(dialog);

    dialog.addEventListener('scroll', refresh, { passive: true });
    new ResizeObserver(refresh).observe(dialog);
    new MutationObserver(refresh).observe(dialog, { childList: true, subtree: true, attributes: true, attributeFilter: ['open'] });
    refresh();
};

const scan = () => document.querySelectorAll('dialog[data-modal], [data-scroll-hint-host]').forEach(watch);

document.addEventListener('DOMContentLoaded', scan);
document.addEventListener('livewire:navigated', scan);
document.addEventListener('modal-show', () => requestAnimationFrame(scan));

// O conteúdo de algumas modais só chega depois (Livewire renderiza ao abrir): reescaneia quando o DOM muda.
let scanQueued = false;

new MutationObserver(() => {
    if (!scanQueued) {
        scanQueued = true;
        requestAnimationFrame(() => {
            scanQueued = false;
            scan();
        });
    }
}).observe(document.documentElement, { childList: true, subtree: true });
