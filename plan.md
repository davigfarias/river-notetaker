# Plano: Mobile-first no Notetaker

Objetivo: tornar o app utilizável mobile-first. Hoje a navegação principal é inacessível no mobile `< 768px`, a leitura de notas dentro de uma disciplina não funciona no mobile, modais perdem o respiro lateral no mobile e o autocomplete "Tema principal" renderiza com bordas/texto escuros.

Stack: Laravel 13, Livewire v4 (Volt), Flux UI v2, Tailwind v4, Pest 5.

---

## 1. Layout global — `resources/views/layouts/app.blade.php`

### 1.1 Adicionar barra de navegação inferior fixa (mobile)

Espelhar o `flux:navbar` atual (linhas 29-67), que ficará apenas para desktop (`max-md:hidden`), num `<nav>` fixo abaixo do `<main>`, visível apenas em `md:hidden`.

Requisitos:
- Links com `wire:navigate` para: `route('dashboard')` (Disciplinas, `squares-2x2`), `route('concepts')` (Conceitos, `light-bulb`), `route('pastoral')` (Conselhos Pastorais, `users`), `route('referencias')` (Referências, `book-open`).
- Estado ativo com `data-current` do Livewire v4 + Tailwind v4 (`data-current:text-primary data-current:font-semibold`). Para Referências, destacar também as sub-rotas com `@if (request()->routeIs('referencias*') || request()->routeIs('referencias.show'))` → aplicar as mesmas classes `data-current`.
- Estrutura sugerida (usar os tokens de cor já usados no app):

```blade
<nav class="shrink-0 border-t border-outline-variant bg-surface-container-lowest/95 backdrop-blur-md md:hidden">
    <div class="mx-auto flex max-w-md items-stretch justify-around">
        <a wire:navigate href="{{ route('dashboard') }}"
           class="flex flex-1 flex-col items-center gap-0.5 py-2 text-[11px] text-on-surface-variant data-current:text-primary data-current:font-semibold">
            <flux:icon name="squares-2x2" class="size-6" />
            <span>Disciplinas</span>
        </a>
        {{-- ⊗ repitir para Conceitos, Conselhos Pastorais, Referências --}}
    </div>
</nav>
```

Localização: entre `</main>` (linha 81) e o `<footer>`.

### 1.2 Rodapé escondido no mobile

O `<footer>` atual (linhas 83-114) é `h-14 shrink-0`. Alterar para visível apenas no desktop:

```blade
<footer class="h-14 shrink-0 hidden md:flex">
```

### 1.3 Header

Manter `flux:header` e `flux:navbar class="max-md:hidden"` inalterados.

---

## 2. Leitura de nota no mobile — `resources/views/pages/⚡disciplina/`

### 2.1 Componente Volt (`disciplina.php`)

- Adicionar propriedade: `public bool $mobileDetail = false;`
- Em `selectNote(int $id)` (linha 105), setar `$this->mobileDetail = true;`

### 2.2 Template (`disciplina.blade.php`)

- **Sidebar da lista** (linha 7): adicionar condicional para esconder no mobile quando a nota estiver aberta:

```blade
<div class="border-outline-variant bg-surface-container-lowest/30 flex min-h-0 w-full flex-col border-r @if ($this->mobileDetail) hidden @endif md:flex md:w-1/3 lg:w-1/4">
```

- **Painel de conteúdo** (linha 55): trocar `hidden md:flex` por condicional:

```blade
<div class="bg-surface min-h-0 flex-1 flex-col overflow-y-auto p-8 md:flex lg:p-12 {{ $this->mobileDetail ? 'flex' : 'hidden' }}">
```

  (mover a classe `md:flex` conforme acima; manter `hidden` apenas quando `mobileDetail` for false no mobile)

- **Botão "Voltar"** no topo do painel de conteúdo, visível apenas no mobile. Inserir logo após a abertura do bloco `@if ($this->selectedNote)` (linha 56), antes do header da nota:

