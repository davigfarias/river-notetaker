<?php

use App\Models\AccessToken;
use App\Models\PrincipleTopic;
use Livewire\Livewire;

beforeEach(function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    Livewire::withoutLazyLoading();
});

test('creating a topic redirects to its show page', function () {
    Livewire::test('pages::principios')
        ->set('form.title', 'Soteriologia')
        ->call('createTopic')
        ->assertHasNoErrors()
        ->assertRedirect(route('principios.show', 'soteriologia'));

    $this->assertDatabaseHas('principle_topics', ['title' => 'Soteriologia', 'slug' => 'soteriologia']);
});

test('a duplicate title is rejected', function () {
    PrincipleTopic::factory()->create(['title' => 'Soteriologia', 'slug' => 'soteriologia']);

    Livewire::test('pages::principios')
        ->set('form.title', 'Soteriologia')
        ->call('createTopic');

    expect(PrincipleTopic::where('title', 'Soteriologia')->count())->toBe(1);
});

test('existing topics are listed', function () {
    PrincipleTopic::factory()->create(['title' => 'Cristologia', 'slug' => 'cristologia']);

    Livewire::test('pages::principios')->assertSee('Cristologia');
});
