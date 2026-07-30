<?php

declare(strict_types=1);

namespace App\Support\Application\Service;

final readonly class SparePartScope
{
    public function __construct(
        public ?string $employeeId = null,
        public ?string $locationId = null,
    ) {}
}
