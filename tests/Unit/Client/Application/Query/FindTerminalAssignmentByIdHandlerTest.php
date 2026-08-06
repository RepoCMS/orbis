<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Exception\TerminalAssignmentNotFoundException;
use App\Client\Application\Query\FindTerminalAssignmentById\FindTerminalAssignmentByIdHandler;
use App\Client\Application\Query\FindTerminalAssignmentById\FindTerminalAssignmentByIdQuery;
use App\Client\Application\Query\Response\TerminalAssignmentResponse;
use App\Client\Domain\Entities\TerminalAssignment;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdAssignedByClientValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalNameValueObject;
use PHPUnit\Framework\TestCase;

final class FindTerminalAssignmentByIdHandlerTest extends TestCase
{
    private const ASSIGNMENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testReturnsTerminalAssignmentResponse(): void
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

        $handler = new FindTerminalAssignmentByIdHandler($repo);
        $response = ($handler)(new FindTerminalAssignmentByIdQuery(assignmentId: self::ASSIGNMENT_ID));

        self::assertInstanceOf(TerminalAssignmentResponse::class, $response);
        self::assertSame(self::ASSIGNMENT_ID, $response->id);
    }

    public function testThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(TerminalAssignmentRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new FindTerminalAssignmentByIdHandler($repo);

        $this->expectException(TerminalAssignmentNotFoundException::class);
        ($handler)(new FindTerminalAssignmentByIdQuery(assignmentId: self::ASSIGNMENT_ID));
    }
}
