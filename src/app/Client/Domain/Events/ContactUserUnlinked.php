<?php

declare(strict_types=1);

namespace App\Client\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class ContactUserUnlinked extends DomainEvent
{
    public function __construct(
        public readonly string $contactId,
        public readonly string $userId,
    ) {
        parent::__construct();
    }
}
