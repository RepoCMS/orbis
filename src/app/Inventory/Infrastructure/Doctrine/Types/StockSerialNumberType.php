<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class StockSerialNumberType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'stock_serial_number';
    }

    protected function getValueObjectClass(): string
    {
        return StockSerialNumberValueObject::class;
    }
}
