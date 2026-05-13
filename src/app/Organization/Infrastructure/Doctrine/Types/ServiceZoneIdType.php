<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class ServiceZoneIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'service_zone_id';
    }

    protected function getValueObjectClass(): string
    {
        return ServiceZoneIdValueObject::class;
    }
}
