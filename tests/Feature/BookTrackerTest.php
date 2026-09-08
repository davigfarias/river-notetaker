<?php

use App\Enums\BookFormat;
use App\Enums\ReadingStatus;
use App\Models\AccessToken;
use App\Models\ReferenceMaterial;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    Livewire::withoutLazyLoading();
});

test('a physical book is added with format and page count', function () {
    Livewire::test('pages::referencias')
        ->set('form.title', 'A Vida Juntos')
        ->set('form.type', 'book-open')
        ->set('form.book_format', BookFormat::Physical->value)
        ->set('form.page_count', 240)
        ->call('addMaterial')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('reference_materials', [
        'title' => 'A Vida Juntos',
        'book_format' => BookFormat::Physical->value,
        'page_count' => 240,
    ]);
});

test('a digital book requires reader start and end pages', function () {
    Livewire::test('pages::referencias')
        ->set('form.title', 'Livro Kindle')
        ->set('form.type', 'book-open')
        ->set('form.book_format', BookFormat::Kindle->value)
        ->call('addMaterial')
        ->assertHasErrors(['form.reader_start_page', 'form.reader_end_page']);
});

test('a digital book derives the page count from the reader range', function () {
    Livewire::test('pages::referencias')
        ->set('form.title', 'Livro Kindle')
        ->set('form.type', 'book-open')
        ->set('form.book_format', BookFormat::Kindle->value)
        ->set('form.reader_start_page', 12)
        ->set('form.reader_end_page', 340)
        ->call('addMaterial')
        ->assertHasNoErrors();

    $material = ReferenceMaterial::firstWhere('title', 'Livro Kindle');

    expect($material->page_count)->toBe(329)
        ->and($material->pagesTotal())->toBe(329);
});

test('moving to reading records the start date', function () {
    $material = ReferenceMaterial::factory()->tracking()->create([
        'access_token_id' => $this->token->id,
        'reading_status' => null,
        'reading_started_at' => null,
    ]);

    Livewire::test('pages::referencia', ['id' => $material->id])
        ->set('readingStatus', ReadingStatus::Reading->value)
        ->assertHasNoErrors();

    expect($material->refresh()->reading_started_at?->toDateString())->toBe(today()->toDateString());
});

test('moving to read records the finish date and completes the pages', function () {
    $material = ReferenceMaterial::factory()->tracking(total: 300, read: 40)->create([
        'access_token_id' => $this->token->id,
        'reading_started_at' => null,
    ]);

    Livewire::test('pages::referencia', ['id' => $material->id])
        ->set('readingStatus', ReadingStatus::Read->value)
        ->assertHasNoErrors();

    $material->refresh();

    expect($material->reading_finished_at?->toDateString())->toBe(today()->toDateString())
        ->and($material->reading_started_at?->toDateString())->toBe(today()->toDateString())
        ->and($material->current_page)->toBe(300)
        ->and($material->readingProgressPercent())->toBe(100);
});

test('digital progress percent uses the reader range', function () {
    $material = ReferenceMaterial::factory()->digital(start: 12, end: 340)->create([
        'access_token_id' => $this->token->id,
        'current_page' => 176,
    ]);

    expect($material->readingProgressPercent())->toBe(50);
});

test('current page above the range end is clamped', function () {
    $material = ReferenceMaterial::factory()->tracking(total: 200, read: 10)->create([
        'access_token_id' => $this->token->id,
    ]);

    Livewire::test('pages::referencia', ['id' => $material->id])
        ->set('readingStatus', ReadingStatus::Reading->value)
        ->set('currentPage', 999)
        ->assertHasNoErrors();

    expect($material->refresh()->current_page)->toBe(200);
});

test('non trackable types ignore the tracker fields', function () {
    Livewire::test('pages::referencias')
        ->set('form.title', 'Documentário')
        ->set('form.type', 'video-camera')
        ->set('form.book_format', BookFormat::Kindle->value)
        ->set('form.page_count', 500)
        ->call('addMaterial')
        ->assertHasNoErrors();

    $material = ReferenceMaterial::firstWhere('title', 'Documentário');

    expect($material->book_format)->toBeNull()
        ->and($material->page_count)->toBeNull()
        ->and($material->hasReadingProgress())->toBeFalse();
});

test('the reading section shows only for trackable materials', function () {
    $book = ReferenceMaterial::factory()->tracking()->create(['access_token_id' => $this->token->id]);
    $video = ReferenceMaterial::factory()->create([
        'access_token_id' => $this->token->id,
        'type' => 'video-camera',
    ]);

    Livewire::test('pages::referencia', ['id' => $book->id])->assertSee('Leitura');
    Livewire::test('pages::referencia', ['id' => $video->id])->assertDontSee('Leitura');
});

test('the library card shows reading progress for a tracked book', function () {
    ReferenceMaterial::factory()->tracking(total: 200, read: 50)->create([
        'access_token_id' => $this->token->id,
        'title' => 'Livro em Progresso',
    ]);

    Livewire::test('pages::referencias')
        ->assertSee('50/200 págs');
});
