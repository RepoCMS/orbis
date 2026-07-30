<?php

declare(strict_types=1);

namespace App\Support\Domain\Enums;

enum RescheduleRequestedBy: string
{
    case CLIENT = 'client';
    case CMS    = 'cms';

    public function label(): string
    {
        return match($this) {
            self::CLIENT => 'Cliente',
            self::CMS    => 'CMS',
        };
    }
}
