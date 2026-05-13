<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\AddCityToZone;

use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;

final readonly class AddCityToZoneHandler
{
    public function __construct(
        private ServiceZoneRepositoryInterface $serviceZoneRepository,
    ) {
    }

    public function __invoke(AddCityToZoneCommand $command): void
    {
        $zone = $this->serviceZoneRepository->findById(
            ServiceZoneIdValueObject::fromString($command->serviceZoneId),
        );

        if ($zone === null) {
            throw new ServiceZoneException('Zona de servicio no encontrada.');
        }

        $cityId = strtoupper(trim($command->cityId));

        // Validate exclusivity: city must not belong to another active zone
        foreach ($this->serviceZoneRepository->findAllActive() as $activeZone) {
            if ((string) $activeZone->id() === (string) $zone->id()) {
                continue;
            }

            if ($activeZone->hasCity($cityId)) {
                throw new ServiceZoneException(
                    sprintf(
                        'La ciudad ya está asignada a otra zona activa: %s.',
                        $activeZone->name(),
                    ),
                );
            }
        }

        $zone->addCity($cityId);

        $this->serviceZoneRepository->save($zone);
    }
}
