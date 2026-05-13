<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListLocations;

use App\Inventory\Application\Query\Response\LocationResponse;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;

final readonly class ListLocationsHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    /** @return LocationResponse[] */
    public function __invoke(): array
    {
        return array_map(
            fn ($l) => LocationResponse::fromEntity($l),
            $this->locationRepository->findAll(),
        );
    }
}
