<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeactivateServiceZone;

use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;

final readonly class DeactivateServiceZoneHandler
{
    public function __construct(
        private ServiceZoneRepositoryInterface $repository,
    ) {
    }

    public function __invoke(DeactivateServiceZoneCommand $command): void
    {
        $zone = $this->repository->findById(new ServiceZoneIdValueObject($command->serviceZoneId))
            ?? throw ServiceZoneNotFoundException::withId($command->serviceZoneId);

        $zone->deactivate();
        $this->repository->save($zone);
    }
}
