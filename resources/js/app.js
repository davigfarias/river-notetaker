import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

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

document.addEventListener('alpine:init', () => {
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
});
