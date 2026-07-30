<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Doctrine\Types;

use App\Expense\Domain\Enums\ExpenseStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class ExpenseStatusType extends AbstractStringEnumType
{
    public function getName(): string { return 'expense_status'; }
    protected function getEnumClass(): string { return ExpenseStatus::class; }
}
