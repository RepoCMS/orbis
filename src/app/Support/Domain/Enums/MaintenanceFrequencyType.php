<?php

declare(strict_types=1);

namespace App\Support\Domain\Enums;

enum MaintenanceFrequencyType: string
{
    case MONTHLY     = 'monthly';
    case QUARTERLY   = 'quarterly';
    case SEMI_ANNUAL = 'semi_annual';
    case ANNUAL      = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY     => 'Mensual',
            self::QUARTERLY   => 'Trimestral',
            self::SEMI_ANNUAL => 'Semestral',
            self::ANNUAL      => 'Anual',
        };
    }

    /**
     * Devuelve el intervalo DateInterval para calcular la próxima fecha.
     */
    public function toDateInterval(): \DateInterval
    {
        return match ($this) {
            self::MONTHLY     => new \DateInterval('P1M'),
            self::QUARTERLY   => new \DateInterval('P3M'),
            self::SEMI_ANNUAL => new \DateInterval('P6M'),
            self::ANNUAL      => new \DateInterval('P1Y'),
        };
    }
}
