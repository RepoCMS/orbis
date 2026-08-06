<?php

declare(strict_types=1);

namespace App\Client\Application\Query\GetClientDashboardSummary;

/**
 * DTO con métricas del dashboard de Clientes.
 */
final readonly class ClientDashboardSummary
{
    /**
     * @param array<array{status: string, label: string, count: int, color: string}> $terminalsByStatus
     * @param array<array{id: string, name: string, ruc: string, status: string}>     $recentClients
     * @param array<array{status: string, label: string, count: int, color: string}> $clientsByStatus
     * @param array<array{label: string, range: string, count: int}>                  $contractsExpiring
     * @param array<array{clientId: string, clientName: string, terminalCount: int}>  $topClientsByTerminals
     * @param array<array{status: string, label: string, count: int}>                 $contactsByStatus
     * @param array<array{month: string, year: int, count: int}>                       $monthlyGrowth
     */
    public function __construct(
        public int $totalClients,
        public int $activeContacts,
        public int $totalTerminals,
        public array $terminalsByStatus,
        public array $recentClients,
        public array $clientsByStatus = [],
        public array $contractsExpiring = [],
        public array $topClientsByTerminals = [],
        public array $contactsByStatus = [],
        public int $unassignedTerminals = 0,
        public array $monthlyGrowth = [],
    ) {
    }
}
