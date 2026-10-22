<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\UpdateTerminalAssignment\UpdateTerminalAssignmentCommand;
use App\Client\Application\Command\UpdateTerminalAssignment\UpdateTerminalAssignmentHandler;
use App\Client\Application\Exception\TerminalAssignmentNotFoundException;
use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Entities\TerminalAssignment;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Events\TerminalAssignmentUpdated;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdAssignedByClientValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalNameValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class UpdateTerminalAssignmentHandlerTest extends TestCase
{
    private const ASSIGNMENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testUpdatesAssignmentSuccessfully(): void
    {
        $assignment = TerminalAssignment::add(
            id: new TerminalAssignmentIdValueObject(self::ASSIGNMENT_ID),
            terminalId: new TerminalIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAW'),
            name: new TerminalNameValueObject('Original Name'),
            idAssingedByClient: new TerminalIdAssignedByClientValueObject('ATM001'),
            siteId: new SiteIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAX'),
        );

        $repo = $this->createMock(TerminalAssignmentRepositoryInterface::class);
        $repo->method('findById')->willReturn($assignment);
        $repo->expects($this->once())->method('save');

        $terminal = Terminal::add(
            id: new TerminalIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAW'),
            clientId: new ClientIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAY'),
            serial: new SerialNumberValueObject('ATM0000001'),
            vendor: TerminalVendor::Hyosung,
            funcionality: TerminalFunctionality::Dispenser,
            type: TerminalType::Lobby,
            status: TerminalStatus::ACTIVE,
        );

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn($terminal);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(TerminalAssignmentUpdated::class));

        $handler = new UpdateTerminalAssignmentHandler($repo, $terminalRepo, $eventPublisher);
        ($handler)(new UpdateTerminalAssignmentCommand(
            id: self::ASSIGNMENT_ID,
            name: 'Updated Name',
            idAssignedByClient: 'ATM002',
            locationId: '01ARZ3NDEKTSV4RRFFQ69G5FAY',
        ));
    }

    public function testThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(TerminalAssignmentRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);

        $handler = new UpdateTerminalAssignmentHandler(
            $repo,
            $terminalRepo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(TerminalAssignmentNotFoundException::class);
        ($handler)(new UpdateTerminalAssignmentCommand(
            id: self::ASSIGNMENT_ID,
            name: 'Should Fail',
            idAssignedByClient: 'ATM002',
            locationId: '01ARZ3NDEKTSV4RRFFQ69G5FAY',
        ));
    }
}
