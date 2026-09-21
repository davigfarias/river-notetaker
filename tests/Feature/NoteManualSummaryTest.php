<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);

    $this->discipline = Disciplines::factory()->create(['slug' => 'teologia-'.uniqid()]);

    $this->note = Notes::create([
        'title' => 'A inspiração das Escrituras',
        'discipline_id' => $this->discipline->id,
        'access_token_id' => $this->token->id,
        'impressions' => 'A aula deixou claro o peso da inspiração verbal.',
    ]);

    Livewire::withoutLazyLoading();
});

function disciplinaPage()
{
    return Livewire::test('pages::disciplina', ['slug' => test()->discipline->slug]);
}

test('a nota sem resumo convida o aluno a escrever um', function () {
    disciplinaPage()
        ->assertSee('RESUMO')
        ->assertSee('Sem resumo escrito. Esta nota fica fora da revisão até você escrever o seu.')
        ->assertSee('Escrever resumo');
});

test('editar o resumo guarda o texto na nota', function () {
    disciplinaPage()
        ->call('edit', 'summary')
        ->assertSet('editing.summary', true)
        ->set('draft.summary', 'Deus inspirou as palavras, não só as ideias.')
        ->call('updateNote', 'summary')
        ->assertSet('editing.summary', false)
        ->assertSee('Deus inspirou as palavras, não só as ideias.');

    expect($this->note->refresh()->summary)->toBe('Deus inspirou as palavras, não só as ideias.');
});

test('o rascunho do resumo começa com o texto que já está salvo', function () {
    $this->note->update(['summary' => 'Resumo antigo.']);

    disciplinaPage()
        ->call('edit', 'summary')
        ->assertSet('draft.summary', 'Resumo antigo.');
});

test('uma nota de outro token não tem o resumo alterado', function () {
    $this->note->update(['access_token_id' => AccessToken::factory()->create()->id]);

    $outcome = app(App\Actions\SubActions\UpdateNote::class)
        ->handle($this->note->id, $this->token->id, ['summary' => 'Invasão.']);

    expect($outcome->success)->toBeFalse()
        ->and($this->note->refresh()->summary)->toBeNull();
});

test('o resumo por IA e a locução saíram da página da nota', function () {
    $this->note->update([
        'summary' => 'O meu resumo.',
        'ai_summary' => 'O TL;DR gerado pela máquina.',
    ]);

    disciplinaPage()
        ->assertSee('O meu resumo.')
        ->assertDontSee('O TL;DR gerado pela máquina.')
        ->assertDontSee('Resumo de IA')
        ->assertDontSee('Gerar resumo com IA')
        ->assertDontSee('Ouvir com voz de IA')
        ->assertDontSee('Voz do navegador');
});

test('o resumo escrito entra no que a busca indexa', function () {
    $this->note->update(['summary' => 'Inspiração verbal e plenária.']);

    expect($this->note->fresh()->toSearchableArray())
        ->toHaveKey('summary', 'Inspiração verbal e plenária.');
});
