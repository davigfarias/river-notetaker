<?php

use App\Models\AccessToken;
use App\Models\ReferenceGroup;
use App\Models\ReferenceMaterial;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();

    $this->material = ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Institutas']);
    $this->other = ReferenceMaterial::factory()->create(['access_token_id' => $this->token->id, 'title' => 'Confissões']);
    $this->group = ReferenceGroup::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Patrística']);
});

test('a group is created with a trimmed name', function () {
    Livewire::test('pages::referencias')
        ->set('groupForm.name', '  Reforma  ')
        ->call('addGroup')
        ->assertHasNoErrors()
        ->assertSet('groupForm.name', '');

    $this->assertDatabaseHas('reference_groups', ['access_token_id' => $this->token->id, 'name' => 'Reforma']);
});

test('invalid group names show a message in portuguese and create nothing', function (string $name, string $message) {
    Livewire::test('pages::referencias')
        ->set('groupForm.name', $name)
        ->call('addGroup')
        ->assertHasErrors('groupForm.name')
        ->assertSee($message);

    expect(ReferenceGroup::count())->toBe(1);
})->with([
    'blank' => ['   ', 'Digite um nome para o grupo.'],
    'too long' => [str_repeat('a', 61), 'no máximo 60 caracteres'],
    'same name ignoring case and accents' => ['PATRISTICA', 'Você já tem um grupo chamado'],
]);

test('two owners can use the same group name', function () {
    ReferenceGroup::factory()->create(['name' => 'Reforma']);

    Livewire::test('pages::referencias')
        ->set('groupForm.name', 'Reforma')
        ->call('addGroup')
        ->assertHasNoErrors();

    expect(ReferenceGroup::where('name', 'Reforma')->count())->toBe(2);
});

test('a work can be toggled in and out of several groups', function () {
    $second = ReferenceGroup::factory()->create(['access_token_id' => $this->token->id]);

    $page = Livewire::test('pages::referencias')
        ->call('toggleGroup', $this->group->id, $this->material->id)
        ->call('toggleGroup', $second->id, $this->material->id);

    expect($this->material->groups()->count())->toBe(2);

    $page->call('toggleGroup', $second->id, $this->material->id);

    expect($this->material->groups()->pluck('reference_groups.id')->all())->toBe([$this->group->id]);
});

test('the filter lists only the works of the chosen group', function () {
    $this->group->materials()->attach($this->material);

    Livewire::test('pages::referencias')
        ->set('group', (string) $this->group->id)
        ->assertSee('Institutas')
        ->assertDontSee('Confissões');
});

test('an empty group explains how to fill it', function () {
    Livewire::test('pages::referencias')
        ->set('group', (string) $this->group->id)
        ->assertSee('Este grupo ainda não tem obras');
});

test('no groups yet shows an explicit message', function () {
    $this->group->delete();

    Livewire::test('pages::referencias')->assertSee('Nenhum grupo ainda');
});

test('a deleted, foreign or garbage group in the url lists everything', function (string $value) {
    Livewire::withQueryParams(['grupo' => $value === 'foreign' ? (string) ReferenceGroup::factory()->create()->id : $value])
        ->test('pages::referencias')
        ->assertSee('Institutas')
        ->assertSee('Confissões');
})->with(['foreign', '999999', 'lixo']);

test('deleting the active group keeps the works and clears the filter', function () {
    $this->group->materials()->attach($this->material);

    Livewire::test('pages::referencias')
        ->set('group', (string) $this->group->id)
        ->call('deleteActiveGroup')
        ->assertSet('group', '')
        ->assertSee('Confissões');

    expect(ReferenceGroup::find($this->group->id))->toBeNull()
        ->and(ReferenceMaterial::find($this->material->id))->not->toBeNull();

    $this->assertDatabaseMissing('reference_group_material', ['reference_material_id' => $this->material->id]);
});

test('groups and works of another owner cannot be touched', function () {
    $foreignGroup = ReferenceGroup::factory()->create();
    $foreignMaterial = ReferenceMaterial::factory()->create(['access_token_id' => $foreignGroup->access_token_id]);

    Livewire::test('pages::referencias')
        ->call('toggleGroup', $foreignGroup->id, $this->material->id)
        ->call('toggleGroup', $this->group->id, $foreignMaterial->id)
        ->set('group', (string) $foreignGroup->id)
        ->call('deleteActiveGroup')
        ->assertDontSee($foreignGroup->name);

    $this->assertDatabaseCount('reference_group_material', 0);
    expect(ReferenceGroup::find($foreignGroup->id))->not->toBeNull();
});

test('the card keeps linking to the work', function () {
    $this->get(route('referencias'))->assertOk();

    Livewire::test('pages::referencias')->assertSeeHtml('href="'.route('referencias.show', $this->material->id).'"');
});
