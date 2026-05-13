<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\FindLocationById;

use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Query\Response\LocationResponse;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;

final readonly class FindLocationByIdHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    public function __invoke(string $id): LocationResponse
    {
        $location = $this->locationRepository->findById(new LocationIdValueObject($id))
            ?? throw LocationNotFoundException::withId($id);

        return LocationResponse::fromEntity($location);
    }
}
