<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class TechnicianLoanIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'technician_loan_id';
    }

    protected function getValueObjectClass(): string
    {
        return TechnicianLoanIdValueObject::class;
    }
}
