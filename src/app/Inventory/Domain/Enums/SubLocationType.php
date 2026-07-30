<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

enum SubLocationType: string
{
    case RACK = 'rack';
    case SHELF = 'shelf';
    case POSITION = 'position';

    public function label(): string
    {
        return match ($this) {
            self::RACK     => 'Rack',
            self::SHELF    => 'Estante',
            self::POSITION => 'Posición',
        };
    }

    public function canHaveChildren(): bool
    {
        return $this !== self::POSITION;
    }
}
