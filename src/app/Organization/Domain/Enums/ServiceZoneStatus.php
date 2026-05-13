<?php

declare(strict_types=1);

namespace App\Organization\Domain\Enums;

enum ServiceZoneStatus: string
{
    case ACTIVE   = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE   => 'Activa',
            self::INACTIVE => 'Inactiva',
        };
    }
}
