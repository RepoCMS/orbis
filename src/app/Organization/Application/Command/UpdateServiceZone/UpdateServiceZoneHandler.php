<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateServiceZone;

use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;

final readonly class UpdateServiceZoneHandler
{
    public function __construct(
        private ServiceZoneRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateServiceZoneCommand $command): void
    {
        $id   = new ServiceZoneIdValueObject($command->serviceZoneId);
        $zone = $this->repository->findById($id)
            ?? throw ServiceZoneNotFoundException::withId($command->serviceZoneId);

        if ($this->repository->existsByName($command->name, $id)) {
            throw new ServiceZoneException('Ya existe una zona de servicio con ese nombre.');
        }

        $zone->changeName($command->name);
        $this->repository->save($zone);
    }
}
