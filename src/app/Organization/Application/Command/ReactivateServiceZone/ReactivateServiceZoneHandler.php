<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ReactivateServiceZone;

use App\Organization\Application\Exception\ServiceZoneNotFoundException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;

final readonly class ReactivateServiceZoneHandler
{
    public function __construct(
        private ServiceZoneRepositoryInterface $repository,
    ) {
    }

    public function __invoke(ReactivateServiceZoneCommand $command): void
    {
        $zone = $this->repository->findById(new ServiceZoneIdValueObject($command->serviceZoneId))
            ?? throw ServiceZoneNotFoundException::withId($command->serviceZoneId);

        $zone->reactivate();
        $this->repository->save($zone);
    }
}
