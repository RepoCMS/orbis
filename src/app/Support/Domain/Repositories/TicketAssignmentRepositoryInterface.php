<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

interface TicketAssignmentRepositoryInterface
{
    public function findById(TicketAssignmentIdValueObject $id): ?TicketAssignment;

    /** @return TicketAssignment[] */
    public function findByTicket(TicketIdValueObject $ticketId): array;

    /** @return TicketAssignment[] */
    public function findByEmployee(string $employeeId): array;

    public function save(TicketAssignment $assignment): void;
}
