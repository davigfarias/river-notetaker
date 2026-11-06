<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\MapNode;
use App\Models\Notes;
use App\Models\ReadingNote;
use App\Models\ReferenceMapEdge;
use App\Models\ReferenceMapNode;
use App\Models\ReferenceMaterial;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();

    $this->material = ReferenceMaterial::factory()->article()->create(['access_token_id' => $this->token->id]);
    ReadingNote::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
        'body' => 'Sobre {{no: Graça}} e {{no: Fé}}.',
    ]);
});

function referenceMap(ReferenceMaterial $material): mixed
{
    return Livewire::test('mapa-da-referencia', ['referenceMaterialId' => $material->id]);
}

test('concepts come only from the reading notes of this work', function () {
    $otherWork = ReferenceMaterial::factory()->article()->create(['access_token_id' => $this->token->id]);
    ReadingNote::factory()->create(['reference_material_id' => $otherWork->id, 'access_token_id' => $this->token->id, 'body' => '{{no: Outra obra}}']);
    $this->material->citations()->create(['quote_text' => '{{no: Da citação}}', 'access_token_id' => $this->token->id]);
    Notes::factory()->create(['discipline_id' => Disciplines::factory()->create()->id, 'summary' => '{{no: Da disciplina}}']);

    expect(array_values(referenceMap($this->material)->instance()->concepts))->toBe(['Graça', 'Fé']);
});

test('nodes, arrows and free concepts work and stay out of the discipline map', function () {
    $map = referenceMap($this->material)
        ->call('placeNode', 'graca', 10, 20)
        ->call('placeNode', 'fe', 50, 20)
        ->call('connect', 'graca', 'fe')
        ->call('bendEdge', 'graca', 'fe', 30.0)
        ->call('addCustomNode', '  Ideia  ', 0, 0)
        ->assertCount('nodes', 3)
        ->assertCount('edges', 1)
        ->assertCount('pills', 0);

    expect(ReferenceMapEdge::first()->bend)->toBe(30.0)
        ->and(MapNode::count())->toBe(0);

    $map->call('reverseEdge', 'graca', 'fe');
    expect(ReferenceMapEdge::first()->only(['from_key', 'to_key', 'bend']))->toBe(['from_key' => 'fe', 'to_key' => 'graca', 'bend' => -30.0]);

    $map->call('removeNode', 'fe');
    expect(ReferenceMapEdge::count())->toBe(0)->and(ReferenceMapNode::count())->toBe(2);
});

test('maps of two works are independent', function () {
    $otherWork = ReferenceMaterial::factory()->article()->create(['access_token_id' => $this->token->id]);

    referenceMap($this->material)->call('addCustomNode', 'Só aqui', 0, 0);

    referenceMap($otherWork)->assertCount('nodes', 0);
});

test('a work of another owner returns 404', function () {
    $foreign = ReferenceMaterial::factory()->article()->create(['access_token_id' => AccessToken::factory()->create()->id]);

    Livewire::test('mapa-da-referencia', ['referenceMaterialId' => $foreign->id]);
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('the map has the canvas, the training and the scratchpad', function () {
    referenceMap($this->material)
        ->assertSeeHtml('x-data="scratchpad"')
        ->assertSeeHtml('x-data="mapTraining"')
        ->assertSeeHtml('conceptMap(')
        ->assertSee('Nenhum conceito marcado nas anotações desta obra ainda');
});

test('the tab mounts the map only when open and counts the concepts', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->assertSee('Mapa mental')
        ->assertDontSeeHtml('conceptMap(')
        ->set('activeTab', 'mapa')
        ->assertSeeLivewire('mapa-da-referencia');
});

test('a concept added to a note shows up the next time the tab opens', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->set('readingNoteForm.body', 'Nova {{no: Justificação}}')
        ->call('addReadingNote')
        ->assertHasNoErrors();

    referenceMap($this->material)->assertCount('pills', 3);
});

test('the reading note renders the marker as a chip', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->assertSeeHtml('>Graça</span>')
        ->assertDontSeeHtml('{{no:');
});

test('chips leave mermaid blocks untouched', function () {
    $html = App\Support\MapConcepts::chips('<p>{{no: A}}</p><pre><code class="language-mermaid">{{no: B}}</code></pre>');

    expect($html)->toContain('>A</span>')->toContain('{{no: B}}');
});

test('on small screens the sections are a select with all four options and their counts', function () {
    Livewire::test('pages::referencia', ['id' => $this->material->id])
        ->assertSeeHtml('aria-label="Escolher seção da obra"')
        ->assertSee('Citações (0)')
        ->assertSee('Capítulos e Perguntas (0)')
        ->assertSee('Anotações (1)')
        ->assertSee('Mapa mental (2)')
        ->set('activeTab', 'mapa')
        ->assertSeeLivewire('mapa-da-referencia');
});

test('promoting a note copies the text without the marker', function () {
    $note = $this->material->readingNotes()->first();

    app(App\Actions\PromoteReadingNoteToCitation::class)->handle($note->id, $this->token->id);

    expect($this->material->citations()->first()->quote_text)->toBe('Sobre Graça e Fé.');
});

test('titles, search snippets and table of contents hide the marker', function () {
    $note = $this->material->readingNotes()->first();
    $note->update(['title' => null]);

    expect($note->displayTitle())->toBe('Sobre Graça e Fé.')
        ->and(App\DTO\SearchResultDTO::fromReadingNote($note)->snippet)->toBe('Sobre Graça e Fé.')
        ->and(App\Support\MarkdownToc::headings("# {{no: Um}}\n\n# Dois", 'x')[0]['text'])->toBe('Um');
});

test('strip keeps the label and leaves other text alone', function () {
    expect(App\Support\MapConcepts::strip('a {{no:  B c }} d {{ e }}'))->toBe('a B c d {{ e }}');
});

test('the AI quiz prompt has no markers but the content hash still uses the stored text', function () {
    $note = $this->material->readingNotes()->first();

    App\Ai\Agents\QuizQuestionGenerator::fake([['questions' => []]]);

    (new App\Jobs\GenerateReadingNoteQuizJob($note))->handle(
        app(App\Actions\ValidateGeneratedQuestions::class),
        app(App\Actions\PersistReadingNoteQuizQuestions::class),
    );

    App\Ai\Agents\QuizQuestionGenerator::assertPrompted(fn ($prompt) => ! str_contains($prompt->prompt, '{{no:') && str_contains($prompt->prompt, 'Sobre Graça'));
});
