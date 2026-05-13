<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\Enums\ServiceZoneStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class ServiceZoneStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'service_zone_status';
    }

    protected function getEnumClass(): string
    {
        return ServiceZoneStatus::class;
    }
}
