<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Expense\Domain\Enums\ExpenseStatus;
use App\Shared\Exceptions\DomainException;

final class ExpenseNotSubmittedOrRejectedException extends DomainException
{
    public static function withStatus(ExpenseStatus $current): self
    {
        return new self(
            sprintf(
                'La liquidación debe estar en estado "submitted" o "rejected". Estado actual: "%s".',
                $current->value
            )
        );
    }
}
