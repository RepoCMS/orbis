<?php
declare(strict_types=1);
namespace App\Inventory\Infrastructure\Doctrine\Types;
use App\Inventory\Domain\Enums\MovementStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class MovementStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string { return 'inventory_movement_status'; }
    protected function getEnumClass(): string { return MovementStatus::class; }
}
