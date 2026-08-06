<?php

declare(strict_types=1);

namespace App\Location\Application\Query\SearchCities;

final readonly class SearchCitiesQuery
{
    public function __construct(
        public string $term,
        public int $limit = 20,
    ) {
    }
}
