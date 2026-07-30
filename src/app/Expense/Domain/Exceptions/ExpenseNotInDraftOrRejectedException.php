<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Expense\Domain\Enums\ExpenseStatus;
use App\Shared\Exceptions\DomainException;

final class ExpenseNotInDraftOrRejectedException extends DomainException
{
    public static function withStatus(ExpenseStatus $current): self
    {
        return new self(
            sprintf(
                'La operación solo es válida para liquidaciones en estado "draft" o "rejected". Estado actual: "%s".',
                $current->value
            )
        );
    }
}
