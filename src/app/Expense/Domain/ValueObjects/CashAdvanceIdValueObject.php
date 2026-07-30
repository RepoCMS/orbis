<?php

declare(strict_types=1);

namespace App\Expense\Domain\ValueObjects;

use App\Expense\Domain\Exceptions\CashAdvanceIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class CashAdvanceIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new CashAdvanceIdException('Formato de ULID inválido para CashAdvanceId.');
    }
}
