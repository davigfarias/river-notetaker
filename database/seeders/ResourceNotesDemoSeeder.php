<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\ReadingNote;
use App\Models\ReferenceMaterial;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Anotações de recursos (e uma nota de aula) de demonstração, com headings
 * Markdown, para ver a fila de revisão do painel principal e o índice (TOC).
 *
 * Idempotente: tudo é gravado com updateOrCreate sobre títulos fixos com o
 * sufixo "(demo)". Nunca apaga nada e nunca toca em dados reais.
 */
class ResourceNotesDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private const string SUFFIX = '(demo)';

    public function run(): void
    {
        $material = ReferenceMaterial::query()->orderBy('id')->first();

        if (! $material instanceof ReferenceMaterial) {
            $this->command->error('Nenhuma obra cadastrada. Crie uma em Referências antes de rodar este seeder.');

            return;
        }

        $today = CarbonImmutable::now()->startOfDay();

        $this->readingNote($material, 'A graça comum e a graça salvadora', [
            'review_stage' => 1,
            'next_review_at' => $today->subDays(2)->toDateString(),
            'location' => 'cap. 3',
            'tags' => ['graça', 'soteriologia'],
            'body' => $this->longBody(),
        ]);

        $this->readingNote($material, 'Anotação curta, sem headings', [
            'review_stage' => 2,
            'next_review_at' => $today->toDateString(),
            'body' => 'Uma anotação de uma linha só: sem headings, o índice não deve aparecer.',
        ]);

        $this->readingNote($material, 'Uma anotação com um título só', [
            'review_stage' => 3,
            'next_review_at' => $today->toDateString(),
            'body' => "## Único título\n\nCom apenas um heading o índice também não aparece.",
        ]);

        $this->readingNote($material, 'Agendada para o futuro', [
            'review_stage' => 2,
            'next_review_at' => $today->addDays(5)->toDateString(),
            'body' => 'Não deve aparecer na fila de hoje.',
        ]);

        $this->readingNote($material, 'Já tirada da fila', [
            'review_stage' => 1,
            'next_review_at' => $today->toDateString(),
            'dismissed_at' => CarbonImmutable::now(),
            'body' => 'Deve aparecer só em "Tiradas da fila".',
        ]);

        $this->classNoteWithHeadings();

        $this->command->info("Pronto: anotações demo na obra \"{$material->title}\" e uma nota de aula com headings.");
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function readingNote(ReferenceMaterial $material, string $title, array $attributes): void
    {
        ReadingNote::updateOrCreate(
            ['reference_material_id' => $material->id, 'title' => trim($title.' '.self::SUFFIX)],
            ['access_token_id' => $material->access_token_id, ...$attributes],
        );
    }

    /**
     * Mexe só na nota demo da disciplina demo; sem ela, não faz nada.
     */
    private function classNoteWithHeadings(): void
    {
        $discipline = Disciplines::query()->where('title', 'Teologia Sistemática '.self::SUFFIX)->first();

        if (! $discipline instanceof Disciplines) {
            $this->command->warn('Disciplina demo não encontrada: rode o SpacedReviewDemoSeeder para ver o índice nas notas de aula.');

            return;
        }

        Notes::updateOrCreate(
            ['discipline_id' => $discipline->id, 'title' => 'Notas com headings '.self::SUFFIX],
            [
                'access_token_id' => Notes::query()->where('discipline_id', $discipline->id)->value('access_token_id'),
                'review_stage' => 0,
                'tags' => ['toc'],
                'summary' => $this->longBody(),
                'impressions' => "# Primeiras impressões\n\nO que mais me marcou.\n\n## Sobre a aula\n\nO ritmo foi bom.\n\n## Sobre o texto\n\nA leitura prévia ajudou.\n\n### Dúvida em aberto\n\nComo isso se aplica ao aconselhamento?",
            ],
        );
    }

    private function longBody(): string
    {
        return <<<'MD'
# A graça de Deus

Introdução curta antes do primeiro bloco.

## Graça comum

Dada a todos, sem distinção, sustentando a criação e restringindo o pecado.

### Exemplos na providência

Chuva sobre justos e injustos.

### Limites

Não salva, mas preserva.

## Graça salvadora

Concedida aos eleitos, produzindo fé e arrependimento.

### Chamado eficaz

Deus chama de modo irresistível.

#### Um detalhe de nível quatro

Este nível não deve entrar no índice (só de # a ###).

## Conclusão

A distinção ajuda a pregar sem confundir bondade geral com salvação.
MD;
    }
}
