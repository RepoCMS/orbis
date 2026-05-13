<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\UpdateLocation;

final readonly class UpdateLocationCommand
{
    public function __construct(
        public string $id,
        public string $name,
        public string $city,
        public string $address,
    ) {
    }
}
