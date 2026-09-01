<?php

declare(strict_types=1);

namespace App\Support;

final class PortugueseStopwords
{
    /**
     * Function words that should never be turned into a cloze blank: articles,
     * prepositions, contractions, conjunctions, clitic/personal pronouns,
     * possessives, demonstratives, and the common short forms of
     * ser/estar/ter/haver. Stored accent-folded so lookups go through
     * TextNormalizer::fold().
     *
     * @var list<string>
     */
    public const LIST = [
        // Artigos
        'o', 'a', 'os', 'as', 'um', 'uma', 'uns', 'umas',
        // Preposições
        'ante', 'apos', 'ate', 'com', 'contra', 'de', 'desde', 'em', 'entre',
        'para', 'perante', 'por', 'sem', 'sob', 'sobre', 'tras',
        // Contrações
        'do', 'da', 'dos', 'das', 'no', 'na', 'nos', 'nas', 'ao', 'aos',
        'num', 'numa', 'nuns', 'numas', 'dum', 'duma', 'duns', 'dumas',
        'pelo', 'pela', 'pelos', 'pelas',
        'neste', 'nesta', 'nestes', 'nestas', 'nesse', 'nessa', 'nesses', 'nessas',
        'nisto', 'nisso', 'naquele', 'naquela', 'naquilo',
        'deste', 'desta', 'destes', 'destas', 'desse', 'dessa', 'desses', 'dessas',
        'disto', 'disso', 'daquele', 'daquela', 'daquilo',
        'dele', 'dela', 'deles', 'delas',
        // Conjunções
        'e', 'ou', 'mas', 'porem', 'contudo', 'todavia', 'entretanto', 'que',
        'se', 'pois', 'porque', 'porquanto', 'portanto', 'logo', 'entao',
        'assim', 'como', 'quando', 'enquanto', 'embora', 'conforme', 'nem',
        'ora', 'seja', 'caso', 'senao',
        // Pronomes pessoais / átonos
        'eu', 'tu', 'ele', 'ela', 'nos', 'vos', 'eles', 'elas', 'me', 'te',
        'lhe', 'lhes', 'mim', 'ti', 'si', 'comigo', 'contigo', 'consigo',
        'conosco', 'connosco', 'convosco',
        // Possessivos
        'meu', 'minha', 'meus', 'minhas', 'teu', 'tua', 'teus', 'tuas',
        'seu', 'sua', 'seus', 'suas', 'nosso', 'nossa', 'nossos', 'nossas',
        'vosso', 'vossa', 'vossos', 'vossas',
        // Demonstrativos
        'este', 'esta', 'estes', 'estas', 'esse', 'essa', 'esses', 'essas',
        'aquele', 'aquela', 'aqueles', 'aquelas', 'isto', 'isso', 'aquilo',
        // Formas curtas de ser / estar / ter / haver
        'sao', 'foi', 'era', 'eram', 'ser', 'sendo', 'sido',
        'estao', 'estava', 'estavam', 'estar',
        'ha', 'havia', 'haver', 'tem', 'tinha', 'ter', 'tendo', 'tido',
        // Advérbios de negação / afirmação
        'nao', 'sim',
    ];

    public static function contains(string $word): bool
    {
        static $lookup;

        $lookup ??= array_fill_keys(self::LIST, true);

        return isset($lookup[TextNormalizer::fold($word)]);
    }
}
