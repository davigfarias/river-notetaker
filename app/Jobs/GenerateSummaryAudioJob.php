<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateSummaryAudio;
use App\Enums\SummaryAudioStatus;
use App\Models\NoteAudio;
use App\Models\Notes;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Audio;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

/**
 * Gera a locução do resumo de uma nota e a guarda.
 *
 * Roda na fila porque uma geração leva de quinze a setenta segundos — tempo
 * demais para um request HTTP, ainda mais no Laravel Cloud.
 */
class GenerateSummaryAudioJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout;

    public function __construct(public Notes $note)
    {
        $this->timeout = (int) config('tts.job_timeout', 180);
    }

    public function handle(GenerateSummaryAudio $action): void
    {
        $summary = trim((string) $this->note->ai_summary);

        if ($summary === '') {
            return;
        }

        try {
            $response = Audio::of($summary)
                ->voice((string) config('tts.voice'))
                ->instructions((string) config('tts.instructions'))
                ->timeout((int) config('tts.request_timeout'))
                ->generate(provider: (string) config('tts.provider'));
        } catch (RateLimitedException $e) {
            /*
             * O free tier do Gemini dá dez locuções por dia, por modelo.
             * Repetir uma chamada barrada por cota não tem como dar certo e
             * ainda consome outra da cota, então a tentativa morre aqui.
             */
            $this->fail($e);

            return;
        }

        NoteAudio::updateOrCreate(
            ['note_id' => $this->note->id],
            [
                'status' => SummaryAudioStatus::Ready,
                'signature' => $action->signatureFor($this->note),
                'voice' => (string) config('tts.voice'),
                'mime' => $response->mimeType() ?? 'audio/wav',
                'failure_reason' => null,
                // A resposta do SDK já chega em base64, que é a forma guardada.
                'content' => $response->audio,
            ],
        );
    }

    public function failed(?Throwable $exception): void
    {
        [$reason, $level] = match (true) {
            $exception instanceof RateLimitedException => [
                'A cota diária de dez locuções do provedor acabou. Tente de novo amanhã.',
                'warning',
            ],
            $exception instanceof ProviderOverloadedException => [
                'O provedor de locução está sobrecarregado. Tente de novo em alguns minutos.',
                'warning',
            ],
            default => ['Não foi possível gerar a locução desta nota.', 'error'],
        };

        NoteAudio::updateOrCreate(
            ['note_id' => $this->note->id],
            [
                'status' => SummaryAudioStatus::Failed,
                'signature' => '',
                'voice' => (string) config('tts.voice'),
                'mime' => 'audio/wav',
                'failure_reason' => $reason,
                'content' => null,
            ],
        );

        Log::log($level, "Falha na locução da nota {$this->note->id}: {$reason} ({$exception?->getMessage()})");
    }
}
