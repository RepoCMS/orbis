<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un préstamo entre técnicos es rechazado.
 */
final class TechnicianLoanRejected extends DomainEvent
{
    public function __construct(
        public readonly string $loanId,
        public readonly string $reason,
        public readonly ?string $rejectedBy = null,
    ) {
        parent::__construct();
    }
}
