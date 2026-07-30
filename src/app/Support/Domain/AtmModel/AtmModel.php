<?php

declare(strict_types=1);

namespace App\Support\Domain\AtmModel;

/**
 * Modelos de ATM Hyosung MX Series soportados.
 *
 * Los valores son lowercase (snake_case) para consistencia con la base de datos.
 */
enum AtmModel: string
{
    case MX8200ST = 'mx8200st';
    case MX8200QT = 'mx8200qt';
    case MX8200TA = 'mx8200ta';
    case MX8600S  = 'mx8600s';
    case MX5800T  = 'mx5800t';
    case MX5800   = 'mx5800';
    case MX5700   = 'mx5700';

    public function label(): string
    {
        return match ($this) {
            self::MX8200ST => 'Hyosung 8200ST',
            self::MX8200QT => 'Hyosung 8200QT',
            self::MX8200TA => 'Hyosung 8200TA',
            self::MX8600S  => 'Hyosung 8600S',
            self::MX5800T  => 'Hyosung 5800T',
            self::MX5800   => 'Hyosung 5800',
            self::MX5700   => 'Hyosung 5700',
        };
    }

    /**
     * Modelos con arquitectura Recycler (BRM + Document Deposit).
     * Incluye: 8200ST, 8200QT, 8200TA, 8600S.
     */
    public function isRecycler(): bool
    {
        return match ($this) {
            self::MX8200ST, self::MX8200QT, self::MX8200TA, self::MX8600S => true,
            default => false,
        };
    }

    /**
     * Modelos con arquitectura Dispenser (CDU).
     * Incluye: 5800T, 5800, 5700.
     */
    public function isDispenser(): bool
    {
        return match ($this) {
            self::MX5800T, self::MX5800, self::MX5700 => true,
            default => false,
        };
    }
}
