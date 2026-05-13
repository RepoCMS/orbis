<?php
declare(strict_types=1);
namespace App\Inventory\Infrastructure\Doctrine\Types;
use App\Inventory\Domain\Enums\LocationType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class LocationTypeEnumType extends AbstractStringEnumType
{
    public function getName(): string { return 'inventory_location_type'; }
    protected function getEnumClass(): string { return LocationType::class; }
}
