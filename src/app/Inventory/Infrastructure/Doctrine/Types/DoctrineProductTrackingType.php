<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class DoctrineProductTrackingType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'product_tracking_type';
    }

    protected function getEnumClass(): string
    {
        return ProductTrackingType::class;
    }
}
