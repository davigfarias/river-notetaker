// Liga/desliga o indicador "role para baixo" de cada <dialog> do Flux conforme sobra conteúdo
// escondido embaixo. O próprio <dialog> é o container que rola (ver flux/modal/index.blade.php).
const THRESHOLD = 8;

const update = (dialog) => {
    const hint = dialog.querySelector('[data-modal-scroll-hint]');

    if (!hint) {
        return;
    }

    const hiddenBelow = dialog.scrollHeight - dialog.scrollTop - dialog.clientHeight;

    hint.hidden = !(dialog.open && hiddenBelow > THRESHOLD);
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

const scan = () => document.querySelectorAll('dialog[data-modal]').forEach(watch);

document.addEventListener('DOMContentLoaded', scan);
document.addEventListener('livewire:navigated', scan);
document.addEventListener('modal-show', () => requestAnimationFrame(scan));
