<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateLocation;

use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class CreateLocationHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(CreateLocationCommand $command): void
    {
        $location = Location::create(
            id:      new LocationIdValueObject($this->idGenerator->generate()),
            name:    new LocationNameValueObject($command->name),
            city:    $command->city,
            address: $command->address,
            type:    LocationType::from($command->type),
        );

        $this->locationRepository->save($location);
    }
}
