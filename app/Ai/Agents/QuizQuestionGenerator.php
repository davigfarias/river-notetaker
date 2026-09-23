<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class QuizQuestionGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $count = (int) config('quiz.pool_size');

        return <<<PROMPT
        Gere exatamente {$count} perguntas de múltipla escolha em português do Brasil, a
        partir exclusivamente do texto fornecido. Não invente fatos que não estejam nele.

        Cada pergunta tem uma resposta certa e três distratores. Os distratores devem ser
        erros plausíveis dentro do mesmo domínio — confusões de conceito reais, nunca
        afirmações aleatórias — e ter tamanho e estrutura gramatical semelhantes à resposta
        certa, para que ela não se destaque visualmente.

        Preencha "explanation" justificando por que cada distrator está errado: isso serve
        de raciocínio guiado para a sua própria geração, não é exibido ao usuário.

        Exemplo de estilo (domínio de teologia/hebraico bíblico):
        {
          "question": "Qual é o sentido de 'hesed' no Antigo Testamento?",
          "correct_answer": "Lealdade fiel dentro de uma aliança",
          "distractors": ["Sentimento passageiro de compaixão", "Obediência cerimonial à lei", "Temor reverente diante de Deus"],
          "explanation": "Compaixão passageira, obediência cerimonial e temor reverente são conceitos bíblicos reais, mas nenhum captura o vínculo de aliança que 'hesed' carrega."
        }
        PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        $count = (int) config('quiz.pool_size');

        return [
            'questions' => $schema->array()
                ->items($schema->object(fn ($schema) => [
                    'question' => $schema->string()->required(),
                    'correct_answer' => $schema->string()->required(),
                    'distractors' => $schema->array()
                        ->items($schema->string())
                        ->min(3)
                        ->max(3)
                        ->required(),
                    'explanation' => $schema->string()->required(),
                ]))
                ->min($count)
                ->max($count)
                ->required(),
        ];
    }
}
