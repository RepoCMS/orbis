<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class NotOutsideWorkingHoursException extends DomainException
{
    public static function create(string $startTime, string $endTime): self
    {
        return new self(
            sprintf(
                'El viaje debe iniciar antes de las 08:30 o terminar después de las 17:30. '
                . 'Rango ingresado: %s - %s.',
                $startTime,
                $endTime
            )
        );
    }
}
