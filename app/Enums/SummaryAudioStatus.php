<?php

declare(strict_types=1);

namespace App\Enums;

enum SummaryAudioStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Failed = 'failed';

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isReady(): bool
    {
        return $this === self::Ready;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }
}
