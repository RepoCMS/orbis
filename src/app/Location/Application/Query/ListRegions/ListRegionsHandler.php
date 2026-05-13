<?php

declare(strict_types=1);

namespace App\Location\Application\Query\ListRegions;

use App\Location\Application\Query\Response\RegionResponse;
use App\Location\Domain\Repositories\RegionRepositoryInterface;

final readonly class ListRegionsHandler
{
    public function __construct(private RegionRepositoryInterface $regionRepository) {}

    /** @return RegionResponse[] */
    public function __invoke(ListRegionsQuery $query): array
    {
        return array_map(
            static fn ($region) => RegionResponse::fromEntity($region),
            $this->regionRepository->findAll(),
        );
    }
}
