<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListSubLocationsByLocation;

use App\Inventory\Application\Query\Response\SubLocationResponse;
use App\Inventory\Domain\Repositories\SubLocationRepositoryInterface;

final readonly class ListSubLocationsByLocationHandler
{
    public function __construct(
        private SubLocationRepositoryInterface $subLocationRepository,
    ) {
    }

    /** @return SubLocationResponse[] */
    public function __invoke(ListSubLocationsByLocationQuery $query): array
    {
        $roots = $this->subLocationRepository->findRootsByLocationId($query->locationId);

        return SubLocationResponse::fromTree($roots);
    }
}
