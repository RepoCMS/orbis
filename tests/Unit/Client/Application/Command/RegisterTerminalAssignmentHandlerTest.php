<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\RegisterTerminalAssignment\RegisterTerminalAssignmentCommand;
use App\Client\Application\Command\RegisterTerminalAssignment\RegisterTerminalAssignmentHandler;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RegisterTerminalAssignmentHandlerTest extends TestCase
{
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
    private const SITE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAX';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAY';

    private function makeTerminal(): Terminal
    {
        return Terminal::add(
            id: new TerminalIdValueObject(self::TERMINAL_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            serial: new SerialNumberValueObject('ATM0000001'),
            vendor: TerminalVendor::Hyosung,
            funcionality: TerminalFunctionality::Dispenser,
            type: TerminalType::Lobby,
            status: TerminalStatus::ACTIVE,
        );
    }

    public function testRegistersAssignmentSuccessfully(): void
    {
        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn($this->makeTerminal());

        $assignmentRepo = $this->createMock(TerminalAssignmentRepositoryInterface::class);
        $assignmentRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish');

        $handler = new RegisterTerminalAssignmentHandler(
            $terminalRepo, $assignmentRepo, $idGenerator, $eventPublisher,
        );
        $result = ($handler)(new RegisterTerminalAssignmentCommand(
            terminalId: self::TERMINAL_ID,
            locationId: self::SITE_ID,
            name: 'Lobby ATM',
            idAssignedByClient: 'ATM001',
        ));

        self::assertSame(self::GENERATED_ID, $result);
    }

    public function testThrowsWhenTerminalNotFound(): void
    {
        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn(null);

        $handler = new RegisterTerminalAssignmentHandler(
            $terminalRepo,
            $this->createMock(TerminalAssignmentRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(TerminalNotFoundException::class);
        ($handler)(new RegisterTerminalAssignmentCommand(
            terminalId: self::TERMINAL_ID,
            locationId: self::SITE_ID,
            name: 'Lobby ATM',
            idAssignedByClient: 'ATM001',
        ));
    }
}
