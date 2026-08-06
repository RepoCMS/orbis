<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class StockMinimumIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'stock_minimum_id';
    }
    protected function getValueObjectClass(): string
    {
        return StockMinimumIdValueObject::class;
    }
}