```blade
<div class="md:hidden">
    <flux:button variant="ghost" icon="arrow-left" wire:click="$set('mobileDetail', false)">Voltar</flux:button>
</div>
```

Comportamento:
- Desktop (≥ md): `mobileDetail` é irrelevante — ambos os painéis sempre visíveis.
- Mobile: lista visível por padrão (`mobileDetail = false`); tocar numa nota abre o detalhe; "Voltar" retorna à lista sem deselecionar a nota.
- `selectedNote()` já tem fallback para a primeira nota; `mobileDetail` iniciando em `false` garante que o mobile abra na lista.

---

## 3. Ajustes menores de responsividade

### 3.1 `resources/views/pages/⚡referencias/referencias.blade.php`

Botão "Exportações" do `headerActions` (linhas 1-8) e do `@placeholder` (linhas 10-18) deve mostrar só o ícone no mobile:

```blade
<flux:button variant="ghost" icon="arrow-down-tray" href="{{ route('referencias.exportacoes') }}" wire:navigate>
    <span class="hidden sm:inline">Exportações</span>
</flux:button>
```

### 3.2 `resources/views/pages/⚡referencia/referencia.blade.php`

Tabs (linha 60): adicionar `overflow-x-auto` e `whitespace-nowrap` para telas muito estreitas:

```blade
<div class="mt-8 flex gap-1 overflow-x-auto whitespace-nowrap border-b border-surface-variant">
```

### 3.3 `resources/views/pages/⚡exportacoes/exportacoes.blade.php`

Linha de exportação (linhas 40-76):
- Coluna de status (linha 56): `w-28` → `w-24 sm:w-28`
- Texto "Baixar" (linha 72) só no `sm+`:

```blade
<flux:button size="sm" variant="ghost" icon="arrow-down-tray" href="..." target="_blank">
    <span class="hidden sm:inline">Baixar</span>
</flux:button>
```

### 3.4 Respiro lateral nas modais (mobile)

**Causa identificada:** a modal `nova-disciplina` (`⚡dashboard/dashboard.blade.php:66`) é a única com largura explicitamente limitada em todos os breakpoints (`w-full max-w-sm`) — por isso tem respiro nas laterais. Todas as demais modais usam apenas `md:w-96`, `md:w-lg`, `md:w-[32rem]` ou `min-w-88` (classes só desktop ou só mínima). No mobile o `<dialog>` cai em `width: fit-content` + `[:where(&)]:max-w-xl` (576px) do Flux, e o conteúdo estica até colar nas bordas da viewport, sem respiro.

**Padrão universal (mobile-first) a aplicar em TODAS as modais exceto `nova-disciplina` (que já está boa):**

```blade
{{-- modais pequenas (ex‑`md:w-96`, 384px) --}}
<flux:modal ... class="w-full max-w-[calc(100vw-2rem)] sm:max-w-sm">

{{-- modais grandes (ex‑`md:w-[32rem]` / `md:w-lg`, 512px) --}}
<flux:modal ... class="w-full max-w-[calc(100vw-2rem)] sm:max-w-lg">
```

- Mobile: `max-width: calc(100vw - 2rem)` garante **~1rem de respiro em cada lateral** em qualquer largura de celular (mesma sensação da `nova-disciplina`).
- `sm+`: caps de 384px / 512px **mantêm as larguras atuais de desktop** (`md:w-96`=384, `md:w-[32rem]`/`md:w-lg`=512). `min-w-88` pode ser removido.

**Inventário completo (arquivo → modal → classe nova):**

