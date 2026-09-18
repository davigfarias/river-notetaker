<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Message;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class Summarizer implements Agent, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $limit = (int) config('summarizer.target_characters');

        return <<<PROMPT
        Produza um TL;DR do conteúdo a seguir para ser OUVIDO em voz alta, não lido na tela.

        FORMA: português do Brasil, no máximo {$limit} caracteres, três frases curtas, em prosa corrida.

        RESTRIÇÕES DE LOCUÇÃO — o texto será lido por um sintetizador de voz, então:
        - frases de no máximo 20 palavras;
        - nada de parênteses, travessões, aspas, listas, markdown, títulos ou siglas;
        - vírgula e ponto como única pontuação;
        - escreva números e datas por extenso quando a leitura em voz alta ficar ambígua.

        CONTEÚDO: diga a tese central e, em seguida, por que ela importa. Corte todo exemplo, toda repetição e todo detalhe secundário. Se não couber, o que sai é o detalhe, nunca a tese.

        Não anuncie que é um resumo e não use fórmulas como "o texto fala sobre". Afirme direto o conteúdo. Nunca escreva mais que o original: se o conteúdo for curto, o resumo é mais curto ainda.
        PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }
}
