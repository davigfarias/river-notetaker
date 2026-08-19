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

document.addEventListener('alpine:init', () => {
    // Registra o componente 'markdownEditor'
    Alpine.data('markdownEditor', (entangledContent) => ({
        content: entangledContent,
        init() {
            const editor = new EasyMDE({
                element: this.$refs.textarea,
                toolbar: ['bold', 'italic', 'horizontal-rule', '|', 'unordered-list', 'ordered-list'],
                spellChecker: false,
                status: false,
                initialValue: this.content,
                placeholder: this.$refs.textarea.getAttribute('placeholder'),
                minHeight: "200px",
                maxHeight: "200px"
            });

            // Sincroniza JS -> Livewire
            editor.codemirror.on('change', () => {
                this.content = editor.value();
            });

            // Sincroniza Livewire -> JS (útil ao limpar o form ou carregar rascunho)
            this.$watch('content', (value) => {
                if (value !== editor.value()) {
                    editor.value(value);
                }
            });
        }
    }));
});
