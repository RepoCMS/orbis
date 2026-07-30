<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InvalidDateRangeException extends DomainException
{
    public static function endBeforeStart(\DateTimeImmutable $start, \DateTimeImmutable $end): self
    {
        return new self(
            sprintf(
                'La fecha de inicio (%s) no puede ser posterior a la fecha de fin (%s)',
                $start->format('Y-m-d'),
                $end->format('Y-m-d')
            )
        );
    }

    public static function sameDayRequired(): self
    {
        return new self('Las fechas de inicio y fin deben ser el mismo día para gastos diarios');
    }
}
