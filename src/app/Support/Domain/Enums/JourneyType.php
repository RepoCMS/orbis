<?php

declare(strict_types=1);

namespace App\Support\Domain\Enums;

enum JourneyType: string
{
    case OUTWARD   = 'outward';
    case NEXT_SITE = 'next_site';
    case RETURN    = 'return';

    public function label(): string
    {
        return match($this) {
            self::OUTWARD   => 'Viaje de ida',
            self::NEXT_SITE => 'Desplazamiento entre cajeros',
            self::RETURN    => 'Viaje de regreso',
        };
    }
}
