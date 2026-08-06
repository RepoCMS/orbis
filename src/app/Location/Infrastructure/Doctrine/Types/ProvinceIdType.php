<?php

declare(strict_types=1);

namespace App\Location\Infrastructure\Doctrine\Types;

use App\Location\Domain\ValueObjects\ProvinceIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class ProvinceIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'geo_province_id';
    }
    protected function getValueObjectClass(): string
    {
        return ProvinceIdValueObject::class;
    }
}
