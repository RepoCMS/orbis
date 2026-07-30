<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Service;

use App\Expense\Application\Exception\TicketNotFoundException;
use App\Expense\Application\Service\TicketResolverInterface;
use Doctrine\DBAL\Connection;

/**
 * Resuelve un Ticket desde Support BC vía DBAL directo.
 * Verifica que el ticket exista (no eliminado) y esté asignado al empleado.
 */
final readonly class CrossBcTicketResolver implements TicketResolverInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function belongsToEmployee(string $ticketId, string $employeeId): bool
    {
        $ticket = $this->connection->fetchAssociative(
            'SELECT id FROM tickets WHERE id = ? AND deleted_at IS NULL',
            [$ticketId],
        );

        if ($ticket === false) {
            throw new TicketNotFoundException($ticketId);
        }

        $assignment = $this->connection->fetchAssociative(
            'SELECT id FROM ticket_assignments WHERE ticket_id = ? AND employee_id = ? AND is_in_charge = 1',
            [$ticketId, $employeeId],
        );

        return $assignment !== false;
    }
}
