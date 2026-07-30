<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando una solicitud de stock es aprobada.
 */
final class RequestApproved extends DomainEvent
{
    public function __construct(
        public readonly string $requestId,
    ) {
        parent::__construct();
    }
}
