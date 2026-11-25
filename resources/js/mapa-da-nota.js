import { mapCanvas } from './map-canvas';

// Mapa mental livre da nota. O estado inteiro vive no cliente e vai para o servidor
// como um único JSON (App\Support\MindMap valida):
//  - mode 'draft' (tela Nova nota): só escreve em $wire.mindMap, que segue no Salvar;
//  - mode 'live' (página da nota): chama $wire.saveMindMap(...) com debounce.
// Teclado estilo XMind: Tab = filho, Enter = irmão, F2 = renomear, Delete = apagar,
// setas = navegar, Ctrl/Cmd+Z = desfazer.
const GAP_X = 60;
const GAP_Y = 16;
const UNDO_LIMIT = 50;

const newKey = () => Math.random().toString(36).slice(2, 12).padEnd(10, '0');

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.data('noteMindMap', ({ map, mode }) => ({
        ...mapCanvas,
        nodes: (map?.nodes ?? []).map((node) => ({ ...node })), // [{ key, label, x, y, w, h }]
        edges: (map?.edges ?? []).map((edge) => ({ ...edge })), // [{ from, to, bend }]
        pan: { x: 40, y: 40 },
        scale: 1,
        drag: null,
        ghost: null,
        linking: null,
        selected: null, // 'from>to' da seta selecionada
        current: null, // chave do nó selecionado
        editing: null, // chave do nó em edição
        editText: '',
        freshKey: null, // nó recém-criado: Esc ou texto vazio o desfaz
        confirmingDelete: null, // { key, count }
        fullscreen: false,
        status: 'idle', // idle | saving | saved | error
        undoStack: [],
        saveTimer: null,

        init() {
            // Mapa vindo do servidor sem posição medida: centraliza o primeiro nó na tela.
            this.$nextTick(() => this.centerOn(this.nodes[0]));
        },

        destroy() {
            if (this.saveTimer) {
                clearTimeout(this.saveTimer);
                this.flush();
            }
        },

        snapshot() {
            return {
                nodes: this.nodes.map(({ key, label, x, y }) => ({ key, label, x, y })),
                edges: this.edges.map(({ from, to, bend }) => ({ from, to, bend: bend ?? 0 })),
            };
        },

        // Guarda o estado anterior para o Desfazer; chamar ANTES de cada mudança.
        remember() {
            this.undoStack.push(JSON.stringify(this.snapshot()));

            if (this.undoStack.length > UNDO_LIMIT) {
                this.undoStack.shift();
            }
        },

        undo() {
            const previous = this.undoStack.pop();

            if (!previous) {
                return;
            }

            const state = JSON.parse(previous);

            this.editing = null;
            this.freshKey = null;
            this.confirmingDelete = null;
            this.selected = null;
            this.nodes = state.nodes;
            this.edges = state.edges;

            if (!this.node(this.current)) {
                this.current = null;
            }

            this.persist();
        },

        persist() {
            if (mode === 'draft') {
                this.$wire.mindMap = this.snapshot();

                return;
            }

            this.status = 'saving';
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.flush(), 600);
        },

        async flush() {
            this.saveTimer = null;

            try {
                await this.$wire.saveMindMap(this.snapshot());
                this.status = this.saveTimer ? 'saving' : 'saved';
            } catch {
                this.status = 'error';
            }
        },

        focusCanvas() {
            this.$refs.canvas.focus({ preventScroll: true });
        },

        centerOn(node) {
            if (!node) {
                return;
            }

            const rect = this.$refs.canvas.getBoundingClientRect();

            this.pan = { x: rect.width / 2 - (node.x + (node.w ?? 100) / 2) * this.scale, y: rect.height / 2 - (node.y + (node.h ?? 30) / 2) * this.scale };
        },

        // Rola o canvas só o necessário para o nó ficar visível.
        reveal(node) {
            const rect = this.$refs.canvas.getBoundingClientRect();
            const left = node.x * this.scale + this.pan.x;
            const top = node.y * this.scale + this.pan.y;
            const width = (node.w ?? 120) * this.scale;
            const height = (node.h ?? 32) * this.scale;
            const margin = 24;

            if (left < margin) {
                this.pan.x += margin - left;
            } else if (left + width > rect.width - margin) {
                this.pan.x -= left + width - (rect.width - margin);
            }

            if (top < margin) {
                this.pan.y += margin - top;
            } else if (top + height > rect.height - margin) {
                this.pan.y -= top + height - (rect.height - margin);
            }
        },

        parentOf(key) {
            return this.edges.find((edge) => edge.to === key)?.from ?? null;
        },

        childrenOf(key) {
            return this.edges.filter((edge) => edge.from === key).map((edge) => this.node(edge.to)).filter(Boolean);
        },

        // Nó e tudo que sai dele pelas setas (protegido contra ciclos).
        subtree(key) {
            const seen = new Set([key]);
            const queue = [key];

            while (queue.length) {
                const from = queue.shift();

                for (const edge of this.edges) {
                    if (edge.from === from && !seen.has(edge.to)) {
                        seen.add(edge.to);
                        queue.push(edge.to);
                    }
                }
            }

            return seen;
        },

        addNode(x, y, parentKey = null) {
            this.remember();

            const node = { key: newKey(), label: '', x: Math.round(x), y: Math.round(y), w: 120, h: 32 };

            this.nodes.push(node);

            if (parentKey) {
                this.edges.push({ from: parentKey, to: node.key, bend: 0 });
            }

            this.current = node.key;
            this.selected = null;
            this.freshKey = node.key;
            this.startEdit(node, '');
            this.$nextTick(() => this.reveal(node));
        },

        addRoot() {
            const rect = this.$refs.canvas.getBoundingClientRect();
            const jitter = (this.nodes.length % 5) * 24;

            this.addNode((rect.width / 2 - this.pan.x) / this.scale - 60 + jitter, (rect.height / 2 - this.pan.y) / this.scale - 16 + jitter);
        },

        addChild() {
            const parent = this.node(this.current);

            if (!parent) {
                this.addRoot();

                return;
            }

            const siblings = this.childrenOf(parent.key);
            const last = siblings.reduce((low, node) => (!low || node.y > low.y ? node : low), null);
            const x = last ? last.x : parent.x + (parent.w ?? 120) + GAP_X;
            const y = last ? last.y + (last.h ?? 32) + GAP_Y : parent.y;

            this.addNode(x, y, parent.key);
        },

        addSibling() {
            const node = this.node(this.current);

            if (!node) {
                this.addRoot();

                return;
            }

            const parentKey = this.parentOf(node.key);
            const siblings = parentKey ? this.childrenOf(parentKey) : [node];
            const lowest = siblings.reduce((low, other) => (other.y > low.y ? other : low), node);

            this.addNode(node.x, lowest.y + (lowest.h ?? 32) + GAP_Y, parentKey);
        },

        startEdit(node, text = node.label) {
            this.editing = node.key;
            this.editText = text;
            this.$nextTick(() => {
                const input = this.$root.querySelector(`[data-node-key="${node.key}"] input`);

                input?.focus();
                input?.select();
            });
        },

        // Fecha a edição. Devolve false se o nó novo ficou vazio (e foi descartado).
        commitEdit() {
            const node = this.node(this.editing);
            const label = this.editText.replace(/\s+/g, ' ').trim().slice(0, 200);
            const fresh = this.freshKey === this.editing;

            this.editing = null;
            this.freshKey = null;

            if (!node) {
                return false;
            }

            if (!label) {
                if (fresh) {
                    this.discard(node);

                    return false;
                }

                return true;
            }

            if (label !== node.label) {
                if (!fresh) {
                    this.remember();
                }

                node.label = label;
                this.persist();
            }

            return true;
        },

        // Esc na edição: nó novo some; nó existente volta ao texto anterior.
        cancelEdit() {
            const node = this.node(this.editing);
            const fresh = this.freshKey === this.editing;

            this.editing = null;
            this.freshKey = null;

            if (node && fresh) {
                this.discard(node);
            }

            this.focusCanvas();
        },

        // Tira um nó novo, nunca salvo, sem deixar rastro no Desfazer.
        discard(node) {
            const parentKey = this.parentOf(node.key);

            this.nodes = this.nodes.filter((item) => item !== node);
            this.edges = this.edges.filter((edge) => edge.from !== node.key && edge.to !== node.key);
            this.undoStack.pop();
            this.current = parentKey;
        },

        editKeydown(event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                this.cancelEdit();
            } else if (event.key === 'Enter' && !event.isComposing) {
                event.preventDefault();
                this.commitEdit();
                this.focusCanvas();
            } else if (event.key === 'Tab') {
                event.preventDefault();

                if (this.commitEdit()) {
                    this.addChild();
                } else {
                    this.focusCanvas();
                }
            }
        },

        editBlur() {
            if (this.editing) {
                this.commitEdit();
            }
        },

        askDelete() {
            const node = this.node(this.current);

            if (!node) {
                return;
            }

            const count = this.subtree(node.key).size - 1;

            if (count === 0) {
                this.deleteNodes(node.key);
            } else {
                this.confirmingDelete = { key: node.key, label: node.label, count };
            }
        },

        deleteNodes(key) {
            const doomed = this.subtree(key);
            const parentKey = this.parentOf(key);

            this.remember();
            this.nodes = this.nodes.filter((node) => !doomed.has(node.key));
            this.edges = this.edges.filter((edge) => !doomed.has(edge.from) && !doomed.has(edge.to));
            this.confirmingDelete = null;
            this.current = doomed.has(parentKey) ? null : parentKey;
            this.persist();
            this.focusCanvas();
        },

        // Vai para o nó mais próximo na direção da seta do teclado.
        moveSelection(direction) {
            const from = this.node(this.current);

            if (!from) {
                this.current = this.nodes[0]?.key ?? null;

                return;
            }

            const center = (node) => ({ x: node.x + (node.w ?? 0) / 2, y: node.y + (node.h ?? 0) / 2 });
            const origin = center(from);
            let best = null;
            let bestScore = Infinity;

            for (const node of this.nodes) {
                if (node === from) {
                    continue;
                }

                const c = center(node);
                const dx = c.x - origin.x;
                const dy = c.y - origin.y;
                const along = { ArrowRight: dx, ArrowLeft: -dx, ArrowDown: dy, ArrowUp: -dy }[direction];
                const across = direction === 'ArrowRight' || direction === 'ArrowLeft' ? Math.abs(dy) : Math.abs(dx);

                if (along <= 0) {
                    continue;
                }

                const score = along + across * 2;

                if (score < bestScore) {
                    best = node;
                    bestScore = score;
                }
            }

            if (best) {
                this.current = best.key;
                this.reveal(best);
            }
        },

        keydown(event) {
            if (event.target.matches('input, textarea') || event.isComposing) {
                return;
            }

            const mod = event.metaKey || event.ctrlKey;

            if (mod && event.key.toLowerCase() === 'z') {
                event.preventDefault();
                this.undo();
            } else if (event.key === 'Tab') {
                event.preventDefault();
                this.addChild();
            } else if (event.key === 'Enter') {
                event.preventDefault();
                this.addSibling();
            } else if (event.key === 'F2' && this.node(this.current)) {
                event.preventDefault();
                this.startEdit(this.node(this.current));
            } else if (event.key === 'Delete' || event.key === 'Backspace') {
                event.preventDefault();

                if (this.selectedEdge()) {
                    this.removeSelected();
                } else {
                    this.askDelete();
                }
            } else if (event.key.startsWith('Arrow')) {
                event.preventDefault();
                this.moveSelection(event.key);
            } else if (event.key === 'Escape') {
                if (this.confirmingDelete) {
                    this.confirmingDelete = null;
                } else if (this.linking || this.selected || this.current) {
                    this.cancelLink();
                    this.selected = null;
                    this.current = null;
                } else if (this.fullscreen) {
                    this.toggleFullscreen();
                }
            }
        },

        toggleFullscreen() {
            this.fullscreen = !this.fullscreen;
            this.$nextTick(() => {
                this.centerOn(this.node(this.current) ?? this.nodes[0]);
                this.focusCanvas();
            });
        },

        // ---- Mouse: arrastar nó, ligar setas, curvar, mover o canvas ----

        connect(from, to) {
            if (from !== to && !this.edges.some((edge) => edge.from === from && edge.to === to)) {
                this.remember();
                this.edges.push({ from, to, bend: 0 });
                this.persist();
            }
        },

        edgePointerDown(event) {
            const hit = event.target.closest('[data-edge], [data-bend]');

            if (!hit) {
                return;
            }

            event.stopPropagation();
            this.cancelLink();
            this.current = null;

            if (hit.dataset.bend !== undefined) {
                this.remember();
                this.drag = { type: 'bend', edge: this.edges[hit.dataset.bend] };
                this.$refs.canvas.setPointerCapture(event.pointerId);
            } else {
                const edge = this.edges[hit.dataset.edge];

                this.selected = `${edge.from}>${edge.to}`;
            }
        },

        straightenSelected() {
            const edge = this.selectedEdge();

            if (edge) {
                this.remember();
                edge.bend = 0;
                this.persist();
            }
        },

        reverseSelected() {
            const edge = this.selectedEdge();

            if (!edge) {
                return;
            }

            this.remember();

            if (this.edges.some((other) => other.from === edge.to && other.to === edge.from)) {
                this.edges = this.edges.filter((other) => other !== edge);
                this.selected = null;
            } else {
                [edge.from, edge.to] = [edge.to, edge.from];
                edge.bend = -(edge.bend ?? 0);
                this.selected = `${edge.from}>${edge.to}`;
            }

            this.persist();
        },

        removeSelected() {
            const edge = this.selectedEdge();

            if (edge) {
                this.remember();
                this.edges = this.edges.filter((item) => item !== edge);
                this.selected = null;
                this.persist();
            }
        },

        startNode(event, node) {
            event.stopPropagation();
            this.focusCanvas();

            if (this.linking) {
                this.connect(this.linking, node.key);
                this.cancelLink();

                return;
            }

            this.current = node.key;
            this.selected = null;

            const point = this.toWorld(event);

            this.drag = { type: 'node', key: node.key, dx: point.x - node.x, dy: point.y - node.y, ox: node.x, oy: node.y };
            event.currentTarget.setPointerCapture(event.pointerId);
        },

        startEdge(event, node) {
            event.stopPropagation();

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
            this.focusCanvas();
            this.cancelLink();
            this.selected = null;
            this.current = null;
            this.drag = { type: 'pan', sx: event.clientX - this.pan.x, sy: event.clientY - this.pan.y };
            event.currentTarget.setPointerCapture(event.pointerId);
        },

        canvasDblclick(event) {
            const point = this.toWorld(event);

            this.current = null;
            this.addNode(point.x - 60, point.y - 16);
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
                this.persist();
            } else if (drag?.type === 'node') {
                const node = this.node(drag.key);

                if (node && (node.x !== drag.ox || node.y !== drag.oy)) {
                    // O Desfazer guarda a posição de antes do arrasto.
                    const moved = { x: node.x, y: node.y };

                    Object.assign(node, { x: drag.ox, y: drag.oy });
                    this.remember();
                    Object.assign(node, moved);
                    this.persist();
                }
            } else if (drag?.type === 'edge') {
                const target = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-node-key]')?.dataset.nodeKey;

                if (target && target !== drag.key) {
                    this.connect(drag.key, target);
                } else if (Math.hypot(event.clientX - drag.sx, event.clientY - drag.sy) < 4) {
                    this.linking = drag.key;
                    this.ghost = this.toWorld(event);
                }
            }
        },
    }));
});
