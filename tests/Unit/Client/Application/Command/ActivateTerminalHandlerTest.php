<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\ActivateTerminal\ActivateTerminalCommand;
use App\Client\Application\Command\ActivateTerminal\ActivateTerminalHandler;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Events\TerminalReactivated;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class ActivateTerminalHandlerTest extends TestCase
{
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    private function makeTerminal(TerminalStatus $status = TerminalStatus::INACTIVE): Terminal
    {
        return Terminal::add(
            id: new TerminalIdValueObject(self::TERMINAL_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            serial: new SerialNumberValueObject('ATM0000001'),
            vendor: TerminalVendor::Hyosung,
            funcionality: TerminalFunctionality::Dispenser,
            type: TerminalType::Lobby,
            status: $status,
        );
    }

    public function testActivatesTerminalSuccessfully(): void
    {
        $terminal = $this->makeTerminal(TerminalStatus::INACTIVE);
        $saved = null;

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn($terminal);
        $terminalRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($t) use (&$saved): void {
                $saved = $t;
            });

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(TerminalReactivated::class));

        $handler = new ActivateTerminalHandler($terminalRepo, $eventPublisher);
        ($handler)(new ActivateTerminalCommand(terminalId: self::TERMINAL_ID));

        self::assertTrue($saved->isActive());
    }

    public function testThrowsWhenTerminalNotFound(): void
    {
        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn(null);

        $handler = new ActivateTerminalHandler(
            $terminalRepo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(TerminalNotFoundException::class);
        ($handler)(new ActivateTerminalCommand(terminalId: self::TERMINAL_ID));
    }

    public function testActivatesAlreadyActiveTerminalDoesNotThrow(): void
    {
        $terminal = $this->makeTerminal(TerminalStatus::ACTIVE);

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn($terminal);
        $terminalRepo->expects($this->once())->method('save');

        $handler = new ActivateTerminalHandler(
            $terminalRepo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        ($handler)(new ActivateTerminalCommand(terminalId: self::TERMINAL_ID));
        self::assertTrue($terminal->isActive());
    }
}
