<?php

declare(strict_types=1);

namespace App\Client\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class SlaPolicyCreated extends DomainEvent
{
    public function __construct(
        public readonly string $policyId,
        public readonly string $contractId,
    ) {
        parent::__construct();
    }
}
