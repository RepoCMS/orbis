<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateLocation;

final readonly class CreateLocationCommand
{
    public function __construct(
        public string $name,
        public string $city,
        public string $address,
        public string $type,
    ) {
    }
}
