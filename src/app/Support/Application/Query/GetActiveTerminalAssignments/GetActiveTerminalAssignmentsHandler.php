<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetActiveTerminalAssignments;

use Doctrine\DBAL\Connection;

final readonly class GetActiveTerminalAssignmentsHandler
{
    public function __construct(
        private Connection $dbal,
    ) {}

    /**
     * Returns active terminal assignments for a given client.
     *
     * @return array<int, array{id: string, assignment_name: string, id_assigned_by_client: ?string}>
     */
    public function __invoke(GetActiveTerminalAssignmentsQuery $query): array
    {
        return $this->dbal->fetchAllAssociative(
            "SELECT ta.id, ta.name AS assignment_name, ta.id_assigned_by_client
             FROM terminal_assignments ta
             INNER JOIN terminals t ON t.id = ta.terminal_id AND t.deleted_at IS NULL AND t.client_id = :clientId
             WHERE ta.status = 'active' AND ta.deleted_at IS NULL
             ORDER BY ta.name",
            ['clientId' => $query->clientId],
        );
    }
}
