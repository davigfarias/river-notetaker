<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\Principle;
use App\Models\PrincipleNoteLink;
use App\Models\PrincipleTopic;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);

    Livewire::withoutLazyLoading();

    $this->note = Notes::factory()->create([
        'access_token_id' => $this->token->id,
        'summary' => 'A graça de Deus é imerecida e transforma o coração.',
    ]);
    $this->discipline = $this->note->discipline;

    $this->topic = PrincipleTopic::factory()->create();
    $this->discipline->principleTopics()->attach($this->topic->id);

    $this->principle = Principle::factory()->create(['principle_topic_id' => $this->topic->id, 'title' => 'Sola Gratia']);
});

function linkSnippet(string $slug, string $field, string $snippet, int $principleId): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test('pages::disciplina', ['slug' => $slug])
        ->call('startLinkingPrinciple', $field, $snippet)
        ->call('linkPendingPrinciple', $principleId);
}

test('a snippet of a note can be linked to a principle of a topic linked to the discipline', function () {
    linkSnippet($this->discipline->slug, 'summary', 'graça de Deus é imerecida', $this->principle->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('principle_note_links', [
        'principle_id' => $this->principle->id,
        'note_id' => $this->note->id,
        'field' => 'summary',
        'snippet' => 'graça de Deus é imerecida',
    ]);
});

test('linking the same snippet twice to the same principle is blocked', function () {
    linkSnippet($this->discipline->slug, 'summary', 'graça de Deus', $this->principle->id);
    linkSnippet($this->discipline->slug, 'summary', 'graça de Deus', $this->principle->id);

    expect(PrincipleNoteLink::where('principle_id', $this->principle->id)->count())->toBe(1);
});

test('a discipline can draw linkable principles from more than one topic', function () {
    $otherTopic = PrincipleTopic::factory()->create();
    $this->discipline->principleTopics()->attach($otherTopic->id);
    $otherPrinciple = Principle::factory()->create(['principle_topic_id' => $otherTopic->id, 'title' => 'Sola Fide']);

    $component = Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug]);

    expect($component->get('linkablePrinciples')->pluck('id')->all())
        ->toContain($this->principle->id, $otherPrinciple->id);
});

test('linkable principles are empty when the discipline has no linked topic', function () {
    $otherDiscipline = Disciplines::factory()->create(['title' => 'Disciplina sem tema linkado']);
    $otherNote = Notes::factory()->create(['access_token_id' => $this->token->id, 'discipline_id' => $otherDiscipline->id]);

    $component = Livewire::test('pages::disciplina', ['slug' => $otherNote->discipline->slug]);

    expect($component->get('linkablePrinciples'))->toBeEmpty();
});

test('a linked snippet is rendered highlighted, and unlinking removes the highlight', function () {
    $component = linkSnippet($this->discipline->slug, 'summary', 'graça de Deus é imerecida', $this->principle->id);

    $link = PrincipleNoteLink::first();

    $component->assertSee('data-link-id="'.$link->id.'"', false);

    $component->call('unlinkPrincipleFromNote', $link->id);

    $component->assertDontSee('data-link-id="'.$link->id.'"', false);
    $this->assertDatabaseMissing('principle_note_links', ['id' => $link->id]);
});

test('editing away the linked snippet stops it from being highlighted without erroring', function () {
    linkSnippet($this->discipline->slug, 'summary', 'graça de Deus é imerecida', $this->principle->id);

    $link = PrincipleNoteLink::first();

    $this->note->update(['summary' => 'Texto totalmente reescrito, sem o trecho original.']);

    Livewire::test('pages::disciplina', ['slug' => $this->discipline->slug])
        ->assertOk()
        ->assertDontSee('data-link-id="'.$link->id.'"', false);

    $this->assertDatabaseHas('principle_note_links', ['id' => $link->id]);
});
