<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateTechnicianLoan;

final readonly class CreateTechnicianLoanCommand
{
    public function __construct(
        public string $serializedStockId,
        public string $fromTechnicianId,
        public string $toTechnicianId,
        public ?string $ticketId = null,
        public ?string $notes = null,
    ) {
    }
}
