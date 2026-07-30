<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\ListEvidences\ListEvidencesHandler;
use App\Support\Application\Query\ListEvidences\ListEvidencesQuery;
use App\Support\Application\Query\Response\TicketEvidenceResponse;
use App\Support\Domain\Entities\TicketEvidence;
use App\Support\Domain\Repositories\TicketEvidenceRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListEvidencesHandlerTest extends TestCase
{
    private const TICKET_ID = '01HZ0000000000000000000001';

    public function testReturnsEvidencesForTicket(): void
    {
        $evidence1 = TicketEvidence::upload(
            id: TicketEvidenceIdValueObject::fromString('01HZ000000000000000000000A'),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            filePath: '/tmp/evidence1.jpg',
            uploadedBy: 'user-1',
        );
        $evidence2 = TicketEvidence::upload(
            id: TicketEvidenceIdValueObject::fromString('01HZ000000000000000000000B'),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            filePath: '/tmp/evidence2.jpg',
            uploadedBy: 'user-2',
        );

        $repo = $this->createMock(TicketEvidenceRepositoryInterface::class);
        $repo->method('findByTicket')->willReturn([$evidence1, $evidence2]);

        $handler = new ListEvidencesHandler($repo);
        $result = ($handler)(new ListEvidencesQuery(ticketId: self::TICKET_ID));

        self::assertCount(2, $result);
        self::assertInstanceOf(TicketEvidenceResponse::class, $result[0]);
        self::assertInstanceOf(TicketEvidenceResponse::class, $result[1]);
        self::assertSame('/tmp/evidence1.jpg', $result[0]->filePath);
        self::assertSame('/tmp/evidence2.jpg', $result[1]->filePath);
    }

    public function testReturnsEmptyArrayWhenNoEvidences(): void
    {
        $repo = $this->createMock(TicketEvidenceRepositoryInterface::class);
        $repo->method('findByTicket')->willReturn([]);

        $handler = new ListEvidencesHandler($repo);
        $result = ($handler)(new ListEvidencesQuery(ticketId: self::TICKET_ID));

        self::assertCount(0, $result);
    }
}
