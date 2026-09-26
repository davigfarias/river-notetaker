<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\PrincipleTopic;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);

    Livewire::withoutLazyLoading();

    $this->note = Notes::factory()->create(['access_token_id' => $this->token->id]);
    $this->discipline = $this->note->discipline;

    $this->topic = PrincipleTopic::factory()->create(['title' => 'Hermenêutica', 'slug' => 'hermeneutica']);
});

test('a topic can be linked to a discipline from the discipline screen', function () {
    Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->call('toggleTopic', $this->topic->id);

    $this->assertDatabaseHas('discipline_principle_topic', [
        'discipline_id' => $this->discipline->id,
        'principle_topic_id' => $this->topic->id,
    ]);
});

test('picking a topic in the select links it and clears the select', function () {
    Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->set('topicToAdd', $this->topic->id)
        ->assertSet('topicToAdd', null);

    $this->assertDatabaseHas('discipline_principle_topic', [
        'discipline_id' => $this->discipline->id,
        'principle_topic_id' => $this->topic->id,
    ]);
});

test('toggling an already linked topic unlinks it', function () {
    $this->discipline->principleTopics()->attach($this->topic->id);

    Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->call('toggleTopic', $this->topic->id);

    $this->assertDatabaseMissing('discipline_principle_topic', [
        'discipline_id' => $this->discipline->id,
        'principle_topic_id' => $this->topic->id,
    ]);
});

test('a discipline can draw from more than one topic', function () {
    $secondTopic = PrincipleTopic::factory()->create(['title' => 'Teologia Sistemática', 'slug' => 'teologia-sistematica']);

    $component = Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->call('toggleTopic', $this->topic->id)
        ->call('toggleTopic', $secondTopic->id);

    expect($component->get('linkedTopicIds'))->toContain($this->topic->id, $secondTopic->id);
});
