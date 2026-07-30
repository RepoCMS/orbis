<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\GetActiveTerminalAssignments\GetActiveTerminalAssignmentsHandler;
use App\Support\Application\Query\GetActiveTerminalAssignments\GetActiveTerminalAssignmentsQuery;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class GetActiveTerminalAssignmentsHandlerTest extends TestCase
{
    private const CLIENT_ID = '01HZ0000000000000000000001';

    public function testReturnsActiveAssignments(): void
    {
        $dbal = $this->createMock(Connection::class);
        $dbal->method('fetchAllAssociative')->willReturn([
            ['id' => '01HZ000000000000000000000A', 'assignment_name' => 'Cajero Principal', 'id_assigned_by_client' => 'ATM-001'],
            ['id' => '01HZ000000000000000000000B', 'assignment_name' => 'Cajero Secundario', 'id_assigned_by_client' => null],
        ]);

        $handler = new GetActiveTerminalAssignmentsHandler($dbal);
        $result = ($handler)(new GetActiveTerminalAssignmentsQuery(clientId: self::CLIENT_ID));

        self::assertCount(2, $result);
        self::assertSame('01HZ000000000000000000000A', $result[0]['id']);
        self::assertSame('Cajero Principal', $result[0]['assignment_name']);
        self::assertSame('ATM-001', $result[0]['id_assigned_by_client']);
        self::assertNull($result[1]['id_assigned_by_client']);
    }

    public function testReturnsEmptyArrayWhenNoAssignments(): void
    {
        $dbal = $this->createMock(Connection::class);
        $dbal->method('fetchAllAssociative')->willReturn([]);

        $handler = new GetActiveTerminalAssignmentsHandler($dbal);
        $result = ($handler)(new GetActiveTerminalAssignmentsQuery(clientId: self::CLIENT_ID));

        self::assertCount(0, $result);
    }

    public function testFiltersByClientId(): void
    {
        $dbal = $this->createMock(Connection::class);
        $dbal->expects(self::once())->method('fetchAllAssociative')
            ->with(
                self::stringContains('client_id = :clientId'),
                self::arrayHasKey('clientId'),
            )
            ->willReturn([]);

        $handler = new GetActiveTerminalAssignmentsHandler($dbal);
        $result = ($handler)(new GetActiveTerminalAssignmentsQuery(clientId: self::CLIENT_ID));

        self::assertCount(0, $result);
    }

    public function testOrdersByName(): void
    {
        $dbal = $this->createMock(Connection::class);
        $dbal->expects(self::once())->method('fetchAllAssociative')
            ->with(self::stringContains('ORDER BY ta.name'))
            ->willReturn([]);

        $handler = new GetActiveTerminalAssignmentsHandler($dbal);
        ($handler)(new GetActiveTerminalAssignmentsQuery(clientId: self::CLIENT_ID));
    }
}
