<?php

declare(strict_types=1);

namespace App\Support\Domain\AtmModel;

/**
 * Momento de la evidencia: antes (before) o después (after) de la reparación.
 *
 * Los valores son lowercase para consistencia con la base de datos.
 */
enum EvidenceMoment: string
{
    case BEFORE = 'before';
    case AFTER  = 'after';

    public function label(): string
    {
        return match ($this) {
            self::BEFORE => 'Antes',
            self::AFTER  => 'Después',
        };
    }
}
