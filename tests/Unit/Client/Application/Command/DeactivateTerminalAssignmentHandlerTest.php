<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\DeactivateTerminalAssignment\DeactivateTerminalAssignmentCommand;
use App\Client\Application\Command\DeactivateTerminalAssignment\DeactivateTerminalAssignmentHandler;
use App\Client\Application\Exception\TerminalAssignmentNotFoundException;
use App\Client\Domain\Enums\TerminalAssignmentStatus;
use App\Client\Domain\Entities\TerminalAssignment;
use App\Client\Domain\Events\TerminalAssignmentDeactivated;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalNameValueObject;
use App\Client\Domain\ValueObjects\TerminalIdAssignedByClientValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class DeactivateTerminalAssignmentHandlerTest extends TestCase
{
    private const ASSIGNMENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testDeactivatesAssignmentSuccessfully(): void
    {
        $assignment = TerminalAssignment::add(
            id: new TerminalAssignmentIdValueObject(self::ASSIGNMENT_ID),
            terminalId: new TerminalIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAW'),
            name: new TerminalNameValueObject('Lobby'),
            idAssingedByClient: new TerminalIdAssignedByClientValueObject('ATM001'),
            siteId: new SiteIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAX'),
        );

        $repo = $this->createMock(TerminalAssignmentRepositoryInterface::class);
        $repo->method('findById')->willReturn($assignment);
        $repo->expects($this->once())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(TerminalAssignmentDeactivated::class));

        $handler = new DeactivateTerminalAssignmentHandler($repo, $eventPublisher);
        ($handler)(new DeactivateTerminalAssignmentCommand(id: self::ASSIGNMENT_ID));

        self::assertSame(TerminalAssignmentStatus::INOPERATIVE, $assignment->status());
    }

    public function testThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(TerminalAssignmentRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new DeactivateTerminalAssignmentHandler(
            $repo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(TerminalAssignmentNotFoundException::class);
        ($handler)(new DeactivateTerminalAssignmentCommand(id: self::ASSIGNMENT_ID));
    }
}
