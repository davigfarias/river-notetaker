// Geometria e navegação comuns aos canvas de mapa (mapa-conceitos.js e mapa-da-nota.js).
// Espalhado (...mapCanvas) dentro do Alpine.data; espera `nodes`, `edges`, `pan`, `scale`,
// `drag`, `ghost`, `linking`, `selected` e o x-ref `canvas`.
export const mapCanvas = {
    node(key) {
        return this.nodes.find((node) => node.key === key);
    },

    measure(node, el) {
        node.w = el.offsetWidth;
        node.h = el.offsetHeight;
    },

    toWorld(event) {
        const rect = this.$refs.canvas.getBoundingClientRect();

        return { x: (event.clientX - rect.left - this.pan.x) / this.scale, y: (event.clientY - rect.top - this.pan.y) / this.scale };
    },

    // Ponto na borda do retângulo do nó, na direção de (tx, ty).
    edgePoint(node, tx, ty) {
        const cx = node.x + (node.w ?? 0) / 2;
        const cy = node.y + (node.h ?? 0) / 2;
        const dx = tx - cx;
        const dy = ty - cy;

        if (dx === 0 && dy === 0) {
            return { x: cx, y: cy };
        }

        const k = Math.min(dx ? (node.w / 2) / Math.abs(dx) : Infinity, dy ? (node.h / 2) / Math.abs(dy) : Infinity);

        return { x: cx + dx * k, y: cy + dy * k };
    },

    // Seta como curva quadrática: o controle fica a 2*bend do meio da corda (a curva passa a `bend`).
    geometry(edge) {
        const a = this.node(edge.from);
        const b = this.node(edge.to);

        if (!a || !b) {
            return null;
        }

        const ca = { x: a.x + (a.w ?? 0) / 2, y: a.y + (a.h ?? 0) / 2 };
        const cb = { x: b.x + (b.w ?? 0) / 2, y: b.y + (b.h ?? 0) / 2 };
        const len = Math.hypot(cb.x - ca.x, cb.y - ca.y) || 1;
        const mid = { x: (ca.x + cb.x) / 2, y: (ca.y + cb.y) / 2 };
        const normal = { x: -(cb.y - ca.y) / len, y: (cb.x - ca.x) / len };
        const bend = edge.bend ?? 0;
        const ctrl = { x: mid.x + normal.x * 2 * bend, y: mid.y + normal.y * 2 * bend };
        const p = this.edgePoint(a, ctrl.x, ctrl.y);
        const q = this.edgePoint(b, ctrl.x, ctrl.y);

        return {
            p, q, ctrl, mid, normal,
            handle: { x: 0.25 * p.x + 0.5 * ctrl.x + 0.25 * q.x, y: 0.25 * p.y + 0.5 * ctrl.y + 0.25 * q.y },
        };
    },

    edgesMarkup() {
        const fmt = (n) => Math.round(n * 10) / 10;

        return this.edges.map((edge, i) => {
            const g = this.geometry(edge);

            if (!g) {
                return '';
            }

            const active = this.selected === `${edge.from}>${edge.to}`;
            const d = `M${fmt(g.p.x)} ${fmt(g.p.y)} Q${fmt(g.ctrl.x)} ${fmt(g.ctrl.y)} ${fmt(g.q.x)} ${fmt(g.q.y)}`;

            return `<path d="${d}" fill="none" stroke="currentColor" stroke-width="${active ? 3 : 2}" marker-end="url(#seta)" />`
                + `<path d="${d}" fill="none" stroke="transparent" stroke-width="16" data-edge="${i}" style="pointer-events: stroke; cursor: pointer" />`
                + (active ? `<circle cx="${fmt(g.handle.x)}" cy="${fmt(g.handle.y)}" r="7" fill="white" stroke="currentColor" stroke-width="2" data-bend="${i}" style="pointer-events: all; cursor: grab" />` : '');
        }).join('');
    },

    selectedEdge() {
        return this.edges.find((edge) => `${edge.from}>${edge.to}` === this.selected);
    },

    ghostLine() {
        const a = this.node(this.drag?.type === 'edge' ? this.drag.key : this.linking);

        if (!a || !this.ghost) {
            return null;
        }

        const p = this.edgePoint(a, this.ghost.x, this.ghost.y);

        return { x1: p.x, y1: p.y, x2: this.ghost.x, y2: this.ghost.y };
    },

    cancelLink() {
        this.linking = null;
        this.ghost = null;
    },

    zoom(event) {
        const rect = this.$refs.canvas.getBoundingClientRect();
        const next = Math.min(3, Math.max(0.3, this.scale * (event.deltaY < 0 ? 1.1 : 1 / 1.1)));
        const mx = event.clientX - rect.left;
        const my = event.clientY - rect.top;

        // Mantém o ponto sob o cursor fixo ao dar zoom.
        this.pan = { x: mx - ((mx - this.pan.x) / this.scale) * next, y: my - ((my - this.pan.y) / this.scale) * next };
        this.scale = next;
    },
};
