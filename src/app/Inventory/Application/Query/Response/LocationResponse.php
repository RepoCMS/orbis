<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\Location;

final readonly class LocationResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $city,
        public string $address,
        public string $type,
        public string $typeLabel,
    ) {
    }

    public static function fromEntity(Location $location): self
    {
        $typeLabel = match ($location->type()) {
            \App\Inventory\Domain\Enums\LocationType::WAREHOUSE  => 'Almacén',
            \App\Inventory\Domain\Enums\LocationType::LABORATORY => 'Laboratorio',
            \App\Inventory\Domain\Enums\LocationType::BACKPACK   => 'Mochila',
        };

        return new self(
            id:        (string) $location->id(),
            name:      (string) $location->name(),
            city:      $location->city(),
            address:   $location->address(),
            type:      $location->type()->value,
            typeLabel: $typeLabel,
        );
    }
}
