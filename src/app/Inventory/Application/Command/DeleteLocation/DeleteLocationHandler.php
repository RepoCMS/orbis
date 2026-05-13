<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DeleteLocation;

use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;

final readonly class DeleteLocationHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    public function __invoke(DeleteLocationCommand $command): void
    {
        $id       = new LocationIdValueObject($command->id);
        $location = $this->locationRepository->findById($id)
            ?? throw LocationNotFoundException::withId($command->id);

        $location->delete();

        $this->locationRepository->save($location);
    }
}
