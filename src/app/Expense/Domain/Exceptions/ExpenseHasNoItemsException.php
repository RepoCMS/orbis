<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class ExpenseHasNoItemsException extends DomainException
{
    public static function create(): self
    {
        return new self('La liquidación debe tener al menos un item de gasto para ser enviada');
    }
}
