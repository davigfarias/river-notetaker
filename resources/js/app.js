import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

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
                // Puxa o placeholder direto do HTML para ficar dinâmico
                placeholder: this.$refs.textarea.getAttribute('placeholder')
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
