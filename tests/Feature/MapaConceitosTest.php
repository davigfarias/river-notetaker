<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\MapEdge;
use App\Models\MapNode;
use App\Models\Notes;
use App\Support\MapConcepts;
use Livewire\Livewire;

beforeEach(function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    Livewire::withoutLazyLoading();

    $this->discipline = Disciplines::factory()->create(['slug' => 'hermeneutica']);
    Notes::factory()->create([
        'discipline_id' => $this->discipline->id,
        'summary' => 'Texto {{no: Exegese}} e {{no:  Eisegese }} de novo {{no: exegese}}.',
        'impressions' => '{{no: Contexto}}',
    ]);
});

test('extract deduplicates by folded key and ignores empty markers', function () {
    expect(MapConcepts::extract(['{{no: Exegese}} {{no: exegese}} {{no:   }} {{no: Ação}}', null]))
        ->toBe(['exegese' => 'Exegese', 'acao' => 'Ação']);
});

test('page lists note markers as pills until placed', function () {
    Livewire::test('pages::mapa-conceitos', ['slug' => 'hermeneutica'])
        ->assertSet('discipline.slug', 'hermeneutica')
        ->assertCount('pills', 3)
        ->call('placeNode', 'exegese', 10, 20)
        ->assertCount('pills', 2)
        ->assertCount('nodes', 1);

    expect(MapNode::first())->toMatchArray(['key' => 'exegese', 'label' => 'Exegese', 'x' => 10.0, 'y' => 20.0]);
});

test('placing an unknown concept is ignored', function () {
    Livewire::test('pages::mapa-conceitos', ['slug' => 'hermeneutica'])->call('placeNode', 'inexistente', 1, 1);

    expect(MapNode::count())->toBe(0);
});

test('connect only links placed nodes and disconnect removes the arrow', function () {
    Livewire::test('pages::mapa-conceitos', ['slug' => 'hermeneutica'])
        ->call('placeNode', 'exegese', 0, 0)
        ->call('connect', 'exegese', 'eisegese')
        ->assertCount('edges', 0)
        ->call('placeNode', 'eisegese', 50, 0)
        ->call('connect', 'exegese', 'eisegese')
        ->call('connect', 'exegese', 'eisegese')
        ->assertCount('edges', 1)
        ->call('disconnect', 'exegese', 'eisegese')
        ->assertCount('edges', 0);

    expect(MapEdge::count())->toBe(0);
});

test('removing a node drops its arrows', function () {
    Livewire::test('pages::mapa-conceitos', ['slug' => 'hermeneutica'])
        ->call('placeNode', 'exegese', 0, 0)
        ->call('placeNode', 'eisegese', 50, 0)
        ->call('connect', 'exegese', 'eisegese')
        ->call('removeNode', 'exegese');

    expect(MapNode::count())->toBe(1)->and(MapEdge::count())->toBe(0);
});

test('node whose marker left the notes is hidden', function () {
    MapNode::create(['discipline_id' => $this->discipline->id, 'key' => 'sumiu', 'label' => 'Sumiu', 'x' => 0, 'y' => 0]);

    Livewire::test('pages::mapa-conceitos', ['slug' => 'hermeneutica'])->assertCount('nodes', 0);
});

test('page is reachable', function () {
    $this->get(route('disciplinas.mapa-conceitos', 'hermeneutica'))->assertOk()->assertSee('Exegese');
});

test('note body renders the marker as a chip', function () {
    $html = Livewire::test('pages::disciplina', ['slug' => 'hermeneutica'])
        ->instance()
        ->renderWithLinks('Veja {{no: Exegese}} aqui', collect());

    expect($html)->toContain('rounded-full')->toContain('>Exegese</span>')->not->toContain('{{no:');
});

test('dashboard card links to both concept map and notes', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('disciplinas.mapa-conceitos', 'hermeneutica'), false)
        ->assertSee(route('disciplinas.show', 'hermeneutica'), false);
});

test('bend is saved on the edge and clamped', function () {
    Livewire::test('pages::mapa-conceitos', ['slug' => 'hermeneutica'])
        ->call('placeNode', 'exegese', 0, 0)
        ->call('placeNode', 'eisegese', 50, 0)
        ->call('connect', 'exegese', 'eisegese')
        ->call('bendEdge', 'exegese', 'eisegese', 40.0)
        ->assertSet('edges.0.bend', 40.0)
        ->call('bendEdge', 'exegese', 'eisegese', 99999.0);

    expect(MapEdge::first()->bend)->toBe(2000.0);
});

test('reverse flips direction and bend sign, or drops the edge when the opposite exists', function () {
    $page = Livewire::test('pages::mapa-conceitos', ['slug' => 'hermeneutica'])
        ->call('placeNode', 'exegese', 0, 0)
        ->call('placeNode', 'eisegese', 50, 0)
        ->call('connect', 'exegese', 'eisegese')
        ->call('bendEdge', 'exegese', 'eisegese', 30.0)
        ->call('reverseEdge', 'exegese', 'eisegese');

    expect(MapEdge::first()->only(['from_key', 'to_key', 'bend']))
        ->toBe(['from_key' => 'eisegese', 'to_key' => 'exegese', 'bend' => -30.0]);

    $page->call('connect', 'exegese', 'eisegese')->call('reverseEdge', 'exegese', 'eisegese');

    expect(MapEdge::count())->toBe(1);
});

test('custom node is created, moved, connected and survives without a note marker', function () {
    $page = Livewire::test('pages::mapa-conceitos', ['slug' => 'hermeneutica']);
    $page->call('addCustomNode', '  Minha ideia  ', 10, 20);

    $node = MapNode::firstWhere('custom', true);

    expect($node->label)->toBe('Minha ideia')->and($node->key)->toStartWith('livre:');

    $page->call('placeNode', $node->key, 99, 88)
        ->call('placeNode', 'exegese', 0, 0)
        ->call('connect', 'exegese', $node->key)
        ->assertCount('nodes', 2)
        ->assertCount('edges', 1)
        ->assertCount('pills', 2);

    expect($node->refresh()->only(['x', 'y']))->toBe(['x' => 99.0, 'y' => 88.0]);

    $page->call('removeNode', $node->key);

    expect(MapNode::where('custom', true)->count())->toBe(0)->and(MapEdge::count())->toBe(0);
});

test('blank custom node label is ignored', function () {
    Livewire::test('pages::mapa-conceitos', ['slug' => 'hermeneutica'])->call('addCustomNode', '   ', 0, 0);

    expect(MapNode::count())->toBe(0);
});

test('only custom nodes can be renamed', function () {
    $page = Livewire::test('pages::mapa-conceitos', ['slug' => 'hermeneutica']);
    $page->call('addCustomNode', 'Antigo', 0, 0)->call('placeNode', 'exegese', 0, 0);

    $custom = MapNode::firstWhere('custom', true);

    $page->call('renameNode', $custom->key, '  Novo nome ')
        ->call('renameNode', 'exegese', 'Hackeado')
        ->call('renameNode', $custom->key, '   ');

    expect($custom->refresh()->label)->toBe('Novo nome')
        ->and(MapNode::firstWhere('key', 'exegese')->label)->toBe('Exegese');
});
