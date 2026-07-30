<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateWarranty;

final readonly class UpdateWarrantyCommand
{
    public function __construct(
        public string $warrantyId,
        public ?string $type = null,
        public ?string $endDate = null,
        public ?string $terms = null,
    ) {
    }
}
