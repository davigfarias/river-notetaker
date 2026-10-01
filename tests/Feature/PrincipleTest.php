<?php

use App\Enums\PrincipleType;
use App\Models\AccessToken;
use App\Models\Concepts;
use App\Models\Principle;
use App\Models\PrincipleCategory;
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

test('a category can be created in the topic', function () {
    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->set('categoryForm.title', 'Ordo salutis')
        ->call('createCategory')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('principle_categories', ['principle_topic_id' => $this->topic->id, 'title' => 'Ordo salutis']);
});

test('a principle can be moved into a category and back out', function () {
    $category = PrincipleCategory::factory()->create(['principle_topic_id' => $this->topic->id]);
    $principle = Principle::factory()->create(['principle_topic_id' => $this->topic->id]);

    $component = Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->call('movePrinciple', $principle->id, 0, (string) $category->id);

    expect($principle->fresh()->principle_category_id)->toBe($category->id);

    $component->call('movePrinciple', $principle->id, 0, '');

    expect($principle->fresh()->principle_category_id)->toBeNull();
});

test('reordering inside a category does not touch other lists', function () {
    $category = PrincipleCategory::factory()->create(['principle_topic_id' => $this->topic->id]);
    $loose = Principle::factory()->create(['principle_topic_id' => $this->topic->id, 'position' => 0]);
    $a = Principle::factory()->create(['principle_topic_id' => $this->topic->id, 'principle_category_id' => $category->id, 'position' => 0, 'title' => 'A']);
    $b = Principle::factory()->create(['principle_topic_id' => $this->topic->id, 'principle_category_id' => $category->id, 'position' => 1, 'title' => 'B']);

    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->call('movePrinciple', $b->id, 0, (string) $category->id);

    expect($category->principles()->pluck('title')->all())->toBe(['B', 'A'])
        ->and($loose->fresh()->principle_category_id)->toBeNull();
});

test('a category from another topic is rejected', function () {
    $foreign = PrincipleCategory::factory()->create();
    $principle = Principle::factory()->create(['principle_topic_id' => $this->topic->id]);

    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->call('movePrinciple', $principle->id, 0, (string) $foreign->id);

    expect($principle->fresh()->principle_category_id)->toBeNull();
});

test('deleting a category keeps its principles', function () {
    $category = PrincipleCategory::factory()->create(['principle_topic_id' => $this->topic->id]);
    $principle = Principle::factory()->create(['principle_topic_id' => $this->topic->id, 'principle_category_id' => $category->id]);

    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->call('confirmDeleteCategory', $category->id)
        ->call('deleteCategory');

    $this->assertDatabaseMissing('principle_categories', ['id' => $category->id]);
    expect($principle->fresh()->principle_category_id)->toBeNull();
});

test('categories and their principles are rendered', function () {
    $category = PrincipleCategory::factory()->create(['principle_topic_id' => $this->topic->id, 'title' => 'Ordo salutis']);
    Principle::factory()->create(['principle_topic_id' => $this->topic->id, 'principle_category_id' => $category->id, 'title' => 'Chamado eficaz']);

    Livewire::test('pages::principio', ['slug' => $this->topic->slug])
        ->assertSee('Ordo salutis')
        ->assertSee('Chamado eficaz');
});
