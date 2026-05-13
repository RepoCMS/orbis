<?php

declare(strict_types=1);

namespace App\Location\Application\Query\FindCityById;

use App\Location\Application\Query\Response\CityResponse;
use App\Location\Domain\Repositories\CityRepositoryInterface;
use App\Location\Domain\ValueObjects\CityIdValueObject;

final readonly class FindCityByIdHandler
{
    public function __construct(private CityRepositoryInterface $cityRepository) {}

    public function __invoke(FindCityByIdQuery $query): ?CityResponse
    {
        $city = $this->cityRepository->findById(CityIdValueObject::fromString($query->cityId));

        return $city !== null ? CityResponse::fromEntity($city) : null;
    }
}
