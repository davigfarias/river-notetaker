import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';
import { Network } from 'vis-network/standalone';
import { computePosition, autoUpdate, offset, flip, shift } from '@floating-ui/dom';
import './session-modal';

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('a[href^="#edit-concept-"]');

    if (!trigger) {
        return;
    }

    event.preventDefault();

    const id = trigger.getAttribute('href').replace('#edit-concept-', '');

    window.Livewire.dispatch('edit-concept-requested', { id: parseInt(id, 10) });
});

const savedAtKey = (autosaveId) => `smde_${autosaveId}_savedAt`;

const readSavedAt = (autosaveId) => {
    try {
        const ts = localStorage.getItem(savedAtKey(autosaveId));

        return ts ? new Date(Number(ts)).toLocaleString('pt-BR') : '';
    } catch (e) {
        return '';
    }
};

const writeSavedAt = (autosaveId) => {
    try {
        localStorage.setItem(savedAtKey(autosaveId), Date.now());
    } catch (e) {
        // localStorage indisponível (ex.: navegação privada) — segue sem rascunho
    }
};

const forgetSavedAt = (autosaveId) => {
    try {
        localStorage.removeItem(savedAtKey(autosaveId));
    } catch (e) {
        // idem
    }
};

const normalizeLang = (lang) => lang.replace('_', '-');

/**
 * Sem voz explícita o navegador usa a voz padrão do sistema, que no Apple é a
 * "compacta" antiga — a de pior qualidade instalada. Ordem de preferência:
 * vozes Enhanced/Premium/Siri, depois vozes de rede (as "Google" do Chrome),
 * depois qualquer uma do idioma.
 */
const voiceQuality = (voice) => {
    if (/enhanced|premium|siri/i.test(voice.name)) {
        return 3;
    }

    if (! voice.localService) {
        return 2;
    }

    return /google/i.test(voice.name) ? 1 : 0;
};

const pickVoice = (lang) => {
    const candidates = window.speechSynthesis
        .getVoices()
        .filter((voice) => normalizeLang(voice.lang).startsWith(lang.split('-')[0]));

    // O dialeto exato pesa mais que a qualidade: uma pt-BR compacta soa melhor
    // pra um brasileiro do que uma pt-PT premium.
    candidates.sort((a, b) => {
        const exact = (voice) => (normalizeLang(voice.lang) === lang ? 1 : 0);

        return exact(b) - exact(a) || voiceQuality(b) - voiceQuality(a);
    });

    return candidates[0] ?? null;
};

// Número de barras da waveform do player de locução.
const BAR_COUNT = 28;

