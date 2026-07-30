<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\ActivateTerminalAssignment\ActivateTerminalAssignmentCommand;
use App\Client\Application\Command\ActivateTerminalAssignment\ActivateTerminalAssignmentHandler;
use App\Client\Application\Exception\TerminalAssignmentNotFoundException;
use App\Client\Domain\Entities\TerminalAssignment;
use App\Client\Domain\Enums\TerminalAssignmentStatus;
use App\Client\Domain\Events\TerminalAssignmentReactivated;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdAssignedByClientValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalNameValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class ActivateTerminalAssignmentHandlerTest extends TestCase
{
    private const ASSIGNMENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FB1';
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FD1';
    private const LOCATION_ID = '01ARZ3NDEKTSV4RRFFQ69G5FE1';

    private function makeInactiveAssignment(): TerminalAssignment
    {
        return TerminalAssignment::add(
            TerminalAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID),
            TerminalIdValueObject::fromString(self::TERMINAL_ID),
            new TerminalNameValueObject('ATM Principal'),
            new TerminalIdAssignedByClientValueObject('ATM001'),
            new SiteIdValueObject(self::LOCATION_ID),
            TerminalAssignmentStatus::INOPERATIVE,
        );
    }

    public function testActivatesAssignmentSuccessfully(): void
    {
        $assignment = $this->makeInactiveAssignment();

        $assignmentRepo = $this->createMock(TerminalAssignmentRepositoryInterface::class);
        $assignmentRepo->method('findById')->willReturn($assignment);
        $assignmentRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (TerminalAssignment $a): bool {
                return $a->status() === TerminalAssignmentStatus::OPERATIVE;
            }));

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TerminalAssignmentReactivated::class));

        $handler = new ActivateTerminalAssignmentHandler($assignmentRepo, $eventPublisher);
        ($handler)(new ActivateTerminalAssignmentCommand(id: self::ASSIGNMENT_ID));
    }

    public function testThrowsWhenAssignmentNotFound(): void
    {
        $assignmentRepo = $this->createMock(TerminalAssignmentRepositoryInterface::class);
        $assignmentRepo->method('findById')->willReturn(null);
        $assignmentRepo->expects($this->never())->method('save');

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new ActivateTerminalAssignmentHandler($assignmentRepo, $eventPublisher);

        $this->expectException(TerminalAssignmentNotFoundException::class);
        $this->expectExceptionMessage(self::ASSIGNMENT_ID);

        ($handler)(new ActivateTerminalAssignmentCommand(id: self::ASSIGNMENT_ID));
    }
}
