<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\UpdateLocation;

use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;

final readonly class UpdateLocationHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    public function __invoke(UpdateLocationCommand $command): void
    {
        $id       = new LocationIdValueObject($command->id);
        $location = $this->locationRepository->findById($id)
            ?? throw LocationNotFoundException::withId($command->id);

        $location->update($command->name, $command->city, $command->address);

        $this->locationRepository->save($location);
    }
}
