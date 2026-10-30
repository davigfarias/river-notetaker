<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Markdown\HeadingIdsExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\StringContainerHelper;

/**
 * Índice (TOC) de um texto Markdown: os headings "#" a "###", com os mesmos
 * ids que Str::markdownRich() grava no HTML quando recebe o mesmo prefixo.
 */
final class MarkdownToc
{
    /**
     * Abaixo disto o índice não ajuda: some em vez de aparecer vazio.
     */
    public const int MIN_HEADINGS = 2;

    /**
     * @return array<int, array{level: int, text: string, id: string}>
     */
    public static function headings(?string $markdown, string $prefix): array
    {
        if ($markdown === null || trim($markdown) === '') {
            return [];
        }

        $converter = new GithubFlavoredMarkdownConverter;
        $converter->getEnvironment()->addExtension(new HeadingIdsExtension($prefix));

        $headings = [];

        foreach ($converter->convert($markdown)->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if ($node instanceof Heading && $node->data->has('attributes/id')) {
                $headings[] = [
                    'level' => $node->getLevel(),
                    'text' => StringContainerHelper::getChildText($node),
                    'id' => (string) $node->data->get('attributes/id'),
                ];
            }
        }

        return count($headings) >= self::MIN_HEADINGS ? $headings : [];
    }
}
