<?php

use App\Ai\Agents\Summarizer;
use App\Jobs\GenerateNoteSummaryJob;
use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\PastoralAdvices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('the job prompts the summarizer with the note content and persists the result', function () {
    Summarizer::fake(['RESUMO: gerado']);

    $token = AccessToken::factory()->create();
    $note = Notes::create([
        'title' => 'Vocação e chamado',
        'discipline_id' => Disciplines::factory()->create()->id,
        'access_token_id' => $token->id,
        'impressions' => 'Senti clareza sobre o próximo passo.',
    ]);

    PastoralAdvices::create([
        'note_id' => $note->id,
        'category' => 'Discernimento',
        'advice' => 'Ore antes de decidir.',
    ]);

    (new GenerateNoteSummaryJob($note))->handle();

    expect($note->fresh()->ai_summary)->toBe('RESUMO: gerado');

    Summarizer::assertPrompted(function ($prompt) {
        $text = is_string($prompt) ? $prompt : $prompt->prompt;

        return str_contains($text, 'Vocação e chamado')
            && str_contains($text, 'Discernimento')
            && str_contains($text, 'Senti clareza');
    });
});

test('the job uses the configured timeout', function () {
    config(['summarizer.job_timeout' => 42]);

    $note = new Notes(['title' => 'x']);

    expect((new GenerateNoteSummaryJob($note))->timeout)->toBe(42);
});
