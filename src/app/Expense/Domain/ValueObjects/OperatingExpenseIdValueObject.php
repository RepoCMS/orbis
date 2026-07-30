<?php

declare(strict_types=1);

namespace App\Expense\Domain\ValueObjects;

use App\Expense\Domain\Exceptions\OperatingExpenseIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class OperatingExpenseIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new OperatingExpenseIdException('Formato de ULID inválido para OperatingExpenseId.');
    }
}
