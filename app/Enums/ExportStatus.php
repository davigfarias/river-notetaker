<?php

declare(strict_types=1);

namespace App\Enums;

enum ExportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Na fila',
            self::Processing => 'Gerando',
            self::Completed => 'Pronto',
            self::Failed => 'Falhou',
            self::Expired => 'Expirado',
        };
    }

    public function isDownloadable(): bool
    {
        return $this === self::Completed;
    }

    public function isInProgress(): bool
    {
        return $this === self::Pending || $this === self::Processing;
    }
}
