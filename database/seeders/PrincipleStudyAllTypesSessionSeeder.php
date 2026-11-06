<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ExerciseType;
use App\Models\AccessToken;
use App\Models\Principle;
use App\Models\PrincipleSession;
use Illuminate\Database\Seeder;

/**
 * Abre uma sessão de estudo com UM item de cada tipo de exercício, para testar tudo de uma vez.
 * Usa os princípios de exemplo (rode antes PrincipleStudyDemoSeeder). Só ACRESCENTA uma sessão.
 * Rodar: php artisan db:seed --class=PrincipleStudyAllTypesSessionSeeder
 */
class PrincipleStudyAllTypesSessionSeeder extends Seeder
{
    /** @var array<string, ExerciseType> título do princípio => tipo do exercício */
    private const ITEMS = [
        'Cristo é a chave hermenêutica' => ExerciseType::RecallTitleToStatement,
        'Justificação pela fé' => ExerciseType::RecallStatementToTitle,
        'Deus é um em três pessoas' => ExerciseType::ExplainWhy,
        'O contexto governa o sentido' => ExerciseType::ApplyScenario,
        'Singularidade de Cristo' => ExerciseType::DistinguishPrinciples,
        'Cristo cumpre a Lei e os Profetas' => ExerciseType::EvaluateInterpretation,
        'A salvação é graça do começo ao fim' => ExerciseType::CreateExample,
    ];

    public function run(): void
    {
        $token = AccessToken::query()->orderBy('id')->first();

        if ($token === null) {
            $this->command?->warn('Nenhum token de acesso encontrado: nada foi criado.');

            return;
        }

        $composition = collect(self::ITEMS)
            ->map(fn (ExerciseType $type, string $title): ?array => ($principle = Principle::query()->where('title', $title)->first())
                ? ['principle_id' => $principle->id, 'type' => $type->value, 'retry' => false]
                : null)
            ->filter()->values()->all();

        if ($composition === []) {
            $this->command?->warn('Princípios de exemplo não encontrados: rode PrincipleStudyDemoSeeder antes.');

            return;
        }

        PrincipleSession::query()->create([
            'access_token_id' => $token->id,
            'planned_items' => count($composition),
            'started_at' => now(),
            'composition' => $composition,
        ]);
    }
}
