<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\Enums\TechnicianLoanStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class TechnicianLoanStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'inventory_technician_loan_status';
    }

    protected function getEnumClass(): string
    {
        return TechnicianLoanStatus::class;
    }
}
