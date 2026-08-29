<?php

use App\Actions\RequestExport;
use App\Enums\ExportFormat;
use App\Enums\ExportScope;
use App\Enums\ExportStatus;
use App\Jobs\GenerateExport;
use App\Models\AccessToken;
use App\Models\Citation;
use App\Models\Export;
use App\Models\ReferenceMaterial;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->token = AccessToken::factory()->create();
    $this->withSession(['access_token_id' => $this->token->id]);
    $this->material = ReferenceMaterial::factory()->create([
        'access_token_id' => $this->token->id,
        'title' => 'A Vida Juntos',
        'author' => 'Dietrich Bonhoeffer',
        'year' => 1939,
    ]);
});

test('requesting a reference export dispatches the job and produces a downloadable file', function () {
    Citation::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
        'quote_text' => 'A comunhão cristã não é um ideal a realizar, mas uma realidade criada por Deus.',
        'location' => 'p. 21',
    ]);

    $outcome = app(RequestExport::class)->handle(
        scope: ExportScope::Reference,
        format: ExportFormat::Docx,
        accessTokenId: $this->token->id,
        referenceMaterialId: $this->material->id,
    );

    expect($outcome->success)->toBeTrue();

    $export = Export::first();

    expect($export->status)->toBe(ExportStatus::Completed)
        ->and($export->file_size)->toBeGreaterThan(0)
        ->and($export->expires_at)->not->toBeNull();

    Storage::disk($export->disk)->assertExists($export->path);
});

test('a pdf export starts with the PDF signature', function () {
    Citation::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
    ]);

    app(RequestExport::class)->handle(
        scope: ExportScope::Reference,
        format: ExportFormat::Pdf,
        accessTokenId: $this->token->id,
        referenceMaterialId: $this->material->id,
    );

    $export = Export::first();
    $contents = Storage::disk($export->disk)->get($export->path);

    expect(substr($contents, 0, 4))->toBe('%PDF');
});

test('exporting a reference with no citations fails before dispatching', function () {
    $outcome = app(RequestExport::class)->handle(
        scope: ExportScope::Reference,
        format: ExportFormat::Docx,
        accessTokenId: $this->token->id,
        referenceMaterialId: $this->material->id,
    );

    expect($outcome->success)->toBeFalse();
    expect(Export::count())->toBe(0);
});

test('a search export groups matching citations by work', function () {
    Citation::factory()->create([
        'reference_material_id' => $this->material->id,
        'access_token_id' => $this->token->id,
        'quote_text' => 'Somente a graça sustenta.',
    ]);

    $outcome = app(RequestExport::class)->handle(
        scope: ExportScope::Search,
        format: ExportFormat::Docx,
        accessTokenId: $this->token->id,
        searchQuery: 'graça',
    );

    expect($outcome->success)->toBeTrue();
    expect(Export::first()->status)->toBe(ExportStatus::Completed);
});

test('a job failure marks the export as failed', function () {
    $foreignMaterial = ReferenceMaterial::factory()->create([
        'access_token_id' => AccessToken::factory()->create()->id,
    ]);

    // Export row belongs to our token but points at a material another token owns:
    // the resolver's scoped findOrFail throws, and the job records the failure.
    $broken = Export::factory()->create([
        'access_token_id' => $this->token->id,
        'scope' => ExportScope::Reference,
        'reference_material_id' => $foreignMaterial->id,
        'status' => ExportStatus::Pending,
    ]);

    $job = new GenerateExport($broken);

    try {
        $job->handle(
            app(\App\Support\Export\ExportContentResolver::class),
            app(\App\Support\Export\CitationExporter::class),
        );
    } catch (\Throwable $e) {
        $job->failed($e);
    }

    expect($broken->refresh()->status)->toBe(ExportStatus::Failed)
        ->and($broken->error)->not->toBeNull();
});

test('the exports page lists the token exports and can delete one', function () {
    $export = Export::factory()->completed()->create([
        'access_token_id' => $this->token->id,
        'reference_material_id' => $this->material->id,
    ]);
    Storage::disk($export->disk)->put($export->path, 'x');

    Livewire::test('pages::exportacoes')
        ->call('loadContent')
        ->assertSee('A Vida Juntos')
        ->call('confirmDeleteExport', $export->id)
        ->call('deleteExport');

    $this->assertDatabaseMissing('exports', ['id' => $export->id]);
    Storage::disk($export->disk)->assertMissing($export->path);
});
