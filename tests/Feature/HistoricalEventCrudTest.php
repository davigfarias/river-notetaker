<?php

use App\Enums\Era;
use App\Enums\HistoricalEventNature;
use App\Models\AccessToken;
use App\Models\HistoricalEvent;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();
});

test('the historico page loads', function () {
    $this->get(route('historico'))->assertOk();
});

test('an event before Christ is stored with a negative sort key', function () {
    Livewire::test('pages::historico')
        ->set('eventForm.title', 'Assassinato de Júlio César')
        ->set('eventForm.nature', HistoricalEventNature::Event->value)
        ->set('eventForm.start_year', 44)
        ->set('eventForm.start_era', Era::BeforeChrist->value)
        ->call('addEvent')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('historical_events', [
        'access_token_id' => $this->token->id,
        'title' => 'Assassinato de Júlio César',
        'start_year' => 44,
        'start_era' => 'bc',
        'end_year' => null,
        'end_era' => null,
        'sort_key' => -44,
    ]);
});

test('an event can span a range across eras', function () {
    Livewire::test('pages::historico')
        ->set('eventForm.title', 'Principado de Augusto')
        ->set('eventForm.nature', HistoricalEventNature::Person->value)
        ->set('eventForm.start_year', 27)
        ->set('eventForm.start_era', Era::BeforeChrist->value)
        ->set('eventForm.end_year', 14)
        ->set('eventForm.end_era', Era::AnnoDomini->value)
        ->call('addEvent')
        ->assertHasNoErrors()
        ->assertSee('27 a.C. – 14 d.C.');
});

test('title and start year are required', function () {
    Livewire::test('pages::historico')
        ->set('eventForm.title', '')
        ->set('eventForm.start_year', null)
        ->call('addEvent')
        ->assertHasErrors(['eventForm.title', 'eventForm.start_year']);
});

test('year zero is rejected', function () {
    Livewire::test('pages::historico')
        ->set('eventForm.title', 'Ano inexistente')
        ->set('eventForm.start_year', 0)
        ->call('addEvent')
        ->assertHasErrors('eventForm.start_year');
});

test('an invalid nature is rejected', function () {
    Livewire::test('pages::historico')
        ->set('eventForm.title', 'Algo')
        ->set('eventForm.start_year', 1500)
        ->set('eventForm.nature', 'novela')
        ->call('addEvent')
        ->assertHasErrors('eventForm.nature');
});

test('the end cannot come before the start', function () {
    Livewire::test('pages::historico')
        ->set('eventForm.title', 'Intervalo invertido')
        ->set('eventForm.start_year', 10)
        ->set('eventForm.start_era', Era::AnnoDomini->value)
        ->set('eventForm.end_year', 10)
        ->set('eventForm.end_era', Era::BeforeChrist->value)
        ->call('addEvent')
        ->assertHasErrors('eventForm.end_year');

    expect(HistoricalEvent::count())->toBe(0);
});

test('events are listed in chronological order', function () {
    HistoricalEvent::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Noventa e Cinco Teses', 'start_year' => 1517]);
    HistoricalEvent::factory()->beforeChrist()->create(['access_token_id' => $this->token->id, 'title' => 'Guerra do Peloponeso', 'start_year' => 431]);
    HistoricalEvent::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Concílio de Niceia', 'start_year' => 325]);
    HistoricalEvent::factory()->beforeChrist()->create(['access_token_id' => $this->token->id, 'title' => 'Morte de César', 'start_year' => 44]);

    Livewire::test('pages::historico')
        ->assertSeeInOrder(['Guerra do Peloponeso', 'Morte de César', 'Concílio de Niceia', 'Noventa e Cinco Teses']);
});

test('events of another access token are neither listed nor editable', function () {
    $other = AccessToken::factory()->create();
    $foreign = HistoricalEvent::factory()->create(['access_token_id' => $other->id, 'title' => 'Evento alheio']);

    Livewire::test('pages::historico')
        ->assertDontSee('Evento alheio')
        ->call('confirmDeleteEvent', $foreign->id)
        ->call('deleteEvent');

    $this->assertDatabaseHas('historical_events', ['id' => $foreign->id]);
});

test('an event can be edited', function () {
    $event = HistoricalEvent::factory()->create([
        'access_token_id' => $this->token->id,
        'title' => 'Institutas',
        'nature' => HistoricalEventNature::Book,
        'start_year' => 1535,
    ]);

    Livewire::test('pages::historico')
        ->call('editEvent', $event->id)
        ->assertSet('editEventForm.title', 'Institutas')
        ->set('editEventForm.start_year', 1536)
        ->call('updateEvent')
        ->assertHasNoErrors()
        ->assertSet('editingEvent', false);

    expect($event->fresh())
        ->start_year->toBe(1536)
        ->sort_key->toBe(1536);
});

test('an event can be deleted', function () {
    $event = HistoricalEvent::factory()->create(['access_token_id' => $this->token->id]);

    Livewire::test('pages::historico')
        ->call('confirmDeleteEvent', $event->id)
        ->call('deleteEvent');

    $this->assertModelMissing($event);
});

test('the search filters by title and description', function () {
    HistoricalEvent::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Concílio de Niceia', 'description' => null]);
    HistoricalEvent::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Sínodo de Dort', 'description' => 'Resposta aos remonstrantes.']);
    HistoricalEvent::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Magna Carta', 'description' => null]);

    Livewire::test('pages::historico')
        ->set('search', 'NICEIA')
        ->assertSee('Concílio de Niceia')
        ->assertDontSee('Magna Carta')
        ->set('search', 'remonstrantes')
        ->assertSee('Sínodo de Dort')
        ->assertDontSee('Concílio de Niceia');
});

test('a numeric search matches the start or end year', function () {
    HistoricalEvent::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Noventa e Cinco Teses', 'start_year' => 1517]);
    HistoricalEvent::factory()->withRange(1453)->create(['access_token_id' => $this->token->id, 'title' => 'Guerra dos Cem Anos', 'start_year' => 1337]);
    HistoricalEvent::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Magna Carta', 'start_year' => 1215]);

    Livewire::test('pages::historico')
        ->set('search', '1517')
        ->assertSee('Noventa e Cinco Teses')
        ->assertDontSee('Magna Carta')
        ->set('search', '1453')
        ->assertSee('Guerra dos Cem Anos')
        ->assertDontSee('Noventa e Cinco Teses');
});

test('an empty search result says nothing was found', function () {
    HistoricalEvent::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Magna Carta']);

    Livewire::test('pages::historico')
        ->set('search', 'inexistente')
        ->assertSee('Nada encontrado para');
});
