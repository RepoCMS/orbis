<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class LocationNameType extends AbstractStringValueObjectType
{
    public function getName(): string { return 'location_name'; }
    protected function getValueObjectClass(): string { return LocationNameValueObject::class; }
}
