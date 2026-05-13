<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class NonserializedStockIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'nonserialized_stock_id';
    }

    protected function getValueObjectClass(): string
    {
        return NonserializedStockIdValueObject::class;
    }
}
