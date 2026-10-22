<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un período especial es editado (cambio de fechas, notas, etc.).
 */
final class SpecialPeriodEdited extends DomainEvent
{
    public function __construct(
        public readonly string $periodId,
        public readonly ?string $userId = null,
    ) {
        parent::__construct();
    }
}
