<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\Enums\WarrantyType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class WarrantyTypeType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'warranty_type';
    }
    protected function getEnumClass(): string
    {
        return WarrantyType::class;
    }
}
