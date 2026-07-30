<?php

declare(strict_types=1);

namespace App\Expense\Domain\ValueObjects;

use App\Expense\Domain\Exceptions\ExpenseItemIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class ExpenseItemIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ExpenseItemIdException('Formato de ULID inválido para ExpenseItemId.');
    }
}
