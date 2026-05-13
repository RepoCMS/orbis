<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class SerializedStockIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'serialized_stock_id';
    }

    protected function getValueObjectClass(): string
    {
        return SerializedStockIdValueObject::class;
    }
}
