<?php

use App\Models\AccessToken;
use App\Models\Disciplines;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $token->id]);

    Livewire::withoutLazyLoading();
});

function createDiscipline(string $title, ?int $period = null, array $attributes = []): Disciplines
{
    return Disciplines::create([
        'title' => $title,
        'slug' => Str::slug($title),
        'icon' => 'book-open',
        'period' => $period,
        ...$attributes,
    ]);
}

test('the dashboard groups disciplines by period and keeps the ones without a period apart', function () {
    createDiscipline('Hermenêutica', 1);
    createDiscipline('História da Igreja', 1);
    createDiscipline('Teologia Sistemática', 3);
    createDiscipline('Homilética');

    $groups = Livewire::test('pages::dashboard')
        ->get('groupedDisciplines');

    expect($groups->pluck('label')->all())->toBe(['1º período', '3º período', 'Sem período'])
        ->and($groups->firstWhere('label', '1º período')['disciplines']->pluck('title')->all())
        ->toBe(['Hermenêutica', 'História da Igreja'])
        ->and($groups->firstWhere('label', 'Sem período')['disciplines']->pluck('title')->all())
        ->toBe(['Homilética']);
});

test('only the periods that have disciplines become filter buttons', function () {
    createDiscipline('Hermenêutica', 2);
    createDiscipline('Grego I', 5);

    $component = Livewire::test('pages::dashboard');

    expect($component->get('availablePeriods')->all())->toBe([2, 5]);
});

test('selecting a period filters the disciplines shown', function () {
    createDiscipline('Hermenêutica', 1);
    createDiscipline('Teologia Sistemática', 3);

    $groups = Livewire::test('pages::dashboard')
        ->set('selectedPeriod', '3')
        ->get('groupedDisciplines');

    expect($groups)->toHaveCount(1)
        ->and($groups->first()['disciplines']->pluck('title')->all())->toBe(['Teologia Sistemática']);
});

test('the sem período filter shows only disciplines without a period', function () {
    createDiscipline('Hermenêutica', 1);
    createDiscipline('Homilética');

    $groups = Livewire::test('pages::dashboard')
        ->set('selectedPeriod', 'sem')
        ->get('groupedDisciplines');

    expect($groups)->toHaveCount(1)
        ->and($groups->first()['disciplines']->pluck('title')->all())->toBe(['Homilética']);
});

test('a new discipline is created with period, code and professor', function () {
    Livewire::test('pages::dashboard')
        ->call('openCreateModal')
        ->set('dto.title', 'Homilética')
        ->set('dto.period', '2')
        ->set('dto.code', 'HOM-201')
        ->set('dto.professor', 'Rev. João da Silva')
        ->call('saveDiscipline')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('disciplines', [
        'title' => 'Homilética',
        'slug' => 'homiletica',
        'period' => 2,
        'code' => 'HOM-201',
        'professor' => 'Rev. João da Silva',
    ]);
});

test('creating a discipline while a period filter is active preselects that period', function () {
    createDiscipline('Hermenêutica', 4);

    $component = Livewire::test('pages::dashboard')
        ->set('selectedPeriod', '4')
        ->call('openCreateModal');

    expect($component->get('dto')->period)->toBe(4);
});

test('editing a discipline updates its name, period, code, professor and slug', function () {
    $discipline = createDiscipline('Teologia Sistematica', 1);

    Livewire::test('pages::dashboard')
        ->call('openEditModal', $discipline->id)
        ->assertSet('isEditing', true)
        ->set('dto.title', 'Teologia Sistemática I')
        ->set('dto.period', '3')
        ->set('dto.code', 'TEO-101')
        ->set('dto.professor', 'Rev. Pedro Lima')
        ->call('saveDiscipline')
        ->assertHasNoErrors()
        ->assertSet('showFormModal', false);

    $this->assertDatabaseHas('disciplines', [
        'id' => $discipline->id,
        'title' => 'Teologia Sistemática I',
        'slug' => 'teologia-sistematica-i',
        'period' => 3,
        'code' => 'TEO-101',
        'professor' => 'Rev. Pedro Lima',
    ]);
});

test('clearing the period on edit moves the discipline to the sem período group', function () {
    $discipline = createDiscipline('Hermenêutica', 1);

    $component = Livewire::test('pages::dashboard')
        ->call('openEditModal', $discipline->id)
        ->set('dto.period', '')
        ->call('saveDiscipline')
        ->assertHasNoErrors();

    expect($component->get('groupedDisciplines')->pluck('label')->all())->toBe(['Sem período']);
});

test('an out of range period is rejected', function () {
    Livewire::test('pages::dashboard')
        ->call('openCreateModal')
        ->set('dto.title', 'Homilética')
        ->set('dto.period', '13')
        ->call('saveDiscipline')
        ->assertHasErrors(['dto.period']);

    $this->assertDatabaseMissing('disciplines', ['title' => 'Homilética']);
});

test('the dashboard renders the period headings, the filter buttons and the discipline details', function () {
    createDiscipline('Hermenêutica', 1, ['code' => 'HER-101', 'professor' => 'Rev. Silva']);
    createDiscipline('Homilética');

    Livewire::test('pages::dashboard')
        ->assertSee('1º período')
        ->assertSee('Sem período')
        ->assertSee('Todas')
        ->assertSee('HER-101')
        ->assertSee('Rev. Silva');
});
