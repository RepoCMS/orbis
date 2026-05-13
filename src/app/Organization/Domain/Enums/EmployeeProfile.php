<?php

declare(strict_types=1);

namespace App\Organization\Domain\Enums;

enum EmployeeProfile: string
{
    case TECHNICIAN     = 'technician';
    case COORDINATOR    = 'coordinator';
    case ADMINISTRATIVE = 'administrative';

    public function label(): string
    {
        return match($this) {
            self::TECHNICIAN     => 'Técnico',
            self::COORDINATOR    => 'Coordinador',
            self::ADMINISTRATIVE => 'Administrativo',
        };
    }

    public function isTechnician(): bool
    {
        return $this === self::TECHNICIAN;
    }
}
