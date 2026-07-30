<?php

declare(strict_types=1);

namespace App\Support\Domain\AtmModel;

/**
 * Tipo de hallazgo encontrado en un componente de ATM.
 *
 * Los valores son lowercase para consistencia con la base de datos.
 */
enum FindingType: string
{
    case OK       = 'ok';
    case FAULTY   = 'faulty';
    case REPLACED = 'replaced';
    case PENDING  = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::OK       => 'Revisado OK',
            self::FAULTY   => 'Falla encontrada',
            self::REPLACED => 'Reemplazado',
            self::PENDING  => 'Pendiente',
        };
    }
}
