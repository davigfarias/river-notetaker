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
});

test('a concept used in a topic shows a link to it on the concepts page', function () {
    $concept = Concepts::create(['term' => 'Graça', 'definition' => 'Favor imerecido de Deus para com o pecador.']);
    $topic = PrincipleTopic::factory()->create(['title' => 'Soteriologia', 'slug' => 'soteriologia']);
    Principle::factory()->forConcept($concept->id)->create(['principle_topic_id' => $topic->id]);

    Livewire::test('pages::concepts')
        ->assertSee('Graça')
        ->assertSee('Soteriologia');
});

test('a concept not used in any topic has no usage entries', function () {
    Concepts::create(['term' => 'Fé', 'definition' => 'Confiança que descansa na obra consumada de Cristo.']);

    $component = Livewire::test('pages::concepts');

    expect($component->get('conceptUsages'))->toBeEmpty();
});
