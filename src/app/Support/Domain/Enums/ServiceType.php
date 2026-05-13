<?php

declare(strict_types=1);

namespace App\Support\Domain\Enums;

enum ServiceType: string
{
    case PREASLISTAMIENTO = 'PREASLISTAMIENTO';
    case INSTALACION      = 'INSTALACION';
    case DESINSTALACION   = 'DESINSTALACION';
    case VISITA           = 'VISITA';
    case ACTIVACION       = 'ACTIVACION';
    case DESACTIVACION    = 'DESACTIVACION';
    case CORRECTIVO       = 'CORRECTIVO';
    case MANTENIMIENTO    = 'MANTENIMIENTO';

    public function label(): string
    {
        return match($this) {
            self::PREASLISTAMIENTO => 'Prealistamiento',
            self::INSTALACION      => 'Instalación',
            self::DESINSTALACION   => 'Desinstalación',
            self::VISITA           => 'Visita',
            self::ACTIVACION       => 'Activación',
            self::DESACTIVACION    => 'Desactivación',
            self::CORRECTIVO       => 'Correctivo',
            self::MANTENIMIENTO    => 'Mantenimiento',
        };
    }
}
