import EasyMDE from 'easymde';
import { enableNativeKeyboard } from './native-keyboard';
import { mapCanvas } from './map-canvas';

// Canvas do mapa de conceitos. O estado vive no cliente (nós, setas, pan/zoom) e
// o servidor só persiste cada gesto via $wire (placeNode/connect/disconnect/removeNode).
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.data('conceptMap', ({ concepts, nodes, edges }) => ({
        ...mapCanvas,
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

// Treino do mapa escrito. Tudo no cliente: o texto e o resultado não são persistidos.
// Aninhado no x-data do conceptMap, de onde lê `nodes` (os conceitos expostos no canvas).
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    const fold = (text) =>
        text
            .normalize('NFD')
            .replace(/\p{Mn}/gu, '')
            .toLowerCase()
            .replace(/[^\p{L}\p{N}\s]/gu, '')
            .replace(/\s+/g, ' ')
            .trim();

    // Distância de Levenshtein (nº mínimo de edições de letras entre duas strings).
    const distance = (a, b) => {
        let prev = Array.from({ length: b.length + 1 }, (_, j) => j);

        for (let i = 1; i <= a.length; i++) {
            const row = [i];

            for (let j = 1; j <= b.length; j++) {
                row[j] = Math.min(prev[j] + 1, row[j - 1] + 1, prev[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
            }

            prev = row;
        }

        return prev[b.length];
    };

    // Bloco de notas do mapa: EasyMDE próprio (só bullets), sem $wire e sem
    // autosave — nada é persistido. O flyout fica montado ao fechar, então o
    // texto sobrevive; só descarta ao sair da página.
    // O editor fica em closure, não em `this`: o proxy reativo do Alpine quebra
    // os internos do CodeMirror (erro ao selecionar texto com o mouse).
    Alpine.data('scratchpad', () => {
        let editor = null;

        return {
            init() {
                editor = new EasyMDE({
                    element: this.$refs.textarea,
                    toolbar: ['unordered-list'],
                    spellChecker: false,
                    status: false,
                    initialValue: '- ',
                    minHeight: '300px',
                });
                enableNativeKeyboard(editor);
            },

            // CodeMirror montado em <dialog> fechado mede 0px: refaz o layout ao abrir.
            open() {
                this.$flux.modal('scratchpad').show();
                this.$nextTick(() => {
                    const cm = editor.codemirror;

                    cm.refresh();
                    cm.focus();
                    cm.setCursor(cm.lastLine(), cm.getLine(cm.lastLine()).length);
                });
            },

            destroy() {
                editor?.toTextArea();
            },
        };
    });

    Alpine.data('mapTraining', () => ({
        durations: [5, 10, 15], // minutos
        minutes: 10,
        remaining: 600,
        timer: null,
        text: '',
        result: null, // { found: [rótulo], missing: [rótulo] } depois de enviar

        get running() {
            return this.timer !== null;
        },

        get words() {
            return this.text.trim() ? this.text.trim().split(/\s+/).length : 0;
        },

        get mapped() {
            const seen = new Set();

            return this.nodes.filter(
                (node) =>
                    !seen.has(fold(node.label)) && seen.add(fold(node.label)),
            );
        },

        // Conceito conta se uma sequência de palavras do texto é igual ao rótulo (sem acento/caixa),
        // tolerando erro de digitação: 1 letra de diferença em rótulos de 5+, 2 em rótulos de 10+.
        get foundLabels() {
            const tokens = fold(this.text).split(' ').filter(Boolean);

            return this.mapped
                .filter((node) => {
                    const target = fold(node.label);
                    const size = target.split(' ').length;
                    const tolerance = target.length >= 10 ? 2 : target.length >= 5 ? 1 : 0;

                    for (let i = 0; i + size <= tokens.length; i++) {
                        if (distance(tokens.slice(i, i + size).join(' '), target) <= tolerance) {
                            return true;
                        }
                    }

                    return false;
                })
                .map((node) => node.label);
        },

        get clock() {
            const pad = (n) => String(n).padStart(2, '0');

            return `${pad(Math.floor(this.remaining / 60))}:${pad(this.remaining % 60)}`;
        },

        pick(minutes) {
            if (this.running || this.result) {
                return;
            }

            this.minutes = minutes;
            this.remaining = minutes * 60;
        },

        start() {
            this.reset(false);
            this.timer = setInterval(() => {
                if (--this.remaining <= 0) {
                    this.submit();
                }
            }, 1000);
            this.$nextTick(() => this.$refs.trainingText.focus());
        },

        submit() {
            const found = this.foundLabels;

            clearInterval(this.timer);
            this.timer = null;
            this.result = {
                found,
                missing: this.mapped
                    .map((node) => node.label)
                    .filter((label) => !found.includes(label)),
            };
        },

        reset(clearText = true) {
            clearInterval(this.timer);
            this.timer = null;
            this.result = null;
            this.remaining = this.minutes * 60;

            if (clearText) {
                this.text = '';
            }
        },

        destroy() {
            clearInterval(this.timer);
        },
    }));
});
