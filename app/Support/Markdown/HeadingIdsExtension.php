<?php

declare(strict_types=1);

namespace App\Support\Markdown;

use Illuminate\Support\Str;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\StringContainerHelper;

/**
 * Dá um id único a cada heading de nível 1 a 3 ("{prefixo}-{slug}", com
 * sufixo numérico quando o texto se repete), para o índice (TOC) poder
 * apontar para eles. O prefixo evita colisão entre campos na mesma página.
 */
final readonly class HeadingIdsExtension implements ExtensionInterface
{
    public const int MAX_LEVEL = 3;

    public function __construct(private string $prefix) {}

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event): void {
            $used = [];

            foreach ($event->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
                if (! $node instanceof Heading || $node->getLevel() > self::MAX_LEVEL) {
                    continue;
                }

                $slug = Str::slug(StringContainerHelper::getChildText($node)) ?: 'secao';
                $id = "{$this->prefix}-{$slug}";

                $used[$id] = ($used[$id] ?? 0) + 1;

                if ($used[$id] > 1) {
                    $id .= '-'.$used[$id];
                }

                $node->data->set('attributes/id', $id);
            }
        }, -100);
    }
}
