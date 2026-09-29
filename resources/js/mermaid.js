// Renderiza blocos ```mermaid do Markdown (que o servidor entrega como
// <pre><code class="language-mermaid">) em diagramas SVG. O mermaid é pesado,
// então só é baixado (chunk separado) quando a página tem um bloco.
let mermaidPromise = null;

const loadMermaid = () => (mermaidPromise ??= import('mermaid').then((module) => module.default));

const isDark = () => document.documentElement.classList.contains('dark');

async function draw(nodes) {
    const mermaid = await loadMermaid();

    mermaid.initialize({ startOnLoad: false, securityLevel: 'strict', theme: isDark() ? 'dark' : 'default' });
    await mermaid.run({ nodes, suppressErrors: true });
}

// Troca cada <pre><code class="language-mermaid"> ainda não processado por um <div class="mermaid">.
// Idempotente: depois de trocado o bloco não casa mais no seletor, então rodar de novo não faz nada.
function renderPending(root) {
    const nodes = [...root.querySelectorAll('pre > code.language-mermaid')].map((code) => {
        const diagram = document.createElement('div');

        diagram.className = 'mermaid';
        diagram.dataset.source = code.textContent;
        diagram.textContent = code.textContent;
        code.parentElement.replaceWith(diagram);

        return diagram;
    });

    if (nodes.length) {
        draw(nodes);
    }
}

// O mermaid grava o SVG dentro do nó; pra trocar o tema volta ao fonte e desenha de novo.
function redrawAll(root) {
    const nodes = [...root.querySelectorAll('.mermaid[data-source]')];

    nodes.forEach((node) => {
        node.removeAttribute('data-processed');
        node.textContent = node.dataset.source;
    });

    if (nodes.length) {
        draw(nodes);
    }
}

document.addEventListener('alpine:init', () => {
    // `x-mermaid` num ancestral (o <main> do layout) cobre tudo dentro dele. O
    // MutationObserver pega o que chega depois: morph do Livewire (salvar edição
    // in-place) e conteúdo novo do wire:navigate.
    Alpine.directive('mermaid', (el, _directive, { cleanup }) => {
        const contentObserver = new MutationObserver(() => renderPending(el));
        const themeObserver = new MutationObserver(() => redrawAll(el));

        contentObserver.observe(el, { childList: true, subtree: true });
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        renderPending(el);

        cleanup(() => {
            contentObserver.disconnect();
            themeObserver.disconnect();
        });
    });
});
