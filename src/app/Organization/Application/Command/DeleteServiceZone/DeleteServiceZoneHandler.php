<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeleteServiceZone;

use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;

final readonly class DeleteServiceZoneHandler
{
    public function __construct(
        private ServiceZoneRepositoryInterface $repository,
    ) {
    }

    public function __invoke(DeleteServiceZoneCommand $command): void
    {
        $id = new ServiceZoneIdValueObject($command->serviceZoneId);
        $zone = $this->repository->findById($id)
            ?? throw ServiceZoneNotFoundException::withId($command->serviceZoneId);

        $zone->softDelete();
        $this->repository->save($zone);
    }
}
