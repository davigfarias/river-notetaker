<?php

use App\Models\AccessToken;
use App\Models\NoteComment;
use App\Models\Notes;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);

    Livewire::withoutLazyLoading();

    $this->note = Notes::factory()->create([
        'access_token_id' => $this->token->id,
        'summary' => 'A graça de Deus é imerecida e transforma o coração.',
    ]);
});

function commentSnippet(string $slug, string $field, string $snippet, string $body): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test('pages::disciplina', ['slug' => $slug])
        ->call('startCommenting', $field, $snippet)
        ->set('commentBody', $body)
        ->call('saveComment');
}

test('a snippet can be commented even without linkable principles, and is rendered highlighted', function () {
    $component = commentSnippet($this->note->discipline->slug, 'summary', 'graça de Deus', 'Ef 2:8-9');

    $comment = NoteComment::sole();

    expect($comment->only('note_id', 'snippet', 'body'))
        ->toBe(['note_id' => $this->note->id, 'snippet' => 'graça de Deus', 'body' => 'Ef 2:8-9']);

    $component->assertSeeHtml('wire:click="viewComment('.$comment->id.')"')
        ->assertSeeHtml('aria-label="Comentar trecho"');
});

test('a comment body is required', function () {
    commentSnippet($this->note->discipline->slug, 'summary', 'graça de Deus', '')
        ->assertHasErrors(['commentBody' => 'required']);

    expect(NoteComment::count())->toBe(0);
});

test('an invalid field is ignored', function () {
    commentSnippet($this->note->discipline->slug, 'title', 'graça', 'x');

    expect(NoteComment::count())->toBe(0);
});

test('clicking a comment opens it for editing and saving updates it', function () {
    $comment = NoteComment::create(['note_id' => $this->note->id, 'field' => 'summary', 'snippet' => 'graça de Deus', 'body' => 'Antes']);

    Livewire::test('pages::disciplina', ['slug' => $this->note->discipline->slug])
        ->call('viewComment', $comment->id)
        ->assertSet('commentBody', 'Antes')
        ->set('commentBody', 'Depois')
        ->call('saveComment');

    expect($comment->fresh()->body)->toBe('Depois')
        ->and(NoteComment::count())->toBe(1);
});

test('a comment can be deleted, removing the highlight', function () {
    $comment = NoteComment::create(['note_id' => $this->note->id, 'field' => 'summary', 'snippet' => 'graça de Deus', 'body' => 'x']);

    Livewire::test('pages::disciplina', ['slug' => $this->note->discipline->slug])
        ->assertSeeHtml('data-comment-id="'.$comment->id.'"')
        ->call('viewComment', $comment->id)
        ->call('deleteComment')
        ->assertDontSeeHtml('data-comment-id="'.$comment->id.'"');

    $this->assertDatabaseMissing('note_comments', ['id' => $comment->id]);
});
