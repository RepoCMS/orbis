<?php

declare(strict_types=1);

namespace App\Location\Application\Query\SearchCities;

use App\Location\Application\Exception\InvalidLocationParameterException;
use App\Location\Application\Query\Response\CitySearchResponse;
use App\Location\Domain\Repositories\CityRepositoryInterface;
use App\Location\Domain\Repositories\ProvinceRepositoryInterface;
use App\Location\Domain\ValueObjects\ProvinceIdValueObject;

final readonly class SearchCitiesHandler
{
    public function __construct(
        private CityRepositoryInterface $cityRepository,
        private ProvinceRepositoryInterface $provinceRepository,
    ) {
    }

    /** @return CitySearchResponse[] */
    public function __invoke(SearchCitiesQuery $query): array
    {
        if (strlen($query->term) < 2) {
            throw InvalidLocationParameterException::shortSearchTerm(2);
        }

        $cities = $this->cityRepository->searchByName($query->term, $query->limit);

        return array_map(function ($city) {
            $response = CitySearchResponse::fromEntity($city);
            $province = $this->provinceRepository->findById(
                ProvinceIdValueObject::fromString((string) $city->provinceId()),
            );
            $response = new CitySearchResponse(
                id: $response->id,
                name: $response->name,
                provinceId: $response->provinceId,
                provinceName: $province?->name() ?? '',
            );

            return $response;
        }, $cities);
    }
}
