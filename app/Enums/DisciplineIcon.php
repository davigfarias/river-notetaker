<?php

declare(strict_types=1);

namespace App\Enums;

enum DisciplineIcon: string
{
    case BookOpen = 'book-open';
    case AcademicCap = 'academic-cap';
    case BuildingLibrary = 'building-library';
    case Beaker = 'beaker';
    case Calculator = 'calculator';
    case DocumentMagnifyingGlass = 'document-magnifying-glass';
    case LightBulb = 'light-bulb';
    case Sparkles = 'sparkles';
    case GlobeAlt = 'globe-alt';
    case GlobeAmericas = 'globe-americas';
    case GlobeAsiaAustralia = 'globe-asia-australia';
    case GlobeEuropeAfrica = 'globe-europe-africa';
    case Language = 'language';
    case Scale = 'scale';
    case Map = 'map';
    case MapPin = 'map-pin';
    case Clock = 'clock';
    case ChartBar = 'chart-bar';
    case PresentationChartBar = 'presentation-chart-bar';
    case PresentationChartLine = 'presentation-chart-line';
    case Newspaper = 'newspaper';
    case Bookmark = 'bookmark';
    case Clipboard = 'clipboard';
    case ClipboardDocumentCheck = 'clipboard-document-check';
    case PencilSquare = 'pencil-square';
    case PaintBrush = 'paint-brush';
    case MusicalNote = 'musical-note';
    case Film = 'film';
    case CodeBracket = 'code-bracket';
    case Cube = 'cube';
    case PuzzlePiece = 'puzzle-piece';
    case Heart = 'heart';
    case Star = 'star';
    case Flag = 'flag';
    case Banknotes = 'banknotes';

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
