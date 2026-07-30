<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\ValueObjects\ServiceZoneAssignmentIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class ServiceZoneAssignmentIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'service_zone_assignment_id';
    }

    protected function getValueObjectClass(): string
    {
        return ServiceZoneAssignmentIdValueObject::class;
    }
}
