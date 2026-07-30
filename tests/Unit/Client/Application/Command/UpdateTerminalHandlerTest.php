<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\UpdateTerminal\UpdateTerminalCommand;
use App\Client\Application\Command\UpdateTerminal\UpdateTerminalHandler;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Events\TerminalUpdated;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class UpdateTerminalHandlerTest extends TestCase
{
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testUpdatesTerminalSuccessfully(): void
    {
        $terminal = Terminal::add(
            id: new TerminalIdValueObject(self::TERMINAL_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            serial: new SerialNumberValueObject('ORIGINAL01'),
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

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(TerminalUpdated::class));

        $handler = new UpdateTerminalHandler($repo, $eventPublisher);
        ($handler)(new UpdateTerminalCommand(
            terminalId: self::TERMINAL_ID,
            serial: 'UPDATED001',
            vendor: 'diebold',
            funcionality: 'recycler',
            type: 'ttw',
            atmModelCode: null,
        ));

        self::assertNotNull($saved);
    }

    public function testThrowsWhenTerminalNotFound(): void
    {
        $repo = $this->createMock(TerminalRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateTerminalHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(TerminalNotFoundException::class);
        ($handler)(new UpdateTerminalCommand(
            terminalId: self::TERMINAL_ID,
            serial: 'UPDATED001',
            vendor: 'diebold',
            funcionality: 'recycler',
            type: 'ttw',
        ));
    }
}
