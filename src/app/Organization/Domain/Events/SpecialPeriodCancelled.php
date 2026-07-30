<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un período especial es cancelado manualmente.
 */
final class SpecialPeriodCancelled extends DomainEvent
{
    public function __construct(
        public readonly string $periodId,
        public readonly string $employeeId,
    ) {
        parent::__construct();
    }
}
