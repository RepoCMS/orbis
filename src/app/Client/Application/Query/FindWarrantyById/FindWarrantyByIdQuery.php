<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindWarrantyById;

final readonly class FindWarrantyByIdQuery
{
    public function __construct(
        public string $warrantyId,
    ) {
    }
}
