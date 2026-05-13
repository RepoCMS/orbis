<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class LocationIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'location_id';
    }

    protected function getValueObjectClass(): string
    {
        return LocationIdValueObject::class;
    }
}
