# Modificações a executar (apenas)

## 1. Respiro lateral nas modais — mobile

Todas as modais abaixo estão coladas nas laterais no mobile. Trocar a classe de cada `<flux:modal>`:

- **pequenas** (hoje `md:w-96` ou `min-w-88`): `w-full max-w-[calc(100vw-2rem)] sm:max-w-sm`
- **grandes** (hoje `md:w-[32rem]` ou `md:w-lg`): `w-full max-w-[calc(100vw-2rem)] sm:max-w-lg`

| Arquivo | Modal | Padrão |
|---|---|---|
| `resources/views/pages/⚡dashboard/dashboard.blade.php` | excluir-disciplina (`min-w-88`) | pequenas |
| `resources/views/pages/⚡dashboard/dashboard.blade.php` | nova-disciplina (`w-full max-w-sm`) | manter como está |
| `resources/views/pages/⚡disciplina/disciplina.blade.php` | edit-title, edit-concept, edit-advice, add-concept, add-advice (`md:w-96`) | pequenas |
| `resources/views/pages/⚡disciplina/disciplina.blade.php` | edit-impressions, edit-life_experiences (`md:w-lg`) | grandes |
| `resources/views/pages/⚡create/create.blade.php` | add-reference-material (`md:w-[32rem]`) | grandes |
| `resources/views/pages/⚡create/create.blade.php` | edit-concept (`md:w-96`) | pequenas |
| `resources/views/pages/⚡concepts/concepts.blade.php` | modal-concept-* (`min-w-88 md:w-lg space-y-6`) | grandes (manter `space-y-6`) |
| `resources/views/pages/⚡concepts/concepts.blade.php` | add-concept, edit-concept (`md:w-96`) | pequenas |
| `resources/views/pages/⚡pastoral/pastoral.blade.php` | add-advice, edit-advice (`md:w-96`) | pequenas |
| `resources/views/pages/⚡referencia/referencia.blade.php` | edit-material, edit-citation, question-form, edit-question (`md:w-[32rem]`) | grandes |
| `resources/views/pages/⚡referencia/referencia.blade.php` | delete-citation, chapter-form, edit-chapter, delete-chapter, delete-question, export (`md:w-96`) | pequenas |
| `resources/views/pages/⚡referencias/referencias.blade.php` | add-material (`md:w-[32rem]`) | grandes |
| `resources/views/pages/⚡buscar-referencias/buscar-referencias.blade.php` | export-search (`md:w-96`) | pequenas |
| `resources/views/pages/⚡exportacoes/exportacoes.blade.php` | delete-export (`md:w-96`) | pequenas |

## 2. Autocomplete "Tema principal" (Conselhos) — bordas e texto escuros

1. `resources/views/pages/⚡pastoral/pastoral.blade.php` → no `<x-lwa::autocomplete>` adicionar `class="lwa-autocomplete"`.
2. `resources/css/app.css` → adicionar (fora de `@layer`):

```css
.lwa-autocomplete input {
    @apply w-full rounded-lg border border-surface-variant bg-surface-container-lowest px-3 py-2 text-sm text-on-surface placeholder:text-on-surface-variant shadow-none focus:border-primary focus:outline-none;
}
.lwa-autocomplete input:disabled {
    @apply bg-surface-variant/50;
}
.lwa-autocomplete .bg-white {
    background-color: var(--color-surface-container-lowest) !important;
    border-color: var(--color-surface-variant) !important;
}
.lwa-autocomplete .bg-gray-500 {
    background-color: var(--color-surface-variant) !important;
    opacity: 1 !important;
}
.lwa-autocomplete .border-2 {
    @apply border-surface-variant bg-surface-container-low text-on-surface-variant hover:text-on-surface;
}
```

## 3. Verificação

- `npm run build`
- Testar no mobile: cada modal listada com folga ~16px nas laterais; "Tema principal" legível, sem texto/borda escuros, dropdown de sugestões escuro.