<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SummaryAudioStatus;
use App\Jobs\GenerateSummaryAudioJob;
use App\Models\NoteAudio;
use App\Models\Notes;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

/**
 * Coloca na fila a locução do resumo de IA de uma nota.
 *
 * O free tier do provider dá poucas locuções por dia, então nada é regerado à
 * toa: um áudio cuja assinatura ainda bate com o resumo atual é reaproveitado.
 */
final readonly class GenerateSummaryAudio
{
    public function handle(int $noteId): Outcome
    {
        try {
            $note = Notes::findOrFail($noteId);

            $summary = trim((string) $note->ai_summary);

            if ($summary === '') {
                return Outcome::failure(message: 'Esta nota ainda não tem um resumo para ler.');
            }

            $limit = (int) config('tts.max_characters');

            if (mb_strlen($summary) > $limit) {
                return Outcome::failure(
                    message: "O resumo passa de {$limit} caracteres e é longo demais para a locução. Gere o resumo novamente para encurtá-lo.",
                );
            }

            if ($this->cachedAudioFor($note) !== null) {
                return Outcome::success(message: 'A locução desta nota já está pronta.');
            }

            /*
             * O registro nasce pendente para a tela saber que há trabalho em
             * curso e, se falhar, poder mostrar o motivo sem esperar o prazo.
             */
            NoteAudio::updateOrCreate(
                ['note_id' => $note->id],
                [
                    'status' => SummaryAudioStatus::Pending,
                    'signature' => $this->signatureFor($note),
                    'voice' => (string) config('tts.voice'),
                    'mime' => 'audio/wav',
                    'failure_reason' => null,
                    'content' => null,
                ],
            );

            GenerateSummaryAudioJob::dispatch($note);

            return Outcome::success(message: 'Gerando a locução. Isso leva cerca de um minuto.');
        } catch (\Exception $e) {
            Log::error("Erro ao despachar a locução do resumo: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível iniciar a geração da locução.');
        }
    }

    /**
     * Devolve o áudio guardado que ainda corresponde ao resumo atual da nota.
     */
    public function cachedAudioFor(Notes $note): ?NoteAudio
    {
        $audio = $note->audio()->first();

        return $audio?->matches($this->signatureFor($note)) ? $audio : null;
    }

    public function signatureFor(Notes $note): string
    {
        return NoteAudio::signatureFor(
            (string) $note->ai_summary,
            (string) config('tts.voice'),
            (string) config('tts.model'),
        );
    }
}
