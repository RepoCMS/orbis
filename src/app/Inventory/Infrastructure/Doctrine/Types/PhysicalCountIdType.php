<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class PhysicalCountIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'physical_count_id';
    }

    protected function getValueObjectClass(): string
    {
        return PhysicalCountIdValueObject::class;
    }
}
