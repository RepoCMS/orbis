<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\Enums\PhysicalCountStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class PhysicalCountStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'inventory_physical_count_status';
    }

    protected function getEnumClass(): string
    {
        return PhysicalCountStatus::class;
    }
}
