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
        return 'Reescreva o conteúdo a seguir como um parágrafo único e natural, em linguagem simples e acessível, como se você estivesse explicando o assunto a alguém do zero: não copie frases nem estrutura do texto original, não anuncie as categorias (conceitos, conselhos pastorais, impressões, experiências de vida) como blocos separados, e sim funda essas informações organicamente na narrativa, priorizando fluidez e sentido sobre completude.';
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
