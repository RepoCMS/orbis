<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class CashAdvanceAlreadySettledException extends DomainException
{
    public static function create(): self
    {
        return new self('El adelanto ya ha sido liquidado anteriormente');
    }
}
