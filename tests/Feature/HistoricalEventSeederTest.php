<?php

use App\Models\AccessToken;
use App\Models\HistoricalEvent;
use Database\Seeders\HistoricalEventSeeder;

test('the seeder fills the timeline and is idempotent', function () {
    $token = AccessToken::factory()->create();

    $this->seed(HistoricalEventSeeder::class);
    $firstRun = HistoricalEvent::count();

    $this->seed(HistoricalEventSeeder::class);

    expect($firstRun)->toBeGreaterThan(50)
        ->and(HistoricalEvent::count())->toBe($firstRun)
        ->and(HistoricalEvent::where('access_token_id', $token->id)->count())->toBe($firstRun)
        ->and(HistoricalEvent::where('sort_key', '<', 0)->exists())->toBeTrue();
});

test('the seeder creates nothing without an access token', function () {
    $this->seed(HistoricalEventSeeder::class);

    expect(HistoricalEvent::count())->toBe(0);
});
