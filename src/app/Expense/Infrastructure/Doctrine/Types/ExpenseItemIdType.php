<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Doctrine\Types;

use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class ExpenseItemIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'expense_item_id';
    }
    protected function getValueObjectClass(): string
    {
        return ExpenseItemIdValueObject::class;
    }
}
