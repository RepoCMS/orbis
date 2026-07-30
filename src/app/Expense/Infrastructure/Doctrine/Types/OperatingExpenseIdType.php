<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Doctrine\Types;

use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class OperatingExpenseIdType extends AbstractUlidType
{
    public function getName(): string { return 'expense_operating_id'; }
    protected function getValueObjectClass(): string { return OperatingExpenseIdValueObject::class; }
}
