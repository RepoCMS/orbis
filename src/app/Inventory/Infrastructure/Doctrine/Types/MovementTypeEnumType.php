<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\Enums\MovementType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class MovementTypeEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'inventory_movement_type';
    }
    protected function getEnumClass(): string
    {
        return MovementType::class;
    }
}
