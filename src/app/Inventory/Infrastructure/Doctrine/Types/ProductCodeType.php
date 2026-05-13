<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class ProductCodeType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'product_code';
    }

    protected function getValueObjectClass(): string
    {
        return ProductCodeAssignedBySupplierValueObject::class;
    }
}
