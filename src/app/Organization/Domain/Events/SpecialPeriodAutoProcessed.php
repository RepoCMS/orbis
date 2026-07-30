<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando el proceso automático de special periods
 * procesa lotes (begin/complete/cancel).
 */
final class SpecialPeriodAutoProcessed extends DomainEvent
{
    public function __construct(
        public readonly int $count,
    ) {
        parent::__construct();
    }
}