| Arquivo | Modal (classe atual) | Nova classe |
|---|---|---|
| `⚡dashboard` | excluir-disciplina (`min-w-88`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-sm` |
| `⚡dashboard` | nova-disciplina (`w-full max-w-sm`) | **manter** (referência) |
| `⚡disciplina` | edit-title, edit-concept, edit-advice, add-concept, add-advice (`md:w-96`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-sm` |
| `⚡disciplina` | edit-impressions, edit-life_experiences (`md:w-lg`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-lg` |
| `⚡create` | add-reference-material (`md:w-[32rem]`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-lg` |
| `⚡create` | edit-concept (`md:w-96`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-sm` |
| `⚡concepts` | modal-concept-* (`min-w-88 md:w-lg space-y-6`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-lg space-y-6` |
| `⚡concepts` | add-concept, edit-concept (`md:w-96`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-sm` |
| `⚡pastoral` | add-advice, edit-advice (`md:w-96`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-sm` |
| `⚡referencia` | edit-material, edit-citation, question-form, edit-question (`md:w-[32rem]`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-lg` |
| `⚡referencia` | delete-citation, chapter-form, edit-chapter, delete-chapter, delete-question, export (`md:w-96`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-sm` |
| `⚡referencias` | add-material (`md:w-[32rem]`) — **mesmo problema, flagrado por você** | `w-full max-w-[calc(100vw-2rem)] sm:max-w-lg` |
| `⚡buscar-referencias` | export-search (`md:w-96`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-sm` |
| `⚡exportacoes` | delete-export (`md:w-96`) | `w-full max-w-[calc(100vw-2rem)] sm:max-w-sm` |

Verificação no devtools: ao abrir cada modal no mobile, `max-width` do `<dialog>` deve ser `calc(100vw - 2rem)` com ~16px de folga das bordas.

### 3.5 Autocomplete "Tema principal" com bordas e texto pretos

**Causa identificada:** o pacote `livewire-autocomplete` (não-Flux) usa classes da era Tailwind v3 que **não compilam no Tailwind v4**:
- `vendor/joshhanley/livewire-autocomplete/resources/views/components/input.blade.php`: `border-cool-gray-200 text-cool-gray-900 placeholder-cool-gray-400 disabled:bg-cool-gray-100 shadow-inner` — paleta `cool-gray-*` não existe no v4 (**confirmado: 0 ocorrências no CSS compilado**; só `blue-400` do focus compila). Sem cor de texto/borda → recai no estilo nativo do navegador (texto/borda escuros), com focus azul destoando do tema.
- `dropdown.blade.php`: `border-gray-300 bg-white` **compilam** → dropdown de sugestões abre **branco** (bleed do tema claro).

**Correção (sem editar vendor):**

1. Hook de classe no `<x-lwa::autocomplete>` em `⚡pastoral/pastoral.blade.php:89`:

```blade
<x-lwa::autocomplete
    name="category-autocomplete"
    class="lwa-autocomplete"
    wire:model-text="formAdvice.category"
    ... (resto igual)
/>
```

2. Overrides em `resources/css/app.css` (regras fora de `@layer` — vencem a camada de utilities do Tailwind):

```css
/* livewire-autocomplete — dark theme (Catppuccin) */
.lwa-autocomplete input {
    @apply w-full rounded-lg border border-surface-variant bg-surface-container-lowest px-3 py-2 text-sm text-on-surface placeholder:text-on-surface-variant shadow-none focus:border-primary focus:outline-none;
}
.lwa-autocomplete input:disabled {
    @apply bg-surface-variant/50;
}
.lwa-autocomplete .bg-white {   /* dropdown de sugestões */
    background-color: var(--color-surface-container-lowest) !important;
    border-color: var(--color-surface-variant) !important;
}
.lwa-autocomplete .bg-gray-500 {   /* overlay de loading */
    background-color: var(--color-surface-variant) !important;
    opacity: 1 !important;
}
.lwa-autocomplete .border-2 {   /* botão clear (x) */
    @apply border-surface-variant bg-surface-container-low text-on-surface-variant hover:text-on-surface;
}
```

Opcional: `config/autocomplete.php` → `'result-focus-styles' => 'bg-primary/20'` (trocar o azul `bg-blue-500` do foco). Revalidar visualmente o clear-button e o loading.

---

## 4. Testes

Criar `tests/Feature/MobileLayoutTest.php` (Pest) seguindo o padrão de `AccessTokenMiddlewareTest` / `CreateNoteSaveTest` (session `['access_token_id' => $token->id]` com `AccessToken::factory()`).

```php
<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use Livewire\Livewire;

test('dashboard renders the mobile bottom navigation links', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id])
        ->get('/')
        ->assertOk()
        ->assertSee(route('concepts'))
        ->assertSee(route('pastoral'))
        ->assertSee(route('referencias'));
});

test('the desktop navbar remains hidden on mobile and footer is hidden on mobile', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id])
        ->get('/')
        ->assertOk()
        ->assertSee('max-md:hidden', false)   // flux:navbar esconde no mobile
        ->assertSee('hidden md:flex', false); // footer some no mobile
});

