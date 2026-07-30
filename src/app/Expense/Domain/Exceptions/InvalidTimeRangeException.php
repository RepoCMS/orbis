<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InvalidTimeRangeException extends DomainException
{
    public static function startAfterEnd(string $start, string $end): self
    {
        return new self(
            sprintf(
                'La hora de inicio (%s) debe ser anterior a la hora de fin (%s).',
                $start,
                $end
            )
        );
    }
}
