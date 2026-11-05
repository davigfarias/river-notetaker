<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConceptReviewResult;
use Carbon\CarbonImmutable;
use Database\Factories\ConceptReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $access_token_id
 * @property int $concept_id
 * @property ConceptReviewResult $result
 * @property CarbonImmutable $reviewed_at
 */
#[UseFactory(ConceptReviewFactory::class)]
#[Fillable(['access_token_id', 'concept_id', 'result', 'reviewed_at'])]
#[Table(name: 'concept_reviews')]
class ConceptReview extends Model
{
    /** @use HasFactory<ConceptReviewFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    public function casts(): array
    {
        return [
            'result' => ConceptReviewResult::class,
            'reviewed_at' => 'immutable_datetime',
        ];
    }
}
