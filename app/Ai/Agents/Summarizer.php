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
        return 'Resuma o conteúdo a seguir em um único parágrafo corrido, sem tópicos nem marcadores, integrando nesta ordem os elementos presentes: conceitos centrais abordados, conselhos pastorais dados, impressões pessoais transmitidas e experiências de vida relatadas — sendo conciso, fiel ao teor original e omitindo qualquer categoria que não aparecer no texto.';
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
