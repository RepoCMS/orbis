<?php

declare(strict_types=1);

namespace App\Location\Application\Query\ListCities;

use App\Location\Application\Query\Response\CityResponse;
use App\Location\Domain\Repositories\CityRepositoryInterface;

final readonly class ListCitiesHandler
{
    public function __construct(private CityRepositoryInterface $cityRepository)
    {
    }

    /** @return CityResponse[] */
    public function __invoke(ListCitiesQuery $query): array
    {
        return array_map(
            static fn ($city) => CityResponse::fromEntity($city),
            $this->cityRepository->findAll(),
        );
    }
}
