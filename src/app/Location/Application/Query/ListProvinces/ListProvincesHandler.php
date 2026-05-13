<?php

declare(strict_types=1);

namespace App\Location\Application\Query\ListProvinces;

use App\Location\Application\Query\Response\ProvinceResponse;
use App\Location\Domain\Repositories\ProvinceRepositoryInterface;
use App\Location\Domain\ValueObjects\RegionIdValueObject;

final readonly class ListProvincesHandler
{
    public function __construct(private ProvinceRepositoryInterface $provinceRepository) {}

    /** @return ProvinceResponse[] */
    public function __invoke(ListProvincesQuery $query): array
    {
        $provinces = $query->regionId !== null
            ? $this->provinceRepository->findByRegion(RegionIdValueObject::fromString($query->regionId))
            : $this->provinceRepository->findAll();

        return array_map(
            static fn ($province) => ProvinceResponse::fromEntity($province),
            $provinces,
        );
    }
}
