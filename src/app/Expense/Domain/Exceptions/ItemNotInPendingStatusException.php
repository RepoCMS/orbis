<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Expense\Domain\Enums\ExpenseItemStatus;
use App\Shared\Exceptions\DomainException;

final class ItemNotInPendingStatusException extends DomainException
{
    public static function withStatus(ExpenseItemStatus $current): self
    {
        return new self(
            sprintf(
                'El item de gasto debe estar en estado "pending". Estado actual: "%s".',
                $current->value
            )
        );
    }
}
