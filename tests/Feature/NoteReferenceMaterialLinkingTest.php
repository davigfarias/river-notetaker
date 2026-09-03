<?php

use App\Actions\Orchestrators\SaveNote;
use App\DTO\NotesDTO;
use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\ReferenceMaterial;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    $this->discipline = Disciplines::factory()->create();
});

test('saving a note syncs linked reference materials through the pivot', function () {
    $a = ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id]);
    $b = ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id]);

    $dto = new NotesDTO(
        discipline_id: $this->discipline->id,
        access_token_id: $this->token->id,
        title: 'Aula sobre a Reforma',
        reference_material_ids: [$a->id, $b->id],
    );

    $outcome = app(SaveNote::class)->handle($dto);

    expect($outcome->success)->toBeTrue();

    $note = Notes::firstWhere('title', 'Aula sobre a Reforma');

    expect($note->referenceMaterials->pluck('id')->all())
        ->toEqualCanonicalizing([$a->id, $b->id]);
});

test('the create page links and unlinks a reference material', function () {
    $material = ReferenceMaterial::factory()->create([
        'access_token_id' => $this->token->id,
        'title' => 'Institutas',
    ]);

    Livewire::test('pages::create', ['slug' => $this->discipline->slug])
        ->set('refSearch', 'Institutas')
        ->call('linkReference', $material->id)
        ->assertSet('notes.reference_material_ids', [$material->id])
        ->assertSee('Institutas')
        ->call('unlinkReference', $material->id)
        ->assertSet('notes.reference_material_ids', []);
});

test('the create page can add a brand new work and auto-link it', function () {
    Livewire::test('pages::create', ['slug' => $this->discipline->slug])
        ->set('refForm.title', 'Confissões')
        ->set('refForm.author', 'Agostinho')
        ->set('refForm.type', 'book-open')
        ->call('addNewReference')
        ->assertHasNoErrors();

    $material = ReferenceMaterial::firstWhere('title', 'Confissões');

    expect($material)->not->toBeNull()
        ->and($material->access_token_id)->toBe($this->token->id);

    Livewire::test('pages::create', ['slug' => $this->discipline->slug])
        ->set('refForm.title', 'Cidade de Deus')
        ->set('refForm.type', 'book-open')
        ->call('addNewReference')
        ->assertSet('notes.reference_material_ids', fn ($ids) => in_array(
            ReferenceMaterial::firstWhere('title', 'Cidade de Deus')->id,
            $ids,
            true,
        ));
});
