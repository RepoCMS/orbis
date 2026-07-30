<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class ItemAlreadyApprovedException extends DomainException
{
    public static function create(): self
    {
        return new self('El item de gasto ya fue aprobado anteriormente');
    }
}
