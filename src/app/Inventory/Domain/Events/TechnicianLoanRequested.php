<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando se solicita un préstamo entre técnicos.
 */
final class TechnicianLoanRequested extends DomainEvent
{
    public function __construct(
        public readonly string $loanId,
        public readonly string $serializedStockId,
        public readonly string $fromTechnicianId,
        public readonly string $toTechnicianId,
        public readonly ?string $ticketId = null,
    ) {
        parent::__construct();
    }
}
