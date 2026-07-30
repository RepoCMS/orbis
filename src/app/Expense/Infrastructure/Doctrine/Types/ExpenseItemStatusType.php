<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Doctrine\Types;

use App\Expense\Domain\Enums\ExpenseItemStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class ExpenseItemStatusType extends AbstractStringEnumType
{
    public function getName(): string { return 'expense_item_status'; }
    protected function getEnumClass(): string { return ExpenseItemStatus::class; }
}
