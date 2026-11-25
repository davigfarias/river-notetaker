<?php

use App\Enums\NoteType;
use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Support\MindMap;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->discipline = Disciplines::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();
});

function mindMapPayload(): array
{
    return [
        'nodes' => [
            ['key' => 'raiz', 'label' => '  Graça  ', 'x' => 10, 'y' => 20],
            ['key' => 'filho', 'label' => 'Fé', 'x' => 200, 'y' => 20],
        ],
        'edges' => [['from' => 'raiz', 'to' => 'filho', 'bend' => 0]],
    ];
}

test('sanitize drops junk nodes and edges and clamps values', function () {
    $clean = MindMap::sanitize([
        'nodes' => [
            ['key' => 'a', 'label' => 'Um', 'x' => 1e9, 'y' => 'lixo'],
            ['key' => 'a', 'label' => 'Repetido', 'x' => 0, 'y' => 0],
            ['key' => 'B<script>', 'label' => 'Chave ruim', 'x' => 0, 'y' => 0],
            ['key' => 'c', 'label' => '   ', 'x' => 0, 'y' => 0],
            ['key' => 'd', 'label' => str_repeat('x', 500), 'x' => 0, 'y' => 0],
            'não é nó',
        ],
        'edges' => [
            ['from' => 'a', 'to' => 'd', 'bend' => 99999],
            ['from' => 'a', 'to' => 'd'],
            ['from' => 'a', 'to' => 'a'],
            ['from' => 'a', 'to' => 'sumiu'],
        ],
    ]);

    expect(array_column($clean['nodes'], 'key'))->toBe(['a', 'd'])
        ->and($clean['nodes'][0]['x'])->toBe(100000.0)
        ->and($clean['nodes'][0]['y'])->toBe(0.0)
        ->and(mb_strlen($clean['nodes'][1]['label']))->toBe(MindMap::MAX_LABEL)
        ->and($clean['edges'])->toBe([['from' => 'a', 'to' => 'd', 'bend' => 2000.0]])
        ->and(MindMap::sanitize('lixo'))->toBe(MindMap::empty());
});

test('a mind map note is created with the map and without the text fields', function () {
    Livewire::withQueryParams(['tipo' => 'mind_map'])
        ->test('pages::create', ['slug' => $this->discipline->slug])
        ->assertSet('noteType', 'mind_map')
        ->assertSee('MAPA MENTAL')
        ->assertDontSee('EXPERIÊNCIAS DE VIDA')
        ->set('notes.title', 'Aula em mapa')
        ->set('notes.impressions', 'não deve ficar')
        ->set('mindMap', mindMapPayload())
        ->call('save')
        ->assertHasNoErrors();

    $note = Notes::where('title', 'Aula em mapa')->sole();

    expect($note->type)->toBe(NoteType::MindMap)
        ->and($note->impressions)->toBeNull()
        ->and($note->mind_map['nodes'][0]['label'])->toBe('Graça')
        ->and($note->mind_map['edges'])->toHaveCount(1);
});

test('the note type defaults to text and ignores an unknown value', function () {
    Livewire::withQueryParams(['tipo' => 'hacker'])
        ->test('pages::create', ['slug' => $this->discipline->slug])
        ->assertSet('noteType', 'text')
        ->assertSee('EXPERIÊNCIAS DE VIDA')
        ->set('notes.title', 'Aula em texto')
        ->set('mindMap', mindMapPayload())
        ->call('save');

    $note = Notes::where('title', 'Aula em texto')->sole();

    expect($note->type)->toBe(NoteType::Text)->and($note->mind_map)->toBeNull();
});

test('the note type cannot be changed from the browser', function () {
    Livewire::test('pages::create', ['slug' => $this->discipline->slug])
        ->set('noteType', 'mind_map');
})->throws(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

test('the note page shows the map and saves changes', function () {
    $note = Notes::factory()->create([
        'discipline_id' => $this->discipline->id,
        'access_token_id' => $this->token->id,
        'type' => NoteType::MindMap,
        'mind_map' => mindMapPayload(),
    ]);

    Livewire::withQueryParams(['nota' => $note->id])
        ->test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->assertSee('MAPA MENTAL')
        ->assertSeeLivewire('mapa-da-nota');

    Livewire::test('mapa-da-nota', ['noteId' => $note->id])
        ->call('saveMindMap', ['nodes' => [['key' => 'novo', 'label' => 'Esperança', 'x' => 0, 'y' => 0]], 'edges' => []]);

    expect($note->fresh()->mind_map['nodes'])->toHaveCount(1)
        ->and(MindMap::text($note->fresh()->mind_map))->toBe('Esperança');
});

test("another token's mind map note cannot be opened", function () {
    $foreign = Notes::factory()->create([
        'discipline_id' => $this->discipline->id,
        'access_token_id' => AccessToken::factory()->create()->id,
        'type' => NoteType::MindMap,
    ]);

    Livewire::test('mapa-da-nota', ['noteId' => $foreign->id]);
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('a text note cannot be opened as a map', function () {
    $text = Notes::factory()->create(['discipline_id' => $this->discipline->id, 'access_token_id' => $this->token->id]);

    Livewire::test('mapa-da-nota', ['noteId' => $text->id]);
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('the new note button offers both types', function () {
    Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->assertSee('Que tipo de nota você quer criar?')
        ->assertSee(route('notas.criar', ['slug' => $this->discipline->slug, 'tipo' => 'mind_map']), false)
        ->assertSee(route('notas.criar', ['slug' => $this->discipline->slug, 'tipo' => 'text']), false);
});

test('map topics feed search and markdown export', function () {
    $note = Notes::factory()->create([
        'discipline_id' => $this->discipline->id,
        'access_token_id' => $this->token->id,
        'type' => NoteType::MindMap,
        'mind_map' => mindMapPayload(),
    ]);

    expect($note->toSearchableArray()['mind_map'])->toBe("Graça\nFé");

    $this->get(route('notas.exportar', $note))->assertOk();
});
