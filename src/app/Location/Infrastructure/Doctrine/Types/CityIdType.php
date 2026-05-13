<?php

declare(strict_types=1);

namespace App\Location\Infrastructure\Doctrine\Types;

use App\Location\Domain\ValueObjects\CityIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class CityIdType extends AbstractUlidType
{
    public function getName(): string { return 'geo_city_id'; }
    protected function getValueObjectClass(): string { return CityIdValueObject::class; }
}
