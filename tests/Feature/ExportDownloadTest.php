<?php

use App\Enums\ExportStatus;
use App\Models\AccessToken;
use App\Models\Export;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake((string) config('exports.disk', 'local'));
    $this->token = AccessToken::factory()->create();
});

test('the owner can download a completed export', function () {
    $export = Export::factory()->completed()->create(['access_token_id' => $this->token->id]);
    Storage::disk($export->disk)->put($export->path, 'binary');

    $this->withSession(['access_token_id' => $this->token->id])
        ->get(route('referencias.exportacoes.download', $export))
        ->assertOk()
        ->assertDownload($export->filename);
});

test('a different token gets a 403', function () {
    $export = Export::factory()->completed()->create(['access_token_id' => $this->token->id]);
    Storage::disk($export->disk)->put($export->path, 'binary');

    $this->withSession(['access_token_id' => AccessToken::factory()->create()->id])
        ->get(route('referencias.exportacoes.download', $export))
        ->assertForbidden();
});

test('a not-yet-ready export returns 404', function () {
    $export = Export::factory()->create([
        'access_token_id' => $this->token->id,
        'status' => ExportStatus::Pending,
    ]);

    $this->withSession(['access_token_id' => $this->token->id])
        ->get(route('referencias.exportacoes.download', $export))
        ->assertNotFound();
});
