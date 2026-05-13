<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class ProductNameType extends AbstractStringValueObjectType
{
    public function getName(): string { return 'product_name'; }
    protected function getValueObjectClass(): string { return ProductNameValueObject::class; }
}
