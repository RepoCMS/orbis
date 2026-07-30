<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InvalidAmountException extends DomainException
{
    public static function mustBePositive(): self
    {
        return new self('El monto debe ser un valor positivo');
    }

    public static function exceedsMaximum(float $max): self
    {
        return new self(sprintf('El monto excede el máximo permitido de %s', number_format($max, 2)));
    }
}
