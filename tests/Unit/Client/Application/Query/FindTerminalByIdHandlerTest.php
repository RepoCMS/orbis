<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalByIdHandler;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalByIdQuery;
use App\Client\Application\Query\Response\TerminalResponse;
use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindTerminalByIdHandlerTest extends TestCase
{
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testReturnsTerminalResponse(): void
    {
        $terminal = Terminal::add(
            id: new TerminalIdValueObject(self::TERMINAL_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            serial: new SerialNumberValueObject('ATM0000001'),
            vendor: TerminalVendor::Hyosung,
            funcionality: TerminalFunctionality::Dispenser,
            type: TerminalType::Lobby,
            status: TerminalStatus::ACTIVE,
        );

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn($terminal);

        $handler = new FindTerminalByIdHandler($terminalRepo);
        $response = ($handler)(new FindTerminalByIdQuery(terminalId: self::TERMINAL_ID));

        self::assertInstanceOf(TerminalResponse::class, $response);
        self::assertSame(self::TERMINAL_ID, $response->id);
    }

    public function testThrowsWhenTerminalNotFound(): void
    {
        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn(null);

        $handler = new FindTerminalByIdHandler($terminalRepo);

        $this->expectException(TerminalNotFoundException::class);
        $this->expectExceptionMessage('Terminal con ID "' . self::TERMINAL_ID . '" no encontrado.');

        ($handler)(new FindTerminalByIdQuery(terminalId: self::TERMINAL_ID));
    }
}
