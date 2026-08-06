<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Doctrine\Types;

use App\Expense\Domain\Enums\SettlementDirection;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class SettlementDirectionType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'expense_settlement_direction';
    }
    protected function getEnumClass(): string
    {
        return SettlementDirection::class;
    }
}
