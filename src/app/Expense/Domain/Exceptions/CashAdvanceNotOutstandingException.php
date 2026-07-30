<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Shared\Exceptions\DomainException;

final class CashAdvanceNotOutstandingException extends DomainException
{
    public static function withStatus(CashAdvanceStatus $current): self
    {
        return new self(
            sprintf(
                'El adelanto debe estar en estado "outstanding". Estado actual: "%s".',
                $current->value
            )
        );
    }
}
