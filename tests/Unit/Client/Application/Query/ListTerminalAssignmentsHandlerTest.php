<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Query\ListTerminalAssignments\ListTerminalAssignmentsHandler;
use App\Client\Application\Query\ListTerminalAssignments\ListTerminalAssignmentsQuery;
use App\Client\Application\Query\Response\TerminalAssignmentResponse;
use App\Client\Domain\Entities\TerminalAssignment;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalNameValueObject;
use App\Client\Domain\ValueObjects\TerminalIdAssignedByClientValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListTerminalAssignmentsHandlerTest extends TestCase
{
    public function testReturnsAssignmentResponses(): void
    {
        $assignment = TerminalAssignment::add(
            id: new TerminalAssignmentIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            terminalId: new TerminalIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAW'),
            name: new TerminalNameValueObject('Lobby'),
            idAssingedByClient: new TerminalIdAssignedByClientValueObject('ATM001'),
            siteId: new SiteIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAX'),
        );

        $repo = $this->createMock(TerminalAssignmentRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$assignment]);

        $handler = new ListTerminalAssignmentsHandler($repo);
        $result = ($handler)(new ListTerminalAssignmentsQuery());

        self::assertCount(1, $result);
        self::assertInstanceOf(TerminalAssignmentResponse::class, $result[0]);
    }

    public function testReturnsEmptyArray(): void
    {
        $repo = $this->createMock(TerminalAssignmentRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListTerminalAssignmentsHandler($repo);
        $result = ($handler)(new ListTerminalAssignmentsQuery());

        self::assertCount(0, $result);
    }
}
