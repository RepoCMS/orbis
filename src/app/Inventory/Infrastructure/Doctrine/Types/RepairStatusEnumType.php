<?php
declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class RepairStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string { return 'inventory_repair_status'; }
    protected function getEnumClass(): string { return RepairStatus::class; }
}
