<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PrincipleType;
use App\Models\AccessToken;
use App\Models\Disciplines;
use App\Models\Principle;
use App\Models\PrincipleCategory;
use App\Models\PrincipleReviewState;
use App\Models\PrincipleTopic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Dados de exemplo para testar a sessão de estudo de princípios.
 * Só ACRESCENTA (firstOrCreate): nunca apaga nem altera o que já existe.
 * Tudo começa com "Exemplo:" para você achar e apagar à mão depois.
 * Rodar: php artisan db:seed --class=PrincipleStudyDemoSeeder
 */
class PrincipleStudyDemoSeeder extends Seeder
{
    /** @var array<string, array<string, list<array{0: string, 1: string}>>> */
    private const CONTENT = [
        'Exemplo: Hermenêutica' => [
            'Cristo como chave' => [
                ['Cristo é a chave hermenêutica', 'Interprete sempre à luz da escatologia e da vinda de Cristo: Jesus é a chave para ler toda a Escritura.'],
                ['Cristo cumpre a Lei e os Profetas', 'O Antigo Testamento aponta para Cristo; ele não revoga a Lei e os Profetas, mas os cumpre.'],
                ['Cristo é o centro da história da redenção', 'A promessa, a aliança e o reino convergem em Cristo, por isso toda passagem se lê dentro desse enredo.'],
            ],
            'Leitura do texto' => [
                ['O contexto governa o sentido', 'Um versículo só significa o que o seu parágrafo, o seu livro e o seu gênero literário permitem que signifique.'],
                ['A Escritura interpreta a Escritura', 'Passagens claras iluminam as obscuras: nenhum texto deve ser lido contra o ensino do conjunto da Bíblia.'],
                ['Distinga o que o texto diz do que ele pede', 'Primeiro observe o que o autor afirma; só depois pergunte como isso se aplica hoje.'],
            ],
        ],
        'Exemplo: Teologia Sistemática' => [
            'Deus e Cristo' => [
                ['Deus é um em três pessoas', 'Há um só Deus em essência, existente eternamente em três pessoas distintas: Pai, Filho e Espírito Santo.'],
                ['Singularidade de Cristo', 'Jesus é o único mediador entre Deus e os homens: não há salvação em nenhum outro nome.'],
                ['Cristo é plenamente Deus e plenamente homem', 'Na pessoa de Cristo, duas naturezas, divina e humana, estão unidas sem confusão e sem separação.'],
            ],
            'Salvação' => [
                ['Justificação pela fé', 'O pecador é declarado justo diante de Deus somente pela fé em Cristo, e não pelas obras da Lei.'],
                ['A salvação é graça do começo ao fim', 'Deus inicia, sustenta e completa a salvação; o ser humano a recebe, sem poder merecê-la.'],
            ],
        ],
    ];

    public function run(): void
    {
        $disciplines = Disciplines::query()->orderBy('id')->get();
        $token = AccessToken::query()->orderBy('id')->first();

        foreach (array_keys(self::CONTENT) as $topicIndex => $topicTitle) {
            $topic = PrincipleTopic::query()->firstOrCreate(
                ['title' => $topicTitle],
                ['slug' => Str::slug($topicTitle)],
            );

            // Liga cada tema a uma disciplina existente (sem remover vínculos).
            $discipline = $disciplines->get($topicIndex);
            if ($discipline !== null) {
                $topic->disciplines()->syncWithoutDetaching([$discipline->id]);
            }

            foreach (self::CONTENT[$topicTitle] as $categoryTitle => $principles) {
                $category = PrincipleCategory::query()->firstOrCreate(
                    ['principle_topic_id' => $topic->id, 'title' => $categoryTitle],
                    ['position' => PrincipleCategory::query()->where('principle_topic_id', $topic->id)->count()],
                );

                foreach ($principles as [$title, $body]) {
                    Principle::query()->firstOrCreate(
                        ['principle_topic_id' => $topic->id, 'title' => $title],
                        [
                            'principle_category_id' => $category->id,
                            'type' => PrincipleType::Text,
                            'body' => $body,
                            'position' => Principle::query()->where('principle_topic_id', $topic->id)->count(),
                        ],
                    );
                }
            }
        }

        // Três princípios já vencidos, para ver "para hoje" e a revisão espaçada funcionando.
        if ($token !== null) {
            Principle::query()->whereIn('title', ['Cristo é a chave hermenêutica', 'Deus é um em três pessoas', 'Justificação pela fé'])
                ->get()
                ->each(fn (Principle $p) => PrincipleReviewState::query()->firstOrCreate(
                    ['access_token_id' => $token->id, 'principle_id' => $p->id],
                    ['due_at' => now()->subDay(), 'last_reviewed_at' => now()->subDays(4), 'interval_days' => 3, 'ease' => 2.5, 'repetitions' => 2, 'lapses' => 0],
                ));
        }
    }
}