test('selecting a note on mobile opens the detail panel with a back button', function () {
    $token = AccessToken::factory()->create();
    $discipline = Disciplines::factory()->create();
    $note = Notes::factory()->create();

    Livewire::test('pages::disciplina', ['slug' => $discipline->slug])
        ->assertSet('mobileDetail', false)
        ->call('selectNote', $note->id)
        ->assertSet('mobileDetail', true)
        ->assertSee("$set('mobileDetail', false)");
});
```

Validar os fatos dos modelos/factories (`Notes` tem `note_id`? a nota pertence a disciplina via qual coluna?) antes de escrever — consultar `app/Models/Notes.php` e as factories (usa `database/factories`).

### 4.1 Módulo de modais e autocomplete — `tests/Feature/MobileModalSpacingTest.php`

Seguir o mesmo padrão de sessão (`['access_token_id' => $token->id]`). Para páginas que listam dados, usar os factories correspondentes (`ReferenceMaterial`, `Disciplines`, etc.).

```php
<?php

use App\Models\AccessToken;
use Livewire\Livewire;

test('modals keep a mobile breathing margin on every main page', function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    // dashboard (delete discipline modal)
    $this->get('/')->assertOk()->assertSee('w-full max-w-[calc(100vw-2rem)]');

    // pastoral (add/edit advice modal + autocomplete hook)
    Livewire::test('pages::pastoral')
        ->assertSee('w-full max-w-[calc(100vw-2rem)]')
        ->assertSee('lwa-autocomplete');
});

test('the add-material modal on references page keeps a mobile breathing margin', function () {
    $token = AccessToken::factory()->create();
    $material = \App\Models\ReferenceMaterial::factory()->create();

    Livewire::test('pages::referencias')
        ->assertSee('w-full max-w-[calc(100vw-2rem)]');
});
```

Estender por página conforme renderizáveis (`concepts`, `create`, `exportacoes`, `referencia`), sempre com `assertSee('w-full max-w-[calc(100vw-2rem)]')`. Verificar a factory de `ReferenceMaterial` antes (consultar `database/factories/`).

Rodar:

```bash
php artisan test --compact --filter=MobileLayoutTest
php artisan test --compact --filter=MobileModalSpacingTest
php artisan test --compact --filter=CreateNoteSaveTest
php artisan test --compact --filter=AccessTokenMiddlewareTest
```

---

## 5. Verificação final (obrigatório)

1. `vendor/bin/pint --dirty --format agent`
2. `npm run build` (Tailwind v4 — necessário para o `data-current:`, `max-w-[calc(100vw-2rem)]` e novas classes)
3. Teste manual no Herd: abrir em janela/emulador mobile:
   - Navegar entre as 4 seções pela barra inferior
   - Abrir Disciplinas → tocar numa nota → ler conteúdo → Voltar
   - Abrir Referências → ver botão Exportações compacto no header
   - Abrir QUALQUER modal (criar disciplina, nova obra, adicionar conceito/conselho, exportações) → conferir respiro lateral ~16px
   - Abrir a modal "Conselhos Pastorais → Adicionar conselho" → campo "Tema principal" legível, sem texto/borda pretos; sugerir categoria e validar dropdown escuro

---

## Fora de escopo (se sobrar tempo, não é obrigatório)

- `create` como fluxo de tela cheia no mobile (já funciona razoavelmente)
- `@persist` de scroll quando navegar de volta na lista de notas da disciplina