<?php
declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;
use App\Inventory\Domain\Enums\RepairPriority;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class RepairPriorityEnumType extends AbstractStringEnumType
{
    public function getName(): string { return 'inventory_repair_priority'; }
    protected function getEnumClass(): string { return RepairPriority::class; }
}
