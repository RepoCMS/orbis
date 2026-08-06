<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\UpdateTerminalAssignment\UpdateTerminalAssignmentCommand;
use App\Client\Application\Command\UpdateTerminalAssignment\UpdateTerminalAssignmentHandler;
use App\Client\Application\Exception\TerminalAssignmentNotFoundException;
use App\Client\Domain\Entities\TerminalAssignment;
use App\Client\Domain\Events\TerminalAssignmentUpdated;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
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

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(TerminalAssignmentUpdated::class));

        $handler = new UpdateTerminalAssignmentHandler($repo, $eventPublisher);
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

        $handler = new UpdateTerminalAssignmentHandler(
            $repo,
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
