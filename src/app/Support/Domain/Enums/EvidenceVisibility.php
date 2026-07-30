<?php

declare(strict_types=1);

namespace App\Support\Domain\Enums;

enum EvidenceVisibility: string
{
    case ALL = 'all';
    case INTERNAL_ONLY = 'internal_only';
    case FINANCE_ONLY = 'finance_only';

    public function label(): string
    {
        return match($this) {
            self::ALL => 'Visible para todos',
            self::INTERNAL_ONLY => 'Solo interno',
            self::FINANCE_ONLY => 'Solo finanzas',
        };
    }
}
