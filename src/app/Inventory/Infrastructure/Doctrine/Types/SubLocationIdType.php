<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class SubLocationIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'sub_location_id';
    }

    protected function getValueObjectClass(): string
    {
        return SubLocationIdValueObject::class;
    }
}
