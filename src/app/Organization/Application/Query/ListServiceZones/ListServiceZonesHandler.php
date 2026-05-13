<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListServiceZones;

use App\Organization\Application\Query\Response\ServiceZoneResponse;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;

final readonly class ListServiceZonesHandler
{
    public function __construct(
        private ServiceZoneRepositoryInterface $repository,
    ) {
    }

    /**
     * @return ServiceZoneResponse[]
     */
    public function __invoke(ListServiceZonesQuery $query): array
    {
        $zones = $query->onlyActive
            ? $this->repository->findAllActive()
            : $this->repository->findAll();

        return array_map(ServiceZoneResponse::fromEntity(...), $zones);
    }
}
