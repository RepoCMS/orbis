<?php

declare(strict_types=1);

namespace App\Client\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class ClientSaleOrderCreated extends DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $clientId,
    ) {
        parent::__construct();
    }
}
