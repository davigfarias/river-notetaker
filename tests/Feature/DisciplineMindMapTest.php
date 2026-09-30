<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use Livewire\Livewire;

beforeEach(function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    Livewire::withoutLazyLoading();

    $this->discipline = Disciplines::factory()->create(['slug' => 'hermeneutica']);
});

test('mind map saves valid mermaid source', function () {
    Livewire::test('pages::mapa-mental', ['slug' => 'hermeneutica'])
        ->assertSet('editing', true)
        ->set('mindMap', "mindmap\n  root((Hermenêutica))\n    Tópico")
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('editing', false);

    expect($this->discipline->refresh()->mind_map)->toStartWith('mindmap');
});

test('mind map rejects anything that is not mermaid', function (string $content) {
    Livewire::test('pages::mapa-mental', ['slug' => 'hermeneutica'])
        ->set('mindMap', $content)
        ->call('save')
        ->assertHasErrors('mindMap');

    expect($this->discipline->refresh()->mind_map)->toBeNull();
})->with([
    'plain markdown' => "# Título\n\nTexto solto",
    'fenced block' => "```mermaid\nmindmap\n  root\n```",
    'text after fence' => "mindmap\n  root\n```\n# fora",
    'empty' => '',
]);

test('saved mind map renders as a mermaid block and the page is reachable', function () {
    $this->discipline->update(['mind_map' => "mindmap\n  root((A))"]);

    $this->get(route('disciplinas.mapa-mental', 'hermeneutica'))
        ->assertOk()
        ->assertSee('language-mermaid', false);
});

test('dashboard card links to both mind map and notes', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('disciplinas.mapa-mental', 'hermeneutica'), false)
        ->assertSee(route('disciplinas.show', 'hermeneutica'), false);
});
