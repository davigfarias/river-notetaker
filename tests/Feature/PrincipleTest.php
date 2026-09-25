<?php

use App\Enums\PrincipleType;
use App\Models\AccessToken;
use App\Models\Concepts;
use App\Models\Principle;
use App\Models\PrincipleTopic;
use Livewire\Livewire;

beforeEach(function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    Livewire::withoutLazyLoading();

    $this->topic = PrincipleTopic::factory()->create(['title' => 'Soteriologia', 'slug' => 'soteriologia']);
});

test('an existing concept can be added to the topic', function () {
    $concept = Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido de Deus para com o pecador.']);

    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->set('conceptSearch', 'Graça')
        ->call('selectConcept', $concept->id)
        ->call('addConcept')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('principles', [
        'principle_topic_id' => $this->topic->id,
        'concept_id' => $concept->id,
        'type' => PrincipleType::Concept->value,
    ]);
});

test('the same concept cannot be added to a topic twice', function () {
    $concept = Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido de Deus para com o pecador.']);
    Principle::factory()->forConcept($concept->id)->create(['principle_topic_id' => $this->topic->id]);

    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->call('selectConcept', $concept->id)
        ->call('addConcept');

    expect(Principle::where('principle_topic_id', $this->topic->id)->where('concept_id', $concept->id)->count())->toBe(1);
});

test('a free text principle can be added to the topic', function () {
    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->set('textForm.title', 'Sola Gratia')
        ->set('textForm.body', 'A salvação é inteiramente pela graça, sem mérito humano.')
        ->call('addText')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('principles', [
        'principle_topic_id' => $this->topic->id,
        'type' => PrincipleType::Text->value,
        'title' => 'Sola Gratia',
    ]);
});

test('a text principle can be edited', function () {
    $principle = Principle::factory()->create([
        'principle_topic_id' => $this->topic->id,
        'title' => 'Sola Gratia',
        'body' => 'Rascunho inicial.',
    ]);

    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->call('startEditingText', $principle->id)
        ->assertSet('textForm.title', 'Sola Gratia')
        ->set('textForm.title', 'Sola Gratia et Fide')
        ->set('textForm.body', 'Texto revisado.')
        ->call('updateText')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('principles', [
        'id' => $principle->id,
        'title' => 'Sola Gratia et Fide',
        'body' => 'Texto revisado.',
    ]);
});

test('a concept principle cannot be edited as text', function () {
    $concept = Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido de Deus para com o pecador.']);
    $principle = Principle::factory()->forConcept($concept->id)->create(['principle_topic_id' => $this->topic->id]);

    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->call('startEditingText', $principle->id)
        ->assertSet('editingPrincipleId', null);
});

test('a principle entry can be deleted', function () {
    $principle = Principle::factory()->create(['principle_topic_id' => $this->topic->id]);

    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->call('confirmDeletePrinciple', $principle->id)
        ->call('deletePrinciple');

    $this->assertDatabaseMissing('principles', ['id' => $principle->id]);
});

test('principles can be reordered', function () {
    $first = Principle::factory()->create(['principle_topic_id' => $this->topic->id, 'position' => 0, 'title' => 'Primeiro']);
    $second = Principle::factory()->create(['principle_topic_id' => $this->topic->id, 'position' => 1, 'title' => 'Segundo']);
    $third = Principle::factory()->create(['principle_topic_id' => $this->topic->id, 'position' => 2, 'title' => 'Terceiro']);

    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->call('movePrinciple', $third->id, 0);

    expect(PrincipleTopic::find($this->topic->id)->principles->pluck('title')->all())
        ->toBe(['Terceiro', 'Primeiro', 'Segundo']);
});

test('a newly added principle goes to the end of the order', function () {
    Principle::factory()->create(['principle_topic_id' => $this->topic->id, 'position' => 0]);

    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->set('textForm.title', 'Novo')
        ->set('textForm.body', 'Corpo do princípio.')
        ->call('addText');

    $this->assertDatabaseHas('principles', ['principle_topic_id' => $this->topic->id, 'title' => 'Novo', 'position' => 1]);
});