document.addEventListener('alpine:init', () => {
    // Indica se a página de criar nota tem edições não salvas.
    // Alimentado por `resources/views/pages/⚡create/create.js` (100% client-side).
    Alpine.store('noteDraft', { dirty: false });

    // Registra o componente 'markdownEditor'.
    // `field` é o nome da propriedade Livewire (ex.: 'notes.impressions').
    // `autosaveId`, quando presente, liga o autosave nativo do EasyMDE (localStorage).
    Alpine.data('markdownEditor', (field, autosaveId = null) => ({
        editor: null,
        unwatch: null,
        savedAtTimer: null,
        flushDraft: null,
        onVisibilityChange: null,
        draftRestored: false,
        draftSavedAt: '',
        init() {
            const options = {
                element: this.$refs.textarea,
                toolbar: ['bold', 'italic', 'horizontal-rule', '|', 'unordered-list', 'ordered-list'],
                spellChecker: false,
                status: false,
                initialValue: this.$wire.$get(field) ?? '',
                placeholder: this.$refs.textarea.getAttribute('placeholder'),
                minHeight: '200px',
                maxHeight: '200px',
            };

            if (autosaveId) {
                // Grava em localStorage['smde_' + uniqueId], 1s após parar de digitar.
                options.autosave = { enabled: true, uniqueId: autosaveId, delay: 1000 };
            }

            const editor = new EasyMDE(options);
            this.editor = editor;

            // O EasyMDE carrega um rascunho salvo direto no CodeMirror durante a
            // construção. Detecta isso e empurra pro Livewire (sync adiada, sem request).
            if (autosaveId && editor.options.autosave.foundSavedValue === true) {
                this.draftRestored = true;
                this.draftSavedAt = readSavedAt(autosaveId);
            }

            if ((this.$wire.$get(field) ?? '') !== editor.value()) {
                this.$wire.$set(field, editor.value(), false);
            }

            // Sincroniza JS -> Livewire (adiada, igual ao comportamento sem `.live`).
            editor.codemirror.on('change', () => {
                this.$wire.$set(field, editor.value(), false);

                if (autosaveId) {
                    clearTimeout(this.savedAtTimer);
                    this.savedAtTimer = setTimeout(() => writeSavedAt(autosaveId), 1000);
                }
            });

            // Sincroniza Livewire -> JS (útil ao limpar o form ou descartar rascunho).
            this.unwatch = this.$wire.$watch(field, (value) => {
                if ((value ?? '') !== editor.value()) {
                    editor.value(value ?? '');
                }
            });

            if (autosaveId) {
                // Fecha a janela do debounce: grava o rascunho na hora ao sair do
                // campo, esconder a aba ou fechar/recarregar a página — antes que
                // o timer de 1s tenha chance de disparar.
                this.flushDraft = () => {
                    if (this.editor && this.editor.value() !== '') {
                        this.editor.autosave();
                        writeSavedAt(autosaveId);
                    }
                };

                this.onVisibilityChange = () => {
                    if (document.visibilityState === 'hidden') {
                        this.flushDraft();
                    }
                };

                editor.codemirror.on('blur', this.flushDraft);
                window.addEventListener('pagehide', this.flushDraft);
                document.addEventListener('visibilitychange', this.onVisibilityChange);
            }
        },
        // Botão "Descartar" do banner de rascunho recuperado.
        discardDraft() {
            this.editor.value('');

            if (autosaveId) {
                this.editor.clearAutosavedValue();
                forgetSavedAt(autosaveId);
            }

            this.$wire.$set(field, '', false);
            this.draftRestored = false;
        },
        // Disparado após o save salvar a nota com sucesso.
        clearDraft() {
            if (!autosaveId) {
                return;
            }

            this.editor.clearAutosavedValue();
            forgetSavedAt(autosaveId);
            this.draftRestored = false;
        },
        destroy() {
            this.unwatch?.();
            clearTimeout(this.savedAtTimer);

            if (this.flushDraft) {
                window.removeEventListener('pagehide', this.flushDraft);
                document.removeEventListener('visibilitychange', this.onVisibilityChange);
            }

            if (this.editor) {
                clearTimeout(this.editor._autosave_timeout);
                this.editor.cleanup();
                this.editor.toTextArea();
            }
        },
    }));

    // Grafo de conceitos relacionados (estilo Obsidian Graph View).
    // `initialGraph` é `{nodes, edges}` já no formato do vis-network.
    // A instância do vis.Network usa campos privados de classe (#foo), que
    // quebram se o Alpine transformar o objeto em proxy reativo — por isso
    // fica numa variável de closure, fora do `this` reativo do componente.
    Alpine.data('conceptGraph', (initialGraph) => {
        let network = null;
        let graph = initialGraph;

        // Acima disso a física contínua (arrastar com "mola") custa CPU demais;
        // estabiliza uma vez e congela o layout.
        const LARGE_GRAPH_NODES = 300;

        return {
            // O mapa fica num <details> fechado: montar só ao abrir evita travar
            // a página inteira com a simulação de centenas de nós.
            init() {
                const details = this.$el.closest('details');

                if (!details || details.open) {
                    this.mount();

                    return;
                }

                details.addEventListener('toggle', () => details.open && this.mount());
            },
            mount() {
                if (network) {
                    return;
                }

                const isLarge = graph.nodes.length > LARGE_GRAPH_NODES;

                network = new Network(
                    this.$refs.container,
                    { nodes: graph.nodes, edges: graph.edges },
                    {
                        // improvedLayout (Kamada-Kawai) é o que explode com muitos nós.
                        layout: { improvedLayout: false },
                        // updateInterval baixo = vis-network devolve o controle ao
                        // navegador com mais frequência (blocos curtos, sem engasgo).
                        physics: {
                            solver: 'barnesHut',
                            barnesHut: { theta: isLarge ? 0.8 : 0.5 },
                            stabilization: { iterations: isLarge ? 100 : 400, updateInterval: 5 },
                        },
                        nodes: {
                            shape: 'dot',
                            size: 12,
                            color: { background: '#e2c7ff', border: '#cba6f7', highlight: '#e2c7ff' },
                            font: { color: '#e1e0f8', size: 14 },
                        },
                        edges: {
                            color: { color: '#4a444f', highlight: '#e2c7ff' },
                        },
                        interaction: { hover: true, tooltipDelay: 100, zoomView: true, dragView: true },
                    },
                );

                if (isLarge) {
                    network.once('stabilizationIterationsDone', () => network.setOptions({ physics: false }));
                }
            },
            updateGraph(newGraph) {
                graph = newGraph;
                network?.setData({ nodes: graph.nodes, edges: graph.edges });
            },
            zoomIn() {
                network?.moveTo({ scale: network.getScale() * 1.3, animation: { duration: 150 } });
            },
            zoomOut() {
                network?.moveTo({ scale: network.getScale() / 1.3, animation: { duration: 150 } });
            },
            resetZoom() {
                network?.fit({ animation: { duration: 200 } });
            },
            destroy() {
                network?.destroy();
            },
        };
    });

    // Painel flutuante com a definição completa de um conceito (alguns têm
    // quase duas páginas de texto — por isso é um popover grande e rolável,
    // não um tooltip). Usa a Popover API nativa (`popover="manual"`) pra
    // escapar do clipping do <flux:modal>, igual o próprio Flux faz nos
    // tooltips dele: elementos com popover entram no "top layer" do
    // navegador, acima do conteúdo normal da modal.
    Alpine.data('conceptPopover', () => ({
        cleanup: null,
        closeTimer: null,
        open() {
            clearTimeout(this.closeTimer);

            const trigger = this.$refs.trigger;
            const panel = this.$refs.panel;

            if (!panel.matches(':popover-open')) {
                panel.showPopover();
            }

            this.cleanup ??= autoUpdate(trigger, panel, () => {
                computePosition(trigger, panel, {
                    strategy: 'fixed',
                    placement: 'top',
                    middleware: [offset(8), flip(), shift({ padding: 8 })],
                }).then(({ x, y }) => {
                    Object.assign(panel.style, { left: `${x}px`, top: `${y}px` });
                });
            });
        },
        scheduleClose() {
            this.closeTimer = setTimeout(() => this.close(), 150);
        },
        close() {
            this.cleanup?.();
            this.cleanup = null;
            this.$refs.panel?.hidePopover();
        },
        destroy() {
            this.close();
        },
    }));

    // Posiciona um botão "+" flutuante sobre um trecho de texto selecionado
    // (dentro de summary/impressions/life_experiences já renderizados).
    // Clicar nele abre, no servidor, o modal 'link-principle' (busca entre
    // todos os princípios das disciplinas da nota) — este componente só
    // cuida da seleção e do posicionamento, nunca lista princípios.
    Alpine.data('linkable', (field) => ({
        pendingSnippet: '',
        virtualEl: null,
        cleanup: null,
        onDocumentSelectionChange: null,
        init() {
            // O Chrome só desfaz a seleção depois do mouseup quando se clica
            // dentro do trecho já selecionado — então o mouseup ainda vê a
            // seleção velha. Esconder aqui, quando a seleção de fato some,
            // evita o "+" preso na tela.
            this.onDocumentSelectionChange = () => {
                const selection = window.getSelection();

                if (!selection || selection.isCollapsed || !this.$el.contains(selection.anchorNode)) {
                    this.hideTrigger();
                }
            };

            document.addEventListener('selectionchange', this.onDocumentSelectionChange);
        },
        hideTrigger() {
            this.cleanup?.();
            this.cleanup = null;
            this.$refs.trigger?.hidePopover();
        },
        anchor(el) {
            this.cleanup?.();
            this.cleanup = autoUpdate(this.virtualEl, el, () => {
                computePosition(this.virtualEl, el, {
                    strategy: 'fixed',
                    placement: 'top',
                    middleware: [offset(6), flip(), shift({ padding: 8 })],
                }).then(({ x, y }) => {
                    Object.assign(el.style, { left: `${x}px`, top: `${y}px` });
                });
            });
        },
        onSelectionChange(event) {
            // Clicar no próprio "+" (que é `popover`) também dispara
            // `mouseup`, que borbulha até aqui — sem essa guarda, reabriria
            // o "+" no meio do clique.
            if (event.target.closest('[popover]')) {
                return;
            }

            const selection = window.getSelection();
            const text = selection?.toString().trim();

            if (!text || !this.$el.contains(selection.anchorNode)) {
                this.$refs.trigger.hidePopover();

                return;
            }

            this.pendingSnippet = text;

            const range = selection.getRangeAt(0);
            this.virtualEl = { getBoundingClientRect: () => range.getBoundingClientRect() };

            this.anchor(this.$refs.trigger);
            this.$refs.trigger.showPopover();
        },
        openPicker() {
            this.hideTrigger();
            this.$wire.call('startLinkingPrinciple', field, this.pendingSnippet);
            window.getSelection()?.removeAllRanges();
        },
        destroy() {
            this.cleanup?.();
            document.removeEventListener('selectionchange', this.onDocumentSelectionChange);
        },
    }));

    // A lista de vozes carrega de forma assíncrona; pedir uma vez aqui faz o
    // primeiro clique já encontrar as vozes populadas.
    window.speechSynthesis?.getVoices();

    // Leitura em voz alta via Web Speech API (100% client-side, sem IA).
    // `text` fixo, ou null pra ler o innerText de `$refs.content` (útil
    // quando o bloco renderiza Markdown como HTML).
    Alpine.data('readAloud', (text = null) => ({
        read(lang) {
            if (!window.speechSynthesis) {
                return;
            }

            const content = text ?? this.$refs.content?.innerText ?? '';

            if (!content.trim()) {
                return;
            }

            window.speechSynthesis.cancel();

            const utterance = new SpeechSynthesisUtterance(content);
            utterance.lang = lang;

            const voice = pickVoice(lang);

            if (voice) {
                utterance.voice = voice;
            }

            window.speechSynthesis.speak(utterance);
        },
    }));

    // Player da locução gerada por IA, com waveform que reage ao som.
    //
    // A waveform usa o AnalyserNode da Web Audio API, nativa do navegador: um
    // MediaElementSource só pode ser criado uma vez por elemento <audio>, e só
    // depois de um gesto do usuário (política de autoplay), por isso o grafo é
    // montado no primeiro play e reaproveitado daí em diante.
    Alpine.data('aiAudioPlayer', () => ({
        playing: false,
        current: 0,
        duration: 0,
        // Alturas em porcentagem, uma por barra da waveform.
        bars: Array(BAR_COUNT).fill(8),
        analyser: null,
        frame: null,

        get audio() {
            return this.$refs.audio;
        },

        get progress() {
            return this.duration > 0 ? (this.current / this.duration) * 100 : 0;
        },

        toggle() {
            this.audio.paused ? this.audio.play() : this.audio.pause();
        },

        onPlay() {
            this.playing = true;
            this.connectAnalyser();
            this.draw();
        },

        onPause() {
            this.playing = false;
            this.stopDrawing();
        },

        onEnded() {
            this.playing = false;
            this.current = 0;
            this.stopDrawing();
        },

        /** Move a reprodução para o ponto clicado na waveform. */
        seek(event) {
            if (!this.duration) {
                return;
            }

            const box = event.currentTarget.getBoundingClientRect();
            const ratio = Math.min(Math.max((event.clientX - box.left) / box.width, 0), 1);

            this.audio.currentTime = ratio * this.duration;
            this.current = this.audio.currentTime;
        },

        /**
         * Liga o <audio> a um AnalyserNode. O grafo precisa seguir até o
         * destination, senão o áudio é capturado e nada sai nas caixas.
         */
        connectAnalyser() {
            if (this.analyser || !window.AudioContext) {
                return;
            }

            try {
                const context = new AudioContext();
                const source = context.createMediaElementSource(this.audio);

                this.analyser = context.createAnalyser();
                this.analyser.fftSize = 128;
                this.analyser.smoothingTimeConstant = 0.75;

                source.connect(this.analyser);
                this.analyser.connect(context.destination);
            } catch (e) {
                // Sem waveform reativa o player continua funcionando.
                this.analyser = null;
            }
        },

        draw() {
            if (!this.analyser) {
                // Sem analisador, anima um vaivém suave só para não ficar estático.
                this.bars = this.bars.map((_, i) => 20 + Math.sin(Date.now() / 200 + i) * 12);
                this.frame = requestAnimationFrame(() => this.draw());

                return;
            }

            const data = new Uint8Array(this.analyser.frequencyBinCount);
            this.analyser.getByteFrequencyData(data);

            const step = Math.floor(data.length / BAR_COUNT) || 1;

            this.bars = this.bars.map((_, i) => {
                const value = data[i * step] ?? 0;

                // 8% de altura mínima para as barras nunca sumirem de todo.
                return 8 + (value / 255) * 92;
            });

            this.frame = requestAnimationFrame(() => this.draw());
        },

        stopDrawing() {
            if (this.frame) {
                cancelAnimationFrame(this.frame);
                this.frame = null;
            }

            this.bars = this.bars.map(() => 8);
        },

        format(seconds) {
            if (!Number.isFinite(seconds)) {
                return '0:00';
            }

            const total = Math.floor(seconds);

            return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
        },
    }));
});
