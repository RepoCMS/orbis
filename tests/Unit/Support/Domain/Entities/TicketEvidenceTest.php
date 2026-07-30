<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\TicketEvidence;
use App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketEvidenceTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';
    private const UPLOADED_BY = '01HZ0000000000000000000003';

    public function testUploadFactoryCreatesCorrectly(): void
    {
        $evidence = TicketEvidence::upload(
            id: TicketEvidenceIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            filePath: '/uploads/photo.jpg',
            uploadedBy: self::UPLOADED_BY,
        );

        self::assertSame(self::ID, (string) $evidence->id());
        self::assertSame(self::TICKET_ID, (string) $evidence->ticketId());
        self::assertSame('/uploads/photo.jpg', $evidence->filePath());
        self::assertSame(self::UPLOADED_BY, $evidence->uploadedBy());
        self::assertInstanceOf(\DateTimeImmutable::class, $evidence->uploadedAt());
    }

    public function testUploadThrowsOnEmptyFilePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La ruta del archivo es obligatoria');

        TicketEvidence::upload(
            id: TicketEvidenceIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            filePath: '',
            uploadedBy: self::UPLOADED_BY,
        );
    }

    public function testUploadThrowsOnEmptyUploadedBy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El usuario que sube la evidencia es obligatorio');

        TicketEvidence::upload(
            id: TicketEvidenceIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            filePath: '/uploads/photo.jpg',
            uploadedBy: '',
        );
    }
}
