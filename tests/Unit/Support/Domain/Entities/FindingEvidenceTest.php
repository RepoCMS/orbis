<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\AtmModel\EvidenceMoment;
use App\Support\Domain\AtmModel\FindingType;
use App\Support\Domain\AtmModel\ModulePathValueObject;
use App\Support\Domain\Entities\FindingEvidence;
use App\Support\Domain\Entities\TicketModuleFinding;
use App\Support\Domain\ValueObjects\FindingEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindingEvidenceTest extends TestCase
{
    private const EVIDENCE_ID = '01HZ0000000000000000000001';
    private const FINDING_ID = '01HZ0000000000000000000002';
    private const UPLOADED_BY = '01HZ0000000000000000000003';

    public function testUploadFactoryCreatesCorrectly(): void
    {
        $evidence = FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString(self::EVIDENCE_ID),
            findingId: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            filePath: '/uploads/test.jpg',
            originalName: 'test.jpg',
            mimeType: 'image/jpeg',
            moment: EvidenceMoment::BEFORE,
            uploadedBy: self::UPLOADED_BY,
        );

        self::assertSame(self::EVIDENCE_ID, (string) $evidence->id());
        self::assertSame(self::FINDING_ID, (string) $evidence->findingId());
        self::assertSame('/uploads/test.jpg', $evidence->filePath());
        self::assertSame('test.jpg', $evidence->originalName());
        self::assertSame('image/jpeg', $evidence->mimeType());
        self::assertSame(EvidenceMoment::BEFORE, $evidence->moment());
        self::assertSame(self::UPLOADED_BY, $evidence->uploadedBy());
        self::assertInstanceOf(\DateTimeImmutable::class, $evidence->uploadedAt());
        self::assertNull($evidence->finding());
    }

    public function testAssignToSetsFinding(): void
    {
        $evidence = FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString(self::EVIDENCE_ID),
            findingId: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            filePath: '/uploads/test.jpg',
            originalName: 'test.jpg',
            mimeType: 'image/jpeg',
            moment: EvidenceMoment::BEFORE,
            uploadedBy: self::UPLOADED_BY,
        );

        $finding = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            ticketId: TicketIdValueObject::fromString('01HZ0000000000000000000010'),
            modulePath: new ModulePathValueObject('BRM > BMU > BC'),
            componentName: 'Test',
            findingType: FindingType::OK,
            registeredBy: self::UPLOADED_BY,
        );

        $evidence->assignTo($finding);

        self::assertSame($finding, $evidence->finding());
    }

    public function testRejectsNonImageMime(): void
    {
        $this->expectException(\App\Support\Domain\Exceptions\FindingEvidenceException::class);

        FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString(self::EVIDENCE_ID),
            findingId: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            filePath: '/uploads/doc.pdf',
            originalName: 'doc.pdf',
            mimeType: 'application/pdf',
            moment: EvidenceMoment::BEFORE,
            uploadedBy: self::UPLOADED_BY,
        );
    }

    public function testThrowsOnEmptyFilePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString(self::EVIDENCE_ID),
            findingId: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            filePath: '',
            originalName: 'test.jpg',
            mimeType: 'image/jpeg',
            moment: EvidenceMoment::BEFORE,
            uploadedBy: self::UPLOADED_BY,
        );
    }
}
