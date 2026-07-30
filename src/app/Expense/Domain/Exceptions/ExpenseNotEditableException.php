<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Expense\Domain\Enums\ExpenseStatus;
use App\Shared\Exceptions\DomainException;

final class ExpenseNotEditableException extends DomainException
{
    public static function withStatus(ExpenseStatus $current): self
    {
        return new self(
            sprintf(
                'No se puede modificar una liquidación en estado "%s". Solo se permite en borrador o rechazado.',
                $current->value
            )
        );
    }
}
