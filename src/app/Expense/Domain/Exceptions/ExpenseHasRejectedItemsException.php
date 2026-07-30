<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class ExpenseHasRejectedItemsException extends DomainException
{
    public static function create(): self
    {
        return new self('No se puede aprobar la liquidación: existen items rechazados');
    }
}
