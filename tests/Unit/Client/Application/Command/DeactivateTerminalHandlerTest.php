<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\DeactivateTerminal\DeactivateTerminalCommand;
use App\Client\Application\Command\DeactivateTerminal\DeactivateTerminalHandler;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Events\TerminalDeactivated;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class DeactivateTerminalHandlerTest extends TestCase
{
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    private function makeTerminal(TerminalStatus $status = TerminalStatus::ACTIVE): Terminal
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

    public function testDeactivatesTerminalSuccessfully(): void
    {
        $terminal = $this->makeTerminal(TerminalStatus::ACTIVE);
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
            ->with($this->isInstanceOf(TerminalDeactivated::class));

        $handler = new DeactivateTerminalHandler($terminalRepo, $eventPublisher);
        ($handler)(new DeactivateTerminalCommand(terminalId: self::TERMINAL_ID));

        self::assertFalse($saved->isActive());
    }

    public function testThrowsWhenTerminalNotFound(): void
    {
        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn(null);

        $handler = new DeactivateTerminalHandler(
            $terminalRepo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(TerminalNotFoundException::class);
        ($handler)(new DeactivateTerminalCommand(terminalId: self::TERMINAL_ID));
    }

    public function testDeactivatesAlreadyInactiveTerminalDoesNotThrow(): void
    {
        $terminal = $this->makeTerminal(TerminalStatus::INACTIVE);

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn($terminal);
        $terminalRepo->expects($this->once())->method('save');

        $handler = new DeactivateTerminalHandler(
            $terminalRepo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        ($handler)(new DeactivateTerminalCommand(terminalId: self::TERMINAL_ID));
        self::assertFalse($terminal->isActive());
    }
}
