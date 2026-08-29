<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExportFormat;
use App\Enums\ExportScope;
use App\Enums\ExportStatus;
use Database\Factories\ExportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $access_token_id
 * @property ExportFormat $format
 * @property ExportScope $scope
 * @property int|null $reference_material_id
 * @property string|null $search_query
 * @property ExportStatus $status
 * @property string|null $disk
 * @property string|null $path
 * @property string $filename
 * @property int|null $file_size
 * @property string|null $error
 * @property Carbon|null $expires_at
 */
#[UseFactory(ExportFactory::class)]
#[Fillable([
    'access_token_id',
    'format',
    'scope',
    'reference_material_id',
    'search_query',
    'status',
    'disk',
    'path',
    'filename',
    'file_size',
    'error',
    'expires_at',
])]
#[Table(name: 'exports')]
class Export extends Model
{
    /** @use HasFactory<ExportFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'format' => ExportFormat::class,
            'scope' => ExportScope::class,
            'status' => ExportStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ReferenceMaterial, $this>
     */
    public function referenceMaterial(): BelongsTo
    {
        return $this->belongsTo(ReferenceMaterial::class);
    }

    /**
     * @return BelongsTo<AccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class);
    }
}
