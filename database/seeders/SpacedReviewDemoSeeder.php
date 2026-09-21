<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Weekday;
use App\Models\AccessToken;
use App\Models\Concepts;
use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\PastoralAdvices;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Dados de demonstração para exercitar a fila de revisão espaçada.
 *
 * É idempotente por construção: tudo é gravado com updateOrCreate sobre títulos
 * fixos com o sufixo "(demo)", então rodar o seeder de novo atualiza os mesmos
 * registros. Ele nunca apaga nada e nunca toca em dados reais.
 */
class SpacedReviewDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private const string SUFFIX = '(demo)';

    public function run(): void
    {
        $token = AccessToken::query()
            ->whereNull('revoked_at')
            ->orderBy('id')
            ->first();

        if (! $token instanceof AccessToken) {
            $this->command->error('Nenhum token de acesso ativo encontrado. Crie um antes de rodar este seeder.');

            return;
        }

        $today = CarbonImmutable::now()->startOfDay();
        $weekday = Weekday::fromDate($today);

        $this->command->info("Semeando a fila de revisão para o token \"{$token->name}\" ({$weekday->label()}).");

        $this->seedTodayDiscipline($token, $today, $weekday);
        $this->seedUpcomingDiscipline($token, $today, $weekday);
        $this->seedCompletedDiscipline($token, $today, $weekday);

        $this->command->info('Pronto: 4 notas devidas hoje, 1 consolidada, 1 futura, 1 em disciplina encerrada.');
    }

    /**
     * Disciplina com aula hoje: é a que enche o carrossel do painel.
     */
    private function seedTodayDiscipline(AccessToken $token, CarbonImmutable $today, Weekday $weekday): void
    {
        $discipline = $this->discipline('Teologia Sistemática', [
            'icon' => 'academic-cap',
            'period' => 1,
            'code' => 'TEO-101',
            'professor' => 'Rev. João Calvino',
            'class_weekday' => $weekday,
            'completed_at' => null,
        ]);

        $note = $this->note($discipline, $token, 'A inspiração das Escrituras', [
            'review_stage' => 1,
            'next_review_at' => $today->toDateString(),
            'summary' => 'Deus inspirou as palavras, não só as ideias. Por isso a autoridade está no texto, e não na minha leitura dele.',
            'ai_summary' => 'A Escritura é inspirada verbalmente e plenamente: a inspiração alcança as palavras, não apenas as ideias gerais dos autores.',
            'impressions' => 'A aula deixou claro que negar a inspiração verbal esvazia a autoridade do texto na pregação.',
            'life_experiences' => 'Lembrei de uma conversa em que usei a Escritura como conselho e não como autoridade.',
            'tags' => ['bibliologia', 'inspiração'],
        ]);

        $this->concept($note, 'Inspiração verbal', 'Deus inspirou as próprias palavras do texto bíblico, e não apenas o sentido geral pretendido pelos autores.');
        $this->concept($note, 'Autógrafo', 'O manuscrito original escrito pelo autor bíblico, ao qual a inerrância se refere de modo estrito.');
        $this->advice($note, 'Pregação', 'Pregar o texto, não a impressão sobre o texto: a autoridade está na Escritura.');

        $overdueOne = $this->note($discipline, $token, 'Os atributos incomunicáveis de Deus', [
            'review_stage' => 2,
            'next_review_at' => $today->subWeek()->toDateString(),
            'summary' => 'Os incomunicáveis são os atributos que não têm eco na criatura. Imutável não quer dizer parado: Deus não muda de caráter.',
            'ai_summary' => 'Atributos incomunicáveis são aqueles que não têm análogo na criatura: independência, imutabilidade, eternidade e onipresença.',
            'impressions' => 'A imutabilidade não é imobilidade: Deus não muda de caráter, mas age na história.',
            'life_experiences' => 'Usei isso para consolar alguém que temia que Deus tivesse mudado de opinião sobre ela.',
            'tags' => ['teologia própria', 'atributos'],
        ]);

        $this->concept($overdueOne, 'Imutabilidade', 'Deus não muda em seu ser, perfeições, propósitos ou promessas.');
        $this->advice($overdueOne, 'Consolo', 'A imutabilidade de Deus é a base para confiar nas promessas dele ao longo do tempo.');

        $overdueThree = $this->note($discipline, $token, 'A doutrina da Trindade e as heresias antigas', [
            'review_stage' => 1,
            'next_review_at' => $today->subWeeks(3)->toDateString(),
            'summary' => 'Uma essência, três pessoas. Modalismo apaga as pessoas, arianismo rebaixa o Filho: erram em direções opostas.',
            'ai_summary' => 'Um Deus em três pessoas, sem divisão de essência nem confusão de pessoas. Modalismo e ariano erram em lados opostos.',
            'impressions' => 'As heresias trinitárias sempre sacrificam ou a unidade ou a distinção das pessoas.',
            'life_experiences' => 'Reconheci linguagem modalista numa música que eu cantava sem perceber.',
            'tags' => ['trindade', 'heresias'],
        ]);

        $this->concept($overdueThree, 'Modalismo', 'Heresia que trata Pai, Filho e Espírito como modos sucessivos de manifestação de uma só pessoa.');
        $this->concept($overdueThree, 'Homoousios', 'Termo de Niceia: o Filho é da mesma substância do Pai.');

        $this->note($discipline, $token, 'A providência e o decreto divino', [
            'review_stage' => 4,
            'next_review_at' => $today->toDateString(),
            'summary' => 'Decreto é o plano eterno; providência é esse plano acontecendo no tempo, junto com as causas segundas.',
            'ai_summary' => 'O decreto é o plano eterno de Deus; a providência é sua execução no tempo, sustentando, governando e concorrendo com as causas segundas.',
            'impressions' => 'Esta nota já passou por três revisões: uma revisão bem-sucedida agora a consolida.',
            'life_experiences' => 'Serviu para explicar a alguém por que oração e decreto não se anulam.',
            'tags' => ['providência', 'decreto'],
        ]);

        $this->note($discipline, $token, 'A doutrina da criação', [
            'review_stage' => 5,
            'next_review_at' => null,
            'consolidated_at' => $today->subWeeks(2),
            'summary' => 'Criação do nada, por decisão livre, para a glória de Deus. Nada obrigou Deus a criar.',
            'ai_summary' => 'Criação do nada, por livre decisão de Deus, para a manifestação de sua glória. Esta nota já saiu da fila.',
            'impressions' => 'Consolidada: aparece no histórico, não no painel.',
            'tags' => ['criação'],
        ]);

        $this->note($discipline, $token, 'A aliança das obras', [
            'review_stage' => 3,
            'next_review_at' => $today->addWeeks(2)->toDateString(),
            'summary' => 'Adão responde como cabeça federal de todos nós. A condição era obediência perfeita.',
            'ai_summary' => 'Aliança feita com Adão como cabeça federal da humanidade, com condição de obediência perfeita.',
            'impressions' => 'Agendada para duas semanas à frente: não deve aparecer na fila de hoje.',
            'tags' => ['aliança'],
        ]);

        // Devida hoje, mas sem resumo escrito: fica fora da fila e alimenta o
        // aviso de notas pendentes na tela principal.
        $this->note($discipline, $token, 'A perseverança dos santos', [
            'review_stage' => 1,
            'next_review_at' => $today->toDateString(),
            'summary' => null,
            'ai_summary' => null,
            'impressions' => 'Sem resumo escrito: só entra na fila depois que eu resumir.',
            'tags' => ['soteriologia'],
        ]);
    }

    /**
     * Disciplina cuja aula é em outro dia: alimenta o bloco de próximos encontros.
     */
    private function seedUpcomingDiscipline(AccessToken $token, CarbonImmutable $today, Weekday $weekday): void
    {
        $otherWeekday = Weekday::fromDate($today->addDays(2));

        $discipline = $this->discipline('História da Igreja', [
            'icon' => 'building-library',
            'period' => 1,
            'code' => 'HIS-101',
            'professor' => 'Rev. Agostinho de Hipona',
            'class_weekday' => $otherWeekday,
            'completed_at' => null,
        ]);

        $this->note($discipline, $token, 'A controvérsia pelagiana', [
            'review_stage' => 1,
            'next_review_at' => $today->toDateString(),
            'summary' => 'Pelágio negou a corrupção herdada; Agostinho respondeu que a graça é causa da obediência, não prêmio por ela.',
            'ai_summary' => 'Pelágio negava a corrupção herdada e a necessidade da graça preveniente; Agostinho respondeu com a graça como causa, não prêmio.',
            'impressions' => 'Devida, mas só será cobrada no dia da aula desta disciplina.',
            'tags' => ['patrística', 'graça'],
        ]);
    }

    /**
     * Disciplina encerrada: prova que ela não cobra mais nada.
     */
    private function seedCompletedDiscipline(AccessToken $token, CarbonImmutable $today, Weekday $weekday): void
    {
        $discipline = $this->discipline('Hermenêutica', [
            'icon' => 'document-magnifying-glass',
            'period' => 1,
            'code' => 'HER-101',
            'professor' => 'Rev. Martinho Lutero',
            'class_weekday' => $weekday,
            'completed_at' => $today->subWeeks(4),
        ]);

        $this->note($discipline, $token, 'O sentido literal e a analogia da fé', [
            'review_stage' => 2,
            'next_review_at' => $today->subWeek()->toDateString(),
            'ai_summary' => 'O sentido literal é o sentido pretendido pelo autor; a analogia da fé impede leituras que contradigam o todo da Escritura.',
            'impressions' => 'Disciplina encerrada: mesmo devida, esta nota não aparece no painel.',
            'tags' => ['hermenêutica'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function discipline(string $title, array $attributes): Disciplines
    {
        $title = trim($title.' '.self::SUFFIX);

        return Disciplines::updateOrCreate(
            ['title' => $title],
            ['slug' => Str::slug($title), ...$attributes],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function note(Disciplines $discipline, AccessToken $token, string $title, array $attributes): Notes
    {
        return Notes::updateOrCreate(
            [
                'discipline_id' => $discipline->id,
                'title' => trim($title.' '.self::SUFFIX),
            ],
            ['access_token_id' => $token->id, ...$attributes],
        );
    }

    private function concept(Notes $note, string $term, string $definition): void
    {
        Concepts::updateOrCreate(
            ['note_id' => $note->id, 'term' => $term],
            ['definition' => $definition],
        );
    }

    private function advice(Notes $note, string $category, string $advice): void
    {
        PastoralAdvices::updateOrCreate(
            ['note_id' => $note->id, 'category' => $category],
            ['advice' => $advice],
        );
    }
}
