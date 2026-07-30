<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RemoveCityFromZone;

use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;

final readonly class RemoveCityFromZoneHandler
{
    public function __construct(
        private ServiceZoneRepositoryInterface $serviceZoneRepository,
    ) {
    }

    public function __invoke(RemoveCityFromZoneCommand $command): void
    {
        $zone = $this->serviceZoneRepository->findById(
            ServiceZoneIdValueObject::fromString($command->serviceZoneId),
        );

        if ($zone === null) {
            throw new ServiceZoneException('Zona de servicio no encontrada.');
        }

        $zone->removeCity($command->cityId);

        $this->serviceZoneRepository->save($zone);
    }
}
