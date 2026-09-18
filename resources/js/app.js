import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';
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
