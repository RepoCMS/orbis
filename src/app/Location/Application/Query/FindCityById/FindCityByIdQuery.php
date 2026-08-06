<?php

declare(strict_types=1);

namespace App\Location\Application\Query\FindCityById;

final readonly class FindCityByIdQuery
{
    public function __construct(
        public string $cityId,
    ) {
    }
}
