<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class EmployeeUserLinked extends DomainEvent
{
    public function __construct(
        public readonly string $employeeId,
        public readonly string $userId,
    ) {
        parent::__construct();
    }
}
