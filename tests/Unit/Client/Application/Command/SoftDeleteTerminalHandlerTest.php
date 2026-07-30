<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\SoftDeleteTerminal\SoftDeleteTerminalCommand;
use App\Client\Application\Command\SoftDeleteTerminal\SoftDeleteTerminalHandler;
use App\Client\Application\Exception\TerminalNotFoundException;
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

final class SoftDeleteTerminalHandlerTest extends TestCase
{
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testSoftDeletesTerminalSuccessfully(): void
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

        $repo = $this->createMock(TerminalRepositoryInterface::class);
        $repo->method('findById')->willReturn($terminal);
        $saved = null;
        $repo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($t) use (&$saved): void {
                $saved = $t;
            });

        $handler = new SoftDeleteTerminalHandler($repo);
        ($handler)(new SoftDeleteTerminalCommand(terminalId: self::TERMINAL_ID));

        self::assertNotNull($saved->deletedAt());
    }

    public function testThrowsWhenTerminalNotFound(): void
    {
        $repo = $this->createMock(TerminalRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new SoftDeleteTerminalHandler($repo);

        $this->expectException(TerminalNotFoundException::class);
        ($handler)(new SoftDeleteTerminalCommand(terminalId: self::TERMINAL_ID));
    }
}
