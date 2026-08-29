<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Enums\ExportFormat;
use App\Models\ReferenceMaterial;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Language;

final readonly class CitationExporter
{
    public function __construct(private AbntFormatter $abnt) {}

    /**
     * @param  Collection<int, ReferenceMaterial>  $materials  each with `citations` loaded
     */
    public function build(ExportFormat $format, string $heading, Collection $materials): string
    {
        return match ($format) {
            ExportFormat::Docx => $this->word($heading, $materials),
            ExportFormat::Pdf => $this->pdf($heading, $materials),
        };
    }

    /**
     * @param  Collection<int, ReferenceMaterial>  $materials
     */
    private function word(string $heading, Collection $materials): string
    {
        $phpWord = new PhpWord;
        $phpWord->getSettings()->setThemeFontLang(new Language(Language::PT_BR));

        $phpWord->addTitleStyle(1, ['size' => 16, 'bold' => true]);
        $phpWord->addTitleStyle(2, ['size' => 13, 'bold' => true]);

        $section = $phpWord->addSection();
        $section->addTitle($heading, 1);
        $section->addTextBreak();

        foreach ($materials as $material) {
            $section->addTitle($material->title, 2);
            $section->addText($this->abnt->reference($material), ['italic' => true]);
            $section->addTextBreak();

            foreach ($material->citations as $citation) {
                $section->addText(sprintf(
                    '“%s” %s',
                    trim($citation->quote_text),
                    $this->abnt->inText($material, $citation->location),
                ), null, ['spaceAfter' => 120]);

                if (filled($citation->personal_note)) {
                    $section->addText('Nota: '.trim((string) $citation->personal_note), ['italic' => true], ['spaceAfter' => 200]);
                }
            }

            $section->addTextBreak();
        }

        unset($materials, $material, $citation, $heading);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');

        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    /**
     * @param  Collection<int, ReferenceMaterial>  $materials
     */
    private function pdf(string $heading, Collection $materials): string
    {
        return Pdf::loadView('exports.citations', [
            'heading' => $heading,
            'materials' => $materials,
            'abnt' => $this->abnt,
        ])->output();
    }
}
