<?php

declare(strict_types=1);

namespace App\Support\Domain\Enums;

enum ServiceType: string
{
    case PREASLISTAMIENTO = 'preaslistamiento';
    case INSTALACION      = 'instalacion';
    case DESINSTALACION   = 'desinstalacion';
    case VISITA           = 'visita';
    case ACTIVACION       = 'activacion';
    case DESACTIVACION    = 'desactivacion';
    case CORRECTIVO       = 'correctivo';
    case MANTENIMIENTO    = 'mantenimiento';

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
