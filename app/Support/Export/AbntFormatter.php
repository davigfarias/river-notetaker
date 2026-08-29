<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Models\ReferenceMaterial;
use Illuminate\Support\Str;

final class AbntFormatter
{
    /**
     * Full ABNT reference line for a work. Uses the hand-written
     * `abnt_reference` when available, otherwise builds a best-effort line
     * from the structured fields.
     */
    public function reference(ReferenceMaterial $material): string
    {
        if (filled($material->abnt_reference)) {
            return trim((string) $material->abnt_reference);
        }

        $author = filled($material->author)
            ? $this->invertAuthor((string) $material->author).'. '
            : '';

        $title = rtrim(trim($material->title), '.').'. ';

        $imprint = collect([
            filled($material->publisher) ? trim((string) $material->publisher) : null,
            $material->year !== null ? (string) $material->year : 's.d.',
        ])->filter()->implode(', ');

        return $author.$title.$imprint.'.';
    }

    /**
     * Parenthetical in-text citation, e.g. "(WARREN, 2002, p. 42)".
     */
    public function inText(ReferenceMaterial $material, ?string $location = null): string
    {
        $key = filled($material->author)
            ? Str::upper($this->lastName((string) $material->author))
            : Str::upper(trim(Str::words($material->title, 3, '')));

        $parts = [$key];

        if ($material->year !== null) {
            $parts[] = (string) $material->year;
        }

        if (filled($location)) {
            $parts[] = $this->normalizeLocation((string) $location);
        }

        return '('.implode(', ', $parts).')';
    }

    private function invertAuthor(string $author): string
    {
        $author = trim($author);

        if (! str_contains($author, ' ')) {
            return Str::upper($author);
        }

        $last = $this->lastName($author);
        $rest = trim(Str::beforeLast($author, ' '));

        return Str::upper($last).', '.$rest;
    }

    private function lastName(string $author): string
    {
        $parts = preg_split('/\s+/', trim($author)) ?: [$author];

        return (string) end($parts);
    }

    private function normalizeLocation(string $location): string
    {
        $location = trim($location);

        if (preg_match('/^(p\.|pp\.|f\.|cap\.|§|min\.|\d{1,2}:\d{2})/i', $location)) {
            return $location;
        }

        return 'p. '.$location;
    }
}
