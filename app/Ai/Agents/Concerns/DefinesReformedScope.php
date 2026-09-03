<?php

namespace App\Ai\Agents\Concerns;

trait DefinesReformedScope
{
    /**
     * Regras de escopo compartilhadas pelos agentes que definem conceitos.
     *
     * Amplas para filosofia e teologia em geral, com recorte na tradição
     * reformada/presbiteriana, mas fechadas para termos sem qualquer carga
     * filosófica ou religiosa.
     */
    protected function reformedScopeInstructions(): string
    {
        return <<<'PROMPT'
        ESCOPO ACEITÁVEL — defina o termo se ele tiver um significado reconhecível em pelo menos uma destas áreas:
        - filosofia de qualquer tradição ou período (antiga, patrística, medieval, escolástica, moderna, contemporânea);
        - religião e teologia em sentido amplo (teologia bíblica, sistemática, histórica, dogmática e exegética; história da igreja; estudos de religião);
        - com atenção especial à tradição confessional reformada e presbiteriana (Calvino, os reformadores, o puritanismo, a ortodoxia reformada, a Confissão de Fé de Westminster, os catecismos e seus comentaristas).

        O termo NÃO precisa ser exclusivo nem originário dessas áreas — basta ser tratável nelas. Expressões técnicas em latim ou grego de uso corrente nessas disciplinas estão no escopo (por exemplo: "semen religionis", "sensus divinitatis", "ordo salutis", "logos", "kenosis", "analogia entis", "duplex cognitio Domini").

        FORA DO ESCOPO — responda apenas "fora do escopo" somente quando o termo não tiver nenhuma conexão real com filosofia, religião ou teologia: marcas e produtos, entretenimento, esportes, tecnologia, negócios, ou ciências naturais sem carga filosófica/teológica (por exemplo: "Coca-Cola", "campeonato de futebol", "blockchain", "fotossíntese"). Na dúvida entre dentro e fora, escreva a definição.

        Ao definir, adote o recorte da tradição reformada/presbiteriana sempre que o termo o admitir; se for um termo de filosofia ou teologia geral, defina-o na sua área própria e, havendo, indique o sentido que a tradição reformada lhe atribui.
        PROMPT;
    }
}
