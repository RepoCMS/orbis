<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class RepairIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'repair_id';
    }
    protected function getValueObjectClass(): string
    {
        return RepairIdValueObject::class;
    }
}
