<?php

declare(strict_types=1);

namespace App\Client\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class TerminalAssignmentUpdated extends DomainEvent
{
    public function __construct(
        public readonly string $assignmentId,
        public readonly string $terminalId,
        public readonly string $locationId,
        public readonly string $name,
        public readonly string $idAssignedByClient,
    ) {
        parent::__construct();
    }
}
