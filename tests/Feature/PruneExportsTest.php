<?php

use App\Enums\ExportStatus;
use App\Models\AccessToken;
use App\Models\Export;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake((string) config('exports.disk', 'local'));
    $this->token = AccessToken::factory()->create();
});

test('it deletes files and expires records past their retention window', function () {
    $stale = Export::factory()->completed()->create([
        'access_token_id' => $this->token->id,
        'expires_at' => now()->subDay(),
    ]);
    $stalePath = $stale->path;
    Storage::disk($stale->disk)->put($stalePath, 'stale');

    $fresh = Export::factory()->completed()->create([
        'access_token_id' => $this->token->id,
        'expires_at' => now()->addDays(5),
    ]);
    Storage::disk($fresh->disk)->put($fresh->path, 'fresh');

    $this->artisan('exports:prune')->assertSuccessful();

    expect($stale->refresh()->status)->toBe(ExportStatus::Expired)
        ->and($stale->path)->toBeNull();
    Storage::disk($stale->disk)->assertMissing($stalePath);

    expect($fresh->refresh()->status)->toBe(ExportStatus::Completed);
    Storage::disk($fresh->disk)->assertExists($fresh->path);
});
