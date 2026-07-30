<?php

declare(strict_types=1);

namespace App\Notification\Domain\Enums;

enum RecipientType: string
{
    case EMPLOYEE = 'employee';
    case CONTACT = 'contact';
    case USER = 'user';
    case LOCATION = 'location';

    public function label(): string
    {
        return match ($this) {
            self::EMPLOYEE => 'Empleado',
            self::CONTACT => 'Contacto de cliente',
            self::USER => 'Usuario',
            self::LOCATION => 'Ubicación',
        };
    }
}
