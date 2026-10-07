// Canvas do mapa de conceitos. O estado vive no cliente (nós, setas, pan/zoom) e
// o servidor só persiste cada gesto via $wire (placeNode/connect/disconnect/removeNode).
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.data('conceptMap', ({ concepts, nodes, edges }) => ({
        concepts, // { chave: rótulo }
        nodes, // [{ key, label, x, y, w, h }]
        edges, // [{ from, to }]
        pan: { x: 0, y: 0 },
        scale: 1,
        drag: null, // { type: 'pill'|'node'|'edge'|'pan', ... }
        ghost: null, // { x, y } ponta da seta em criação, em coords do mundo
        newLabel: '',
        editing: null, // chave do nó livre em renomeação
        editText: '',
        selected: null, // 'from>to' da seta selecionada
        linking: null, // chave do nó de origem após clicar no círculo (modo clicar-clicar)

        get pills() {
            const placed = new Set(this.nodes.map((node) => node.key));

            return Object.entries(this.concepts).filter(([key]) => !placed.has(key));
        },

        async addCustom() {
            const label = this.newLabel.trim();

            if (!label) {
                return;
            }

            const rect = this.$refs.canvas.getBoundingClientRect();
            const jitter = (this.nodes.length % 5) * 24;
            const x = Math.round((rect.width / 2 - this.pan.x) / this.scale - 60 + jitter);
            const y = Math.round((rect.height / 2 - this.pan.y) / this.scale - 14 + jitter);
            const key = await this.$wire.addCustomNode(label, x, y);

            if (key) {
                this.nodes.push({ key, label, x, y, custom: true });
                this.newLabel = '';
            }
        },

        startRename(node) {
            this.editing = node.key;
            this.editText = node.label;
            this.$nextTick(() => this.$root.querySelector(`[data-node-key="${node.key}"] input`)?.select());
        },

        commitRename(node) {
            const label = this.editText.trim();

            this.editing = null;

            if (label && label !== node.label) {
                node.label = label;
                this.$wire.renameNode(node.key, label);
            }
        },

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

        edgePointerDown(event) {
            const hit = event.target.closest('[data-edge], [data-bend]');

            if (!hit) {
                return;
            }

            event.stopPropagation();
            this.cancelLink();

            if (hit.dataset.bend !== undefined) {
                this.drag = { type: 'bend', edge: this.edges[hit.dataset.bend] };
                this.$refs.canvas.setPointerCapture(event.pointerId);
            } else {
                const edge = this.edges[hit.dataset.edge];

                this.selected = `${edge.from}>${edge.to}`;
            }
        },

        selectedEdge() {
            return this.edges.find((edge) => `${edge.from}>${edge.to}` === this.selected);
        },

        straightenSelected() {
            const edge = this.selectedEdge();

            if (edge) {
                edge.bend = 0;
                this.$wire.bendEdge(edge.from, edge.to, 0);
            }
        },

        reverseSelected() {
            const edge = this.selectedEdge();

            if (!edge) {
                return;
            }

            const { from, to } = edge;

            this.$wire.reverseEdge(from, to);

            if (this.edges.some((other) => other.from === to && other.to === from)) {
                this.edges = this.edges.filter((other) => other !== edge);
                this.selected = null;
            } else {
                edge.from = to;
                edge.to = from;
                edge.bend = -(edge.bend ?? 0);
                this.selected = `${edge.from}>${edge.to}`;
            }
        },

        removeSelected() {
            const edge = this.selectedEdge();

            if (edge) {
                this.removeEdge(edge);
            }
        },

        ghostLine() {
            const a = this.node(this.drag?.type === 'edge' ? this.drag.key : this.linking);

            if (!a || !this.ghost) {
                return null;
            }

            const p = this.edgePoint(a, this.ghost.x, this.ghost.y);

            return { x1: p.x, y1: p.y, x2: this.ghost.x, y2: this.ghost.y };
        },

        // Pill da lista -> canvas (HTML5 drag and drop).
        dragPill(event, key) {
            event.dataTransfer.setData('text/plain', key);
            event.dataTransfer.effectAllowed = 'move';
        },

        dropPill(event) {
            const key = event.dataTransfer.getData('text/plain');

            if (!(key in this.concepts) || this.node(key)) {
                return;
            }

            const { x, y } = this.toWorld(event);
            const node = { key, label: this.concepts[key], x: Math.round(x - 40), y: Math.round(y - 14), w: 80, h: 28 };

            this.nodes.push(node);
            this.$wire.placeNode(key, node.x, node.y);
        },

        connect(from, to) {
            if (from !== to && !this.edges.some((edge) => edge.from === from && edge.to === to)) {
                this.edges.push({ from, to, bend: 0 });
                this.$wire.connect(from, to);
            }
        },

        cancelLink() {
            this.linking = null;
            this.ghost = null;
        },

        startNode(event, node) {
            event.stopPropagation();

            // Modo clicar-clicar: o segundo clique, em outro nó, fecha a seta.
            if (this.linking) {
                this.connect(this.linking, node.key);
                this.cancelLink();

                return;
            }

            this.drag = { type: 'node', key: node.key, dx: this.toWorld(event).x - node.x, dy: this.toWorld(event).y - node.y };
            event.currentTarget.setPointerCapture(event.pointerId);
        },

        startEdge(event, node) {
            event.stopPropagation();

            // Seta armada: clicar no círculo de outro conceito fecha a ligação.
            if (this.linking) {
                if (this.linking !== node.key) {
                    this.connect(this.linking, node.key);
                }

                this.cancelLink();

                return;
            }

            this.drag = { type: 'edge', key: node.key, sx: event.clientX, sy: event.clientY };
            this.ghost = this.toWorld(event);
            this.$refs.canvas.setPointerCapture(event.pointerId);
        },

        startPan(event) {
            this.cancelLink();
            this.selected = null;
            this.drag = { type: 'pan', sx: event.clientX - this.pan.x, sy: event.clientY - this.pan.y };
            event.currentTarget.setPointerCapture(event.pointerId);
        },

        move(event) {
            const drag = this.drag;

            if (!drag) {
                if (this.linking) {
                    this.ghost = this.toWorld(event);
                }

                return;
            }

            if (drag.type === 'pan') {
                this.pan = { x: event.clientX - drag.sx, y: event.clientY - drag.sy };
            } else if (drag.type === 'node') {
                const node = this.node(drag.key);
                const { x, y } = this.toWorld(event);

                node.x = Math.round(x - drag.dx);
                node.y = Math.round(y - drag.dy);
            } else if (drag.type === 'edge') {
                this.ghost = this.toWorld(event);
            } else if (drag.type === 'bend') {
                const g = this.geometry(drag.edge);
                const { x, y } = this.toWorld(event);

                drag.edge.bend = Math.round((x - g.mid.x) * g.normal.x + (y - g.mid.y) * g.normal.y);
            }
        },

        end(event) {
            const drag = this.drag;

            this.drag = null;
            this.ghost = null;

            if (drag?.type === 'bend') {
                this.$wire.bendEdge(drag.edge.from, drag.edge.to, drag.edge.bend);
            } else if (drag?.type === 'node') {
                const node = this.node(drag.key);

                this.$wire.placeNode(node.key, node.x, node.y);
            } else if (drag?.type === 'edge') {
                const target = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-node-key]')?.dataset.nodeKey;

                if (target && target !== drag.key) {
                    this.connect(drag.key, target);
                } else if (Math.hypot(event.clientX - drag.sx, event.clientY - drag.sy) < 4) {
                    // Foi um clique no círculo: arma a origem e espera o clique no destino.
                    this.linking = drag.key;
                    this.ghost = this.toWorld(event);
                }
            }
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

        removeEdge(edge) {
            this.edges = this.edges.filter((item) => item !== edge);
            this.selected = null;
            this.$wire.disconnect(edge.from, edge.to);
        },

        removeNode(node) {
            this.nodes = this.nodes.filter((item) => item !== node);
            this.edges = this.edges.filter((edge) => edge.from !== node.key && edge.to !== node.key);
            this.$wire.removeNode(node.key);
        },
    }));
});
