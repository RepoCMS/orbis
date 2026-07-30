<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Expense\Domain\Enums\ExpenseStatus;
use App\Shared\Exceptions\DomainException;

final class ExpenseNotRejectedException extends DomainException
{
    public static function withStatus(ExpenseStatus $current): self
    {
        return new self(
            sprintf(
                'Solo se puede reenviar una liquidación que fue rechazada. Estado actual: "%s".',
                $current->value
            )
        );
    }
}
