<?php

namespace Database\Seeders;

use App\Actions\LinkConcepts;
use App\Models\Concepts;
use Illuminate\Database\Seeder;

/**
 * Additive demo data pro Graph View de Conceitos: termos de teologia com
 * definições reais, mais alguns links aleatórios entre eles pra testar o
 * grafo. Idempotente (firstOrCreate) e nunca apaga nada.
 * Rodar com: php artisan db:seed --class=ConceptSeeder
 */
class ConceptSeeder extends Seeder
{
    public function run(): void
    {
        $concepts = collect($this->concepts())
            ->map(fn (array $c) => Concepts::query()->firstOrCreate(
                ['term' => $c[0]],
                ['definition' => $c[1]],
            ));

        $action = app(LinkConcepts::class);
        $ids = $concepts->pluck('id')->all();

        foreach ($ids as $id) {
            $linksCount = random_int(1, 3);
            $candidates = collect($ids)->reject(fn ($other) => $other === $id)->shuffle()->take($linksCount);

            foreach ($candidates as $relatedId) {
                $action->handle($id, $relatedId);
            }
        }

        $this->command->info(sprintf('%d conceitos de teologia semeados com links aleatórios.', $concepts->count()));
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function concepts(): array
    {
        return [
            ['Graça', 'Favor imerecido de Deus para com o pecador, concedido sem base em mérito humano.'],
            ['Fé', 'Confiança que descansa na obra consumada de Cristo, dom de Deus e não obra do homem.'],
            ['Justificação', 'Ato judicial pelo qual Deus declara justo o pecador com base na justiça imputada de Cristo.'],
            ['Regeneração', 'Obra soberana do Espírito Santo que concede vida espiritual nova ao pecador morto em pecado.'],
            ['Santificação', 'Processo progressivo pelo qual o crente é conformado à imagem de Cristo pelo Espírito.'],
            ['Eleição', 'Escolha soberana e eterna de Deus de um povo para salvação, anterior a qualquer mérito.'],
            ['Expiação', 'Obra de Cristo na cruz que satisfaz a justiça de Deus e propicia o perdão dos pecados.'],
            ['Aliança', 'Vínculo soberano que Deus estabelece com o seu povo, estruturando toda a história da redenção.'],
            ['Trindade', 'Deus subsiste eternamente em três pessoas distintas — Pai, Filho e Espírito Santo — de uma só essência.'],
            ['Encarnação', 'O Filho eterno de Deus assumiu natureza humana plena, permanecendo plenamente divino.'],
            ['Pecado original', 'Corrupção e culpa herdadas de Adão, presentes em toda a natureza humana desde a queda.'],
            ['Redenção', 'Ato pelo qual Cristo resgata o pecador da escravidão do pecado mediante o pagamento do seu sangue.'],
            ['Propiciação', 'Obra de Cristo que aplaca a justa ira de Deus contra o pecado.'],
            ['Reconciliação', 'Restauração da paz entre Deus e o pecador, obtida pela obra de Cristo na cruz.'],
            ['Glorificação', 'Etapa final da salvação, em que o crente é conformado por completo à imagem de Cristo.'],
            ['Perseverança dos santos', 'Doutrina de que os verdadeiramente regenerados são guardados por Deus até o fim.'],
            ['Escatologia', 'Ramo da teologia que estuda os últimos eventos: volta de Cristo, ressurreição e juízo final.'],
            ['Eclesiologia', 'Doutrina da igreja: sua natureza, marcas, governo e missão.'],
            ['Soteriologia', 'Ramo da teologia que estuda a doutrina da salvação em todas as suas etapas.'],
            ['Pneumatologia', 'Ramo da teologia que estuda a pessoa e a obra do Espírito Santo.'],
            ['Cristologia', 'Ramo da teologia que estuda a pessoa e a obra de Jesus Cristo.'],
            ['Revelação especial', 'Autocomunicação de Deus através das Escrituras e, de modo supremo, em Cristo.'],
            ['Inerrância', 'Doutrina de que as Escrituras, nos seus autógrafos originais, não erram naquilo que afirmam.'],
            ['Sola Scriptura', 'Princípio da Reforma segundo o qual a Escritura é a única regra infalível de fé e prática.'],
        ];
    }
}
