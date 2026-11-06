<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Chapter;
use App\Models\Planner;
use App\Models\ReferenceMaterial;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

/**
 * Coloca no planner capítulos ("c12") e obras sem capítulos ("m7"). Ignora o
 * que já está lá, o que não é do usuário e obra que tem capítulos (nesse caso
 * o que entra são os capítulos).
 */
final readonly class AddPlannerItems
{
    /**
     * @param  array<int, string>  $keys
     */
    public function handle(int $plannerId, array $keys, int $accessTokenId): Outcome
    {
        try {
            $planner = Planner::query()->where('access_token_id', $accessTokenId)->findOrFail($plannerId);

            $chapterIds = [];
            $materialIds = [];

            foreach ($keys as $key) {
                if (preg_match('/^([mc])(\d+)$/', (string) $key, $match) !== 1) {
                    continue;
                }

                if ($match[1] === 'c') {
                    $chapterIds[] = (int) $match[2];
                } else {
                    $materialIds[] = (int) $match[2];
                }
            }

            $wanted = collect();

            Chapter::query()
                ->whereIn('id', $chapterIds)
                ->whereHas('referenceMaterial', fn ($query) => $query->where('access_token_id', $accessTokenId))
                ->orderBy('reference_material_id')->orderBy('position')
                ->get()
                ->each(fn (Chapter $chapter) => $wanted->push([
                    'reference_material_id' => $chapter->reference_material_id,
                    'chapter_id' => $chapter->id,
                ]));

            ReferenceMaterial::query()
                ->whereIn('id', $materialIds)
                ->where('access_token_id', $accessTokenId)
                ->doesntHave('chapters')
                ->get()
                ->each(fn (ReferenceMaterial $material) => $wanted->push([
                    'reference_material_id' => $material->id,
                    'chapter_id' => null,
                ]));

            $existing = $planner->items()->get(['reference_material_id', 'chapter_id'])
                ->map(fn ($item): string => $item->reference_material_id.':'.($item->chapter_id ?? ''))
                ->all();

            $position = (int) $planner->items()->max('position');
            $added = 0;

            foreach ($wanted as $item) {
                if (in_array($item['reference_material_id'].':'.($item['chapter_id'] ?? ''), $existing, true)) {
                    continue;
                }

                $planner->items()->create([...$item, 'position' => ++$position]);
                $added++;
            }

            return $added === 0
                ? Outcome::failure(message: 'Nada novo para adicionar: o que você marcou já está no planner.')
                : Outcome::success(message: $added === 1 ? '1 item adicionado ao planner.' : "{$added} itens adicionados ao planner.");
        } catch (\Exception $e) {
            Log::error("Erro ao adicionar itens ao planner: {$e->getMessage()}");

            return Outcome::failure(message: 'Não foi possível adicionar ao planner.');
        }
    }
}
