<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Query\FindTerminalsByClient\FindTerminalsByClientHandler;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalsByClientQuery;
use App\Client\Application\Query\Response\TerminalResponse;
use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindTerminalsByClientHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testReturnsTerminalResponses(): void
    {
        $terminal = Terminal::add(
            id: new TerminalIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAW'),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            serial: new SerialNumberValueObject('ATM0000001'),
            vendor: TerminalVendor::Hyosung,
            funcionality: TerminalFunctionality::Dispenser,
            type: TerminalType::Lobby,
            status: TerminalStatus::ACTIVE,
        );

        $repo = $this->createMock(TerminalRepositoryInterface::class);
        $repo->method('findByClientId')->willReturn([$terminal]);

        $handler = new FindTerminalsByClientHandler($repo);
        $result = ($handler)(new FindTerminalsByClientQuery(clientId: self::CLIENT_ID));

        self::assertCount(1, $result);
        self::assertInstanceOf(TerminalResponse::class, $result[0]);
    }

    public function testReturnsEmptyArray(): void
    {
        $repo = $this->createMock(TerminalRepositoryInterface::class);
        $repo->method('findByClientId')->willReturn([]);

        $handler = new FindTerminalsByClientHandler($repo);
        $result = ($handler)(new FindTerminalsByClientQuery(clientId: self::CLIENT_ID));

        self::assertCount(0, $result);
    }
}
