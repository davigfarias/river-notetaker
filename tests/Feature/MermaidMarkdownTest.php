<?php

use App\Models\AccessToken;
use App\Models\Notes;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('mermaid fence renders as a language-mermaid code block', function () {
    $html = Str::markdownRich("```mermaid\nflowchart TD\n  A --> B\n```");

    expect($html)->toContain('<pre><code class="language-mermaid">')
        ->and($html)->toContain('A --&gt; B');
});

test('principle links never inject marks inside mermaid blocks', function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);
    Livewire::withoutLazyLoading();

    $note = Notes::factory()->create(['access_token_id' => $token->id]);

    $link = (object) [
        'id' => 1,
        'snippet' => 'Fim',
        'principle' => (object) ['title' => 'Princípio X'],
    ];

    $html = Livewire::test('pages::disciplina', ['slug' => $note->discipline->slug])
        ->instance()
        ->renderWithLinks("Fim do texto\n\n```mermaid\nflowchart TD\n  A[Fim] --> B\n```", collect([$link]));

    expect($html)->toContain('<mark')
        ->and($html)->toContain('A[Fim] --&gt; B')
        ->and(substr_count($html, '<mark'))->toBe(1);
});
