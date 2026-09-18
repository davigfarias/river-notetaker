<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NoteAudio;
use App\Models\Notes;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serve a locução já guardada de uma nota. Nunca gera nada: a geração é
 * trabalho de fila, feito por GenerateSummaryAudioJob.
 */
final class StreamSummaryAudioController extends Controller
{
    public function __invoke(Request $request, Notes $note): Response
    {
        abort_unless(
            (int) $note->access_token_id === (int) $request->session()->get('access_token_id'),
            403,
        );

        /** @var NoteAudio|null $audio */
        $audio = $note->audio()->first();

        abort_unless($audio?->status->isReady() && $audio->content !== null, 404);

        $bytes = $audio->bytes();

        return response($bytes, 200, [
            'Content-Type' => $audio->mime,
            'Content-Length' => (string) strlen($bytes),
            'Accept-Ranges' => 'none',
            // A URL carrega a assinatura, então o conteúdo nunca muda sob ela.
            'Cache-Control' => 'private, max-age='.(int) config('tts.browser_cache_seconds').', immutable',
        ]);
    }
}
