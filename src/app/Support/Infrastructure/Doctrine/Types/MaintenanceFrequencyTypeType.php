<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;
use App\Support\Domain\Enums\MaintenanceFrequencyType;

final class MaintenanceFrequencyTypeType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'maintenance_frequency_type';
    }
    protected function getEnumClass(): string
    {
        return MaintenanceFrequencyType::class;
    }
}
