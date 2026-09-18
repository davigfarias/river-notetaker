<?php

namespace Database\Factories;

use App\Enums\SummaryAudioStatus;
use App\Models\NoteAudio;
use App\Models\Notes;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NoteAudio>
 */
#[UseModel(NoteAudio::class)]
class NoteAudioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $audio = $this->faker->words(3, true);

        return [
            'note_id' => Notes::factory(),
            'status' => SummaryAudioStatus::Ready,
            'signature' => hash('sha256', $audio),
            'voice' => 'Kore',
            'mime' => 'audio/wav',
            'failure_reason' => null,
            'content' => base64_encode($audio),
        ];
    }

    /** Locução ainda na fila. */
    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => SummaryAudioStatus::Pending,
            'content' => null,
        ]);
    }

    /** Locução que falhou, com o motivo mostrado ao usuário. */
    public function failed(string $reason = 'A cota diária de dez locuções do provedor acabou. Tente de novo amanhã.'): static
    {
        return $this->state(fn (): array => [
            'status' => SummaryAudioStatus::Failed,
            'failure_reason' => $reason,
            'content' => null,
        ]);
    }
}
