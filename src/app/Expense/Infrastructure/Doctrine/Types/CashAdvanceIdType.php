<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Doctrine\Types;

use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class CashAdvanceIdType extends AbstractUlidType
{
    public function getName(): string { return 'expense_cash_advance_id'; }
    protected function getValueObjectClass(): string { return CashAdvanceIdValueObject::class; }
}
