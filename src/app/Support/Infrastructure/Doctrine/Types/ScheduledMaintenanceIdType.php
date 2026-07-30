<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\ScheduledMaintenanceIdValueObject;

final class ScheduledMaintenanceIdType extends AbstractUlidType
{
    public function getName(): string { return 'scheduled_maintenance_id'; }
    protected function getValueObjectClass(): string { return ScheduledMaintenanceIdValueObject::class; }
}
