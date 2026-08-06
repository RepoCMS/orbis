<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\Enums\SerializedStockStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class SerializedStockStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'inventory_serialized_stock_status';
    }
    protected function getEnumClass(): string
    {
        return SerializedStockStatus::class;
    }
}
