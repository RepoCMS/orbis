<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\Enums\SubLocationType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class SubLocationTypeEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'inventory_sub_location_type';
    }

    protected function getEnumClass(): string
    {
        return SubLocationType::class;
    }
}
