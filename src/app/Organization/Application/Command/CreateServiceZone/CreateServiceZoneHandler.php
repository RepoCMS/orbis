<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateServiceZone;

use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class CreateServiceZoneHandler
{
    public function __construct(
        private ServiceZoneRepositoryInterface $repository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(CreateServiceZoneCommand $command): string
    {
        if ($this->repository->existsByName($command->name)) {
            throw new ServiceZoneException('Ya existe una zona de servicio con ese nombre.');
        }

        $id   = $this->idGenerator->generate();
        $zone = ServiceZone::create(
            id:   new ServiceZoneIdValueObject($id),
            name: $command->name,
        );

        $this->repository->save($zone);

        return $id;
    }
}
