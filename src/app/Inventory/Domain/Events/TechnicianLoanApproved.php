<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un préstamo entre técnicos es aprobado.
 */
final class TechnicianLoanApproved extends DomainEvent
{
    public function __construct(
        public readonly string $loanId,
        public readonly string $serializedStockId,
        public readonly string $fromTechnicianId,
        public readonly string $toTechnicianId,
        public readonly string $approvedBy,
        public readonly string $movementId,
    ) {
        parent::__construct();
    }
}
