<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Exception\EvidenceNotFoundException;
use App\Support\Application\Query\GetEvidence\GetEvidenceHandler;
use App\Support\Application\Query\GetEvidence\GetEvidenceQuery;
use App\Support\Application\Query\Response\TicketEvidenceResponse;
use App\Support\Domain\Entities\TicketEvidence;
use App\Support\Domain\Enums\EvidenceVisibility;
use App\Support\Domain\Repositories\TicketEvidenceRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class GetEvidenceHandlerTest extends TestCase
{
    private const EVIDENCE_ID = '01HZ0000000000000000000001';
    private const TICKET_ID   = '01HZ0000000000000000000002';
    private const FILE_PATH   = '/tmp/evidence.jpg';

    public function testReturnsEvidence(): void
    {
        $evidence = TicketEvidence::upload(
            id: TicketEvidenceIdValueObject::fromString(self::EVIDENCE_ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            filePath: self::FILE_PATH,
            uploadedBy: 'user-1',
        );

        $repo = $this->createMock(TicketEvidenceRepositoryInterface::class);
        $repo->method('findById')->willReturn($evidence);

        $handler = new GetEvidenceHandler($repo);
        $result = ($handler)(new GetEvidenceQuery(
            ticketId: self::TICKET_ID,
            evidenceId: self::EVIDENCE_ID,
        ));

        self::assertInstanceOf(TicketEvidenceResponse::class, $result);
        self::assertSame(self::EVIDENCE_ID, $result->id);
        self::assertSame(self::FILE_PATH, $result->filePath);
        self::assertSame(EvidenceVisibility::ALL->value, $result->visibility);
    }

    public function testThrowsWhenEvidenceNotFound(): void
    {
        $repo = $this->createMock(TicketEvidenceRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new GetEvidenceHandler($repo);

        $this->expectException(EvidenceNotFoundException::class);

        ($handler)(new GetEvidenceQuery(
            ticketId: self::TICKET_ID,
            evidenceId: '01HZ0000000000000000000099',
        ));
    }
}
