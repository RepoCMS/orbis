<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Doctrine\Types;

use App\Expense\Domain\Enums\CashAdvanceStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class CashAdvanceStatusType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'expense_cash_advance_status';
    }
    protected function getEnumClass(): string
    {
        return CashAdvanceStatus::class;
    }
}
