<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\TransferCity;

use App\Organization\Domain\Exceptions\ServiceZoneException;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;

final readonly class TransferCityHandler
{
    public function __construct(
        private ServiceZoneRepositoryInterface $serviceZoneRepository,
    ) {
    }

    public function __invoke(TransferCityCommand $command): void
    {
        $zoneId = ServiceZoneIdValueObject::fromString($command->serviceZoneId);
        $zone   = $this->serviceZoneRepository->findById($zoneId)
            ?? throw new ServiceZoneException('Zona de servicio origen no encontrada.');

        $targetZoneId = ServiceZoneIdValueObject::fromString($command->targetZoneId);
        $targetZone   = $this->serviceZoneRepository->findById($targetZoneId)
            ?? throw new ServiceZoneException('Zona de servicio destino no encontrada.');

        if ((string) $zoneId === (string) $targetZoneId) {
            throw new ServiceZoneException('La zona origen y destino no pueden ser la misma.');
        }

        if (!$zone->hasCity($command->cityId)) {
            throw new ServiceZoneException(
                sprintf('La ciudad "%s" no pertenece a la zona origen.', $command->cityId)
            );
        }

        // Remove from source, add to target
        $zone->removeCity($command->cityId);
        $this->serviceZoneRepository->save($zone);

        $targetZone->addCity($command->cityId);
        $this->serviceZoneRepository->save($targetZone);
    }
}
