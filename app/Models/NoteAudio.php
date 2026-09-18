<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SummaryAudioStatus;
use Database\Factories\NoteAudioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Locução do resumo de IA de uma nota, guardada para não gastar a cota
 * diária do provider de TTS mais de uma vez pelo mesmo texto.
 *
 * @property-read int $id
 * @property int $note_id
 * @property SummaryAudioStatus $status
 * @property string $signature
 * @property string $voice
 * @property string $mime
 * @property string|null $failure_reason
 * @property string|null $content
 */
#[UseFactory(NoteAudioFactory::class)]
#[Fillable([
    'note_id',
    'status',
    'signature',
    'voice',
    'mime',
    'failure_reason',
    'content',
])]
#[Table(name: 'note_audios')]
class NoteAudio extends Model
{
    /** @use HasFactory<NoteAudioFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'status' => SummaryAudioStatus::class,
        ];
    }

    /**
     * Assinatura do texto, da voz e do modelo que produziram um áudio.
     *
     * Serve para saber se o áudio em cache ainda corresponde ao resumo atual.
     */
    public static function signatureFor(string $summary, string $voice, string $model): string
    {
        return hash('sha256', implode('|', [trim($summary), $voice, $model]));
    }

    /**
     * @return BelongsTo<Notes, $this>
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Notes::class, 'note_id');
    }

    /**
     * Os bytes do áudio, prontos para irem na resposta HTTP.
     */
    public function bytes(): string
    {
        return base64_decode((string) $this->content);
    }

    /**
     * Indica se este áudio ainda corresponde ao resumo, à voz e ao modelo atuais.
     */
    public function matches(string $signature): bool
    {
        return $this->status->isReady() && $this->signature === $signature;
    }
}
