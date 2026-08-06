<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Doctrine\Types;

use App\Expense\Domain\Enums\ExpenseItemType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class ExpenseItemTypeType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'expense_item_type';
    }
    protected function getEnumClass(): string
    {
        return ExpenseItemType::class;
    }
}
