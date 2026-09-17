<?php

use App\Actions\GenerateAccessToken;
use App\Models\AccessToken;
use App\Models\Citation;
use App\Models\ReferenceMaterial;

function readAloudToken(): array
{
    $plain = app(GenerateAccessToken::class)->handle('read-aloud-test')->data['plainTextToken'];

    return [$plain, AccessToken::firstWhere('name', 'read-aloud-test')];
}

/**
 * Substitui a Web Speech API por um dublê: o navegador headless não tem vozes
 * instaladas, e é a escolha da voz que precisa ser verificada.
 */
const SPEECH_STUB = <<<'JS'
    window.__spoken = [];
    window.__jsErrors = [];
    window.addEventListener('error', (event) => window.__jsErrors.push(event.message));

    window.SpeechSynthesisUtterance = class {
        constructor(text) {
            this.text = text;
        }
    };

    Object.defineProperty(window, 'speechSynthesis', {
        configurable: true,
        value: {
            cancel() {},
            getVoices: () => [
                { name: 'Luciana', lang: 'pt-BR', localService: true },
                { name: 'Joana (Premium)', lang: 'pt-PT', localService: true },
                { name: 'Google português do Brasil', lang: 'pt-BR', localService: false },
            ],
            speak(utterance) {
                window.__spoken.push({
                    text: utterance.text,
                    lang: utterance.lang,
                    voice: utterance.voice ? utterance.voice.name : null,
                });
            },
        },
    });
JS;

test('reading a citation aloud picks the best voice for the requested language', function () {
    [$plain, $token] = readAloudToken();

    $material = ReferenceMaterial::factory()->create(['access_token_id' => $token->id, 'title' => 'Ortodoxia']);
    Citation::factory()->create([
        'reference_material_id' => $material->id,
        'access_token_id' => $token->id,
        'quote_text' => 'A tradição é a democracia dos mortos.',
    ]);

    $page = loginWithAccessToken($plain)->navigate('/referencias/'.$material->id)->wait(0.7);

    $page->assertSee('A tradição é a democracia dos mortos.');
    $page->script(SPEECH_STUB);

    $page->click('[aria-label="Ler em português"]')->wait(0.3);

    $spoken = $page->script('window.__spoken');

    expect($spoken)->toHaveCount(1);
    expect($spoken[0]['text'])->toBe('A tradição é a democracia dos mortos.');
    expect($spoken[0]['lang'])->toBe('pt-BR');
    // pt-BR de rede ganha da pt-BR compacta, e o dialeto exato ganha da pt-PT premium.
    expect($spoken[0]['voice'])->toBe('Google português do Brasil');
    expect($page->script('window.__jsErrors'))->toBe([]);
});

test('reading in a language with no installed voice still speaks with that lang', function () {
    [$plain, $token] = readAloudToken();

    $material = ReferenceMaterial::factory()->create(['access_token_id' => $token->id, 'title' => 'Miracles']);
    Citation::factory()->create([
        'reference_material_id' => $material->id,
        'access_token_id' => $token->id,
        'quote_text' => 'There are no ordinary people.',
    ]);

    $page = loginWithAccessToken($plain)->navigate('/referencias/'.$material->id)->wait(0.7);

    $page->script(SPEECH_STUB);
    $page->click('[aria-label="Read in English"]')->wait(0.3);

    $spoken = $page->script('window.__spoken');

    expect($spoken)->toHaveCount(1);
    expect($spoken[0]['lang'])->toBe('en-US');
    expect($spoken[0]['voice'])->toBeNull();
    expect($page->script('window.__jsErrors'))->toBe([]);
});
