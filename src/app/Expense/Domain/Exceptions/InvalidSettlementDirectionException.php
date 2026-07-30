<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InvalidSettlementDirectionException extends DomainException
{
    public static function create(): self
    {
        return new self('La dirección de liquidación es inválida');
    }
}
