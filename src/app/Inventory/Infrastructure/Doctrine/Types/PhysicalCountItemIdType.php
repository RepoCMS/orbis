<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\PhysicalCountItemIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class PhysicalCountItemIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'physical_count_item_id';
    }

    protected function getValueObjectClass(): string
    {
        return PhysicalCountItemIdValueObject::class;
    }
}
