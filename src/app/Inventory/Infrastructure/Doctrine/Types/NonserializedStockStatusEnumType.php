<?php
declare(strict_types=1);
namespace App\Inventory\Infrastructure\Doctrine\Types;
use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class NonserializedStockStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string { return 'inventory_nonserialized_stock_status'; }
    protected function getEnumClass(): string { return NonserializedStockStatus::class; }
}
