<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un supervisor rechaza una solicitud de vacaciones.
 */
final class VacationRejected extends DomainEvent
{
    public function __construct(
        public readonly string $employeeId,
        public readonly string $requestId,
        public readonly string $reason,
    ) {
        parent::__construct();
    }
}
