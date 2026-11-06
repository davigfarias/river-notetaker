<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\PrincipleType;
use App\Models\Principle;

/**
 * Princípio pronto para estudo: título e enunciado já resolvidos
 * (no tipo Concept, vêm do termo e da definição do conceito).
 */
final readonly class StudyPrinciple
{
    public function __construct(
        public int $id,
        public string $title,
        public string $statement,
        public string $groupKey,
        public string $categoryTitle = '',
    ) {}

    public static function from(Principle $principle): self
    {
        $isConcept = $principle->type === PrincipleType::Concept;

        return new self(
            id: $principle->id,
            title: (string) ($isConcept ? $principle->concept?->term : $principle->title),
            statement: (string) ($isConcept ? $principle->concept?->definition : $principle->body),
            groupKey: self::groupKeyFor($principle),
            categoryTitle: (string) $principle->category?->title,
        );
    }

    /** Impressão digital do conteúdo: se mudar, exercícios gerados antes deixam de valer. */
    public function sourceHash(): string
    {
        return hash('sha256', implode("\0", [$this->title, $this->statement, $this->categoryTitle]));
    }

    /** Chave usada para intercalar categorias: a categoria, ou o tema quando não há. */
    public static function groupKeyFor(Principle $principle): string
    {
        return $principle->principle_category_id !== null
            ? 'c'.$principle->principle_category_id
            : 't'.$principle->principle_topic_id;
    }
}
