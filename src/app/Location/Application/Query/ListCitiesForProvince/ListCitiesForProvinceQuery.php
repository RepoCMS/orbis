<?php

declare(strict_types=1);

namespace App\Location\Application\Query\ListCitiesForProvince;

final readonly class ListCitiesForProvinceQuery
{
    public function __construct(
        public string $provinceId,
    ) {}
}
