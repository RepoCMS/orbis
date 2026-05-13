<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindSiteById;

final readonly class FindSiteByIdQuery
{
    public function __construct(
        public string $siteId,
    ) {
    }
}
