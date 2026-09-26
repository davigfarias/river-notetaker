<?php

namespace Database\Seeders;

use App\Actions\LinkConcepts;
use App\Models\Concepts;
use Illuminate\Database\Seeder;

/**
 * Volume alto e aditivo (nunca apaga nada) só pra estressar a performance do
 * Mapa de Conceitos (vis-network) com muitos nós/arestas. Termos gerados,
 * sem valor de conteúdo — não confundir com o ConceptSeeder curado.
 * Rodar com: php artisan db:seed --class=PerformanceConceptSeeder
 */
class PerformanceConceptSeeder extends Seeder
{
    public function run(): void
    {
        $total = 1000;
        $batchSize = 200;
        $created = collect();

        for ($i = 0; $i < $total; $i += $batchSize) {
            $rows = [];

            foreach (range(1, min($batchSize, $total - $i)) as $_) {
                $rows[] = [
                    'term' => 'Perf '.fake()->unique()->words(2, true),
                    'definition' => fake()->paragraph(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Concepts::insert($rows);
        }

        $ids = Concepts::where('term', 'like', 'Perf %')->pluck('id');

        $action = app(LinkConcepts::class);

        foreach ($ids->random(min(300, $ids->count())) as $id) {
            $candidates = $ids->reject(fn ($other) => $other === $id)->random(random_int(1, 3));

            foreach ((is_iterable($candidates) ? $candidates : [$candidates]) as $relatedId) {
                $action->handle($id, $relatedId);
            }
        }

        $this->command->info(sprintf('%d conceitos de performance semeados (%d já existentes antes).', $ids->count(), $ids->count() - $total));
    }
}
