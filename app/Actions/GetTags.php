<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\TagsDTO;
use App\Models\Tags;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class GetTags
{
    public function handle(): Outcome
    {
        try {
            $data = Tags::all()
                ->map(fn (Tags $tag): TagsDTO => TagsDTO::fromModel($tag));

            return Outcome::noViewMessage(data: $data);
        } catch (\Exception $e) {
            Log::error("Erro: {$e->getMessage()}");

            return Outcome::failure(message: '');
        }
    }
}
