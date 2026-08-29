<?php

declare(strict_types=1);

namespace App\Enums;

enum ExportFormat: string
{
    case Docx = 'docx';
    case Pdf = 'pdf';

    public function label(): string
    {
        return match ($this) {
            self::Docx => 'Word (.docx)',
            self::Pdf => 'PDF (.pdf)',
        };
    }

    public function extension(): string
    {
        return $this->value;
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Docx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            self::Pdf => 'application/pdf',
        };
    }
}
