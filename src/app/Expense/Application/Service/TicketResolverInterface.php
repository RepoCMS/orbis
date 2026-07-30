<?php

declare(strict_types=1);

namespace App\Expense\Application\Service;

use App\Expense\Application\Exception\TicketNotFoundException;

interface TicketResolverInterface
{
    /**
     * Validates that a ticket belongs to the given employee.
     *
     * @throws TicketNotFoundException
     */
    public function belongsToEmployee(string $ticketId, string $employeeId): bool;
}
