<?php

declare(strict_types=1);

namespace App\Client\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class ClientDeleted extends DomainEvent
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $reason = '',
    ) {
        parent::__construct();
    }
}
