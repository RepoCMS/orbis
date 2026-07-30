<?php

declare(strict_types=1);

namespace App\Client\Application\Command\RegisterWarranty;

final readonly class RegisterWarrantyCommand
{
    public function __construct(
        public string $terminalId,
        public string $type,
        public string $startDate,
        public string $endDate,
        public ?string $terms = null,
    ) {
    }
}
