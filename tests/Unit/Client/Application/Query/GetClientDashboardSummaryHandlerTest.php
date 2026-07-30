<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Query\GetClientDashboardSummary\ClientDashboardSummary;
use App\Client\Application\Query\GetClientDashboardSummary\GetClientDashboardSummaryHandler;
use App\Client\Application\Query\GetClientDashboardSummary\GetClientDashboardSummaryQuery;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class GetClientDashboardSummaryHandlerTest extends TestCase
{
    private const TOTAL_CLIENTS = 10;
    private const ACTIVE_CONTACTS = 25;
    private const TOTAL_TERMINALS = 50;

    public function testReturnsDashboardSummarySuccessfully(): void
    {
        $connection = $this->createMock(Connection::class);

        // fetchOne calls: totalClients, activeContacts, totalTerminals, unassignedTerminals
        $connection->expects($this->exactly(4))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(
                (string) self::TOTAL_CLIENTS,
                (string) self::ACTIVE_CONTACTS,
                (string) self::TOTAL_TERMINALS,
                '5', // unassignedTerminals
            );

        $matcher = $this->exactly(7);
        $connection->expects($matcher)
            ->method('fetchAllAssociative')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [ // terminalsByStatus
                        ['status' => 'active', 'total' => '30'],
                        ['status' => 'inactive', 'total' => '20'],
                    ],
                    2 => [ // recentClients
                        ['id' => '01H1', 'name' => 'Client A', 'ruc' => '1712345675001', 'status' => 'active'],
                        ['id' => '01H2', 'name' => 'Client B', 'ruc' => '1712345675002', 'status' => 'active'],
                        ['id' => '01H3', 'name' => 'Client C', 'ruc' => '1712345675003', 'status' => 'active'],
                        ['id' => '01H4', 'name' => 'Client D', 'ruc' => '1712345675004', 'status' => 'inactive'],
                        ['id' => '01H5', 'name' => 'Client E', 'ruc' => '1712345675005', 'status' => 'active'],
                    ],
                    3 => [ // clientsByStatus
                        ['status' => 'active', 'total' => '8'],
                        ['status' => 'inactive', 'total' => '2'],
                    ],
                    4 => [ // contractsExpiring
                        ['range' => '30', 'total' => '3'],
                        ['range' => '60', 'total' => '5'],
                        ['range' => '90', 'total' => '7'],
                    ],
                    5 => [ // topClientsByTerminals
                        ['clientId' => '01H1', 'clientName' => 'Client A', 'terminalCount' => '15'],
                        ['clientId' => '01H2', 'clientName' => 'Client B', 'terminalCount' => '10'],
                    ],
                    6 => [ // contactsByStatus
                        ['status' => 'active', 'total' => '25'],
                        ['status' => 'inactive', 'total' => '5'],
                    ],
                    7 => [ // monthlyGrowth
                        ['month' => '2025-01', 'year' => '2025', 'count' => '3'],
                        ['month' => '2025-02', 'year' => '2025', 'count' => '5'],
                    ],
                };
            });

        $handler = new GetClientDashboardSummaryHandler($connection);
        $summary = ($handler)(new GetClientDashboardSummaryQuery());

        self::assertInstanceOf(ClientDashboardSummary::class, $summary);
        self::assertSame(self::TOTAL_CLIENTS, $summary->totalClients);
        self::assertSame(self::ACTIVE_CONTACTS, $summary->activeContacts);
        self::assertSame(self::TOTAL_TERMINALS, $summary->totalTerminals);
        self::assertCount(2, $summary->terminalsByStatus);
        self::assertCount(5, $summary->recentClients);
        // New KPIs
        self::assertCount(2, $summary->clientsByStatus);
        self::assertCount(3, $summary->contractsExpiring);
        self::assertCount(2, $summary->topClientsByTerminals);
        self::assertCount(2, $summary->contactsByStatus);
        self::assertSame(5, $summary->unassignedTerminals);
        self::assertCount(2, $summary->monthlyGrowth);
    }

    public function testReturnsSummaryWithEmptyData(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection->expects($this->exactly(4))
            ->method('fetchOne')
            ->willReturn('0');

        $connection->expects($this->exactly(7))
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $handler = new GetClientDashboardSummaryHandler($connection);
        $summary = ($handler)(new GetClientDashboardSummaryQuery());

        self::assertSame(0, $summary->totalClients);
        self::assertSame(0, $summary->activeContacts);
        self::assertSame(0, $summary->totalTerminals);
        self::assertCount(0, $summary->terminalsByStatus);
        self::assertCount(0, $summary->recentClients);
        self::assertCount(0, $summary->clientsByStatus);
        self::assertCount(0, $summary->contractsExpiring);
        self::assertCount(0, $summary->topClientsByTerminals);
        self::assertCount(0, $summary->contactsByStatus);
        self::assertSame(0, $summary->unassignedTerminals);
        self::assertCount(0, $summary->monthlyGrowth);
    }
}
