<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Application\Query\GetCatCodeComponents\GetCatCodeComponentsHandler;
use App\Support\Application\Query\GetCatCodeComponents\GetCatCodeComponentsQuery;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class GetCatCodeComponentsHandlerTest extends TestCase
{
    private const TICKET_ID = '01HZ0000000000000000000001';

    public function testReturnsCatCodeComponents(): void
    {
        $dbal = $this->createMock(Connection::class);
        $dbal->method('fetchAssociative')->willReturn([
            'client_code'   => 'TES',
            'city_code'     => 'PTY',
            'terminal_code' => 'TERM001',
        ]);

        $handler = new GetCatCodeComponentsHandler($dbal);
        $result = ($handler)(new GetCatCodeComponentsQuery(ticketId: self::TICKET_ID));

        self::assertSame('TES', $result['clientCode']);
        self::assertSame('PTY', $result['cityCode']);
        self::assertSame('TERM001', $result['terminalCode']);
    }

    public function testReturnsCatCodeComponentsWithAssignedByClientId(): void
    {
        $dbal = $this->createMock(Connection::class);
        $dbal->method('fetchAssociative')->willReturn([
            'client_code'   => 'ABC',
            'city_code'     => 'XYZ',
            'terminal_code' => 'CUST-001',
        ]);

        $handler = new GetCatCodeComponentsHandler($dbal);
        $result = ($handler)(new GetCatCodeComponentsQuery(ticketId: self::TICKET_ID));

        self::assertSame('CUST-001', $result['terminalCode']);
    }

    public function testThrowsExceptionWhenTicketNotFound(): void
    {
        $dbal = $this->createMock(Connection::class);
        $dbal->method('fetchAssociative')->willReturn(false);

        $handler = new GetCatCodeComponentsHandler($dbal);

        $this->expectException(TicketNotFoundException::class);

        ($handler)(new GetCatCodeComponentsQuery(ticketId: self::TICKET_ID));
    }
}
