<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class ProductIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'product_id';
    }

    protected function getValueObjectClass(): string
    {
        return ProductIdValueObject::class;
    }
}
