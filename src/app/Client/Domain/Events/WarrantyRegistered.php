<?php

declare(strict_types=1);

namespace App\Client\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class WarrantyRegistered extends DomainEvent
{
    public function __construct(
        public readonly string $warrantyId,
        public readonly string $terminalId,
        public readonly string $type,
        public readonly string $clientId,
    ) {
        parent::__construct();
    }
}
