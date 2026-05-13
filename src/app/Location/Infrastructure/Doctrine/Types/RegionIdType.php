<?php

declare(strict_types=1);

namespace App\Location\Infrastructure\Doctrine\Types;

use App\Location\Domain\ValueObjects\RegionIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class RegionIdType extends AbstractUlidType
{
    public function getName(): string { return 'geo_region_id'; }
    protected function getValueObjectClass(): string { return RegionIdValueObject::class; }
}
