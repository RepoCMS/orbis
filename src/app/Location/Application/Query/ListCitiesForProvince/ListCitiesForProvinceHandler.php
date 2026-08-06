<?php

declare(strict_types=1);

namespace App\Location\Application\Query\ListCitiesForProvince;

use App\Location\Application\Query\Response\CityResponse;
use App\Location\Domain\Repositories\CityRepositoryInterface;
use App\Location\Domain\ValueObjects\ProvinceIdValueObject;

final readonly class ListCitiesForProvinceHandler
{
    public function __construct(private CityRepositoryInterface $cityRepository)
    {
    }

    /** @return CityResponse[] */
    public function __invoke(ListCitiesForProvinceQuery $query): array
    {
        return array_map(
            static fn ($city) => CityResponse::fromEntity($city),
            $this->cityRepository->findByProvince(ProvinceIdValueObject::fromString($query->provinceId)),
        );
    }
}
