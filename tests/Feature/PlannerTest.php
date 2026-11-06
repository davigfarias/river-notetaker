<?php

use App\Actions\AddPlannerItems;
use App\Actions\TogglePlannerItemRead;
use App\Models\AccessToken;
use App\Models\Chapter;
use App\Models\Planner;
use App\Models\PlannerItem;
use App\Models\ReferenceMaterial;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);

    $this->book = ReferenceMaterial::factory()->book()->create(['access_token_id' => $this->token->id, 'title' => 'Institutas']);
    $this->chapters = Chapter::factory()->count(3)->sequence(
        ['title' => 'Livro I', 'position' => 0],
        ['title' => 'Livro II', 'position' => 1],
        ['title' => 'Livro III', 'position' => 2],
    )->create(['reference_material_id' => $this->book->id]);
    $this->article = ReferenceMaterial::factory()->article()->create(['access_token_id' => $this->token->id, 'title' => 'Sola Fide']);

    $this->planner = Planner::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Justificação']);

    Livewire::withoutLazyLoading();
});

test('a lista mostra o aviso de vazio e a navegação Obras | Planners', function () {
    Planner::query()->delete();

    Livewire::test('pages::planners')
        ->assertSee('Nenhum planner ainda.')
        ->assertSee('Obras')
        ->assertSee('Planners');

    Livewire::test('pages::referencias')->assertSee('Planners');
});

test('criar um planner pede nome e aceita objetivo e data-alvo opcionais', function () {
    Livewire::test('pages::planners')
        ->set('form.name', '')
        ->call('create')
        ->assertHasErrors('form.name');

    Livewire::test('pages::planners')
        ->set('form.name', 'Graça comum')
        ->call('create')
        ->assertHasNoErrors();

    $created = Planner::where('name', 'Graça comum')->sole();

    expect($created->objective)->toBeNull()
        ->and($created->target_date)->toBeNull()
        ->and($created->access_token_id)->toBe($this->token->id);
});

test('adiciona capítulos e obras sem capítulos, sem duplicar nem aceitar o que não é do usuário', function () {
    $foreign = ReferenceMaterial::factory()->article()->create(['access_token_id' => AccessToken::factory()->create()->id]);
    $keys = ['c'.$this->chapters[0]->id, 'c'.$this->chapters[1]->id, 'm'.$this->article->id, 'm'.$this->book->id, 'm'.$foreign->id, 'lixo', 'c999999'];

    $action = app(AddPlannerItems::class);

    expect($action->handle($this->planner->id, $keys, $this->token->id)->success)->toBeTrue()
        ->and($this->planner->items()->count())->toBe(3)
        ->and($this->planner->items()->whereNull('chapter_id')->sole()->reference_material_id)->toBe($this->article->id);

    $again = $action->handle($this->planner->id, $keys, $this->token->id);

    expect($again->success)->toBeFalse()
        ->and($again->message)->toContain('já está no planner')
        ->and($this->planner->items()->count())->toBe(3);
});

test('o seletor da tela adiciona o que foi marcado', function () {
    Livewire::test('pages::planner', ['id' => $this->planner->id])
        ->assertSee('Este planner ainda está vazio.')
        ->call('openAdd')
        ->set('selection', ['c'.$this->chapters[2]->id])
        ->call('addItems')
        ->assertSee('Livro III');

    expect($this->planner->items()->count())->toBe(1);
});

test('adicionar sem marcar nada avisa em vez de falhar calado', function () {
    Livewire::test('pages::planner', ['id' => $this->planner->id])
        ->call('openAdd')
        ->call('addItems')
        ->assertSet('adding', true);

    expect($this->planner->items()->count())->toBe(0);
});

test('o check de leitura é do planner: marcar num não marca no outro', function () {
    $other = Planner::factory()->create(['access_token_id' => $this->token->id]);
    $mine = PlannerItem::factory()->create(['planner_id' => $this->planner->id, 'reference_material_id' => $this->book->id, 'chapter_id' => $this->chapters[0]->id]);
    $theirs = PlannerItem::factory()->create(['planner_id' => $other->id, 'reference_material_id' => $this->book->id, 'chapter_id' => $this->chapters[0]->id]);

    Livewire::test('pages::planner', ['id' => $this->planner->id])->call('toggle', $mine->id);

    expect($mine->refresh()->isRead())->toBeTrue()
        ->and($theirs->refresh()->isRead())->toBeFalse();

    Livewire::test('pages::planner', ['id' => $this->planner->id])->call('toggle', $mine->id);

    expect($mine->refresh()->isRead())->toBeFalse();
});

test('a barra avança com os checks e o planner conclui em 100%', function () {
    $items = collect($this->chapters)->map(fn (Chapter $chapter) => PlannerItem::factory()->create([
        'planner_id' => $this->planner->id,
        'reference_material_id' => $this->book->id,
        'chapter_id' => $chapter->id,
    ]));

    $component = Livewire::test('pages::planner', ['id' => $this->planner->id])->assertSee('0%');

    $component->call('toggle', $items[0]->id)->assertSee('33%')->assertSee('1 de 3 itens lidos');
    $component->call('toggle', $items[1]->id)->call('toggle', $items[2]->id)->assertSee('100%')->assertSee('Concluído');
});

test('apagar um capítulo tira o item do planner e recalcula a barra', function () {
    PlannerItem::factory()->read()->create(['planner_id' => $this->planner->id, 'reference_material_id' => $this->book->id, 'chapter_id' => $this->chapters[0]->id]);
    PlannerItem::factory()->create(['planner_id' => $this->planner->id, 'reference_material_id' => $this->book->id, 'chapter_id' => $this->chapters[1]->id]);

    expect($this->planner->progress()['percent'])->toBe(50);

    $this->chapters[1]->delete();

    expect($this->planner->fresh()->progress())->toBe(['read' => 1, 'total' => 1, 'percent' => 100]);
});

test('excluir o planner não apaga obras nem capítulos', function () {
    PlannerItem::factory()->create(['planner_id' => $this->planner->id, 'reference_material_id' => $this->book->id, 'chapter_id' => $this->chapters[0]->id]);

    Livewire::test('pages::planner', ['id' => $this->planner->id])->call('deletePlanner');

    expect(Planner::find($this->planner->id))->toBeNull()
        ->and(PlannerItem::count())->toBe(0)
        ->and(ReferenceMaterial::find($this->book->id))->not->toBeNull()
        ->and(Chapter::count())->toBe(3);
});

test('planner de outro usuário dá 404 e o check dele é recusado', function () {
    $foreign = Planner::factory()->create(['access_token_id' => AccessToken::factory()->create()->id]);
    $item = PlannerItem::factory()->create(['planner_id' => $foreign->id]);

    $this->get(route('referencias.planners.show', $foreign->id))->assertNotFound();

    expect(app(TogglePlannerItemRead::class)->handle($item->id, $this->token->id)->success)->toBeFalse()
        ->and($item->refresh()->isRead())->toBeFalse();
});

test('editar o planner atualiza nome, objetivo e data-alvo', function () {
    Livewire::test('pages::planner', ['id' => $this->planner->id])
        ->call('openEdit')
        ->set('form.name', 'Novo nome')
        ->set('form.objective', 'Entender a justificação')
        ->set('form.targetDate', '2026-12-31')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->planner->refresh())
        ->name->toBe('Novo nome')
        ->objective->toBe('Entender a justificação')
        ->and($this->planner->target_date->toDateString())->toBe('2026-12-31');
});
