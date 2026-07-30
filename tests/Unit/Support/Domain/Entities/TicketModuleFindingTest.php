<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\AtmModel\EvidenceMoment;
use App\Support\Domain\AtmModel\FindingType;
use App\Support\Domain\AtmModel\ModulePathValueObject;
use App\Support\Domain\Entities\FindingEvidence;
use App\Support\Domain\Entities\TicketModuleFinding;
use App\Support\Domain\Exceptions\FindingEvidenceException;
use App\Support\Domain\ValueObjects\FindingEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketModuleFindingTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';
    private const REGISTERED_BY = '01HZ0000000000000000000003';

    private function createFinding(): TicketModuleFinding
    {
        return TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM > BMU > BC'),
            componentName: 'Billete Acceptance Module',
            findingType: FindingType::OK,
            registeredBy: self::REGISTERED_BY,
        );
    }

    public function testRecordFactoryCreatesCorrectly(): void
    {
        $finding = $this->createFinding();

        self::assertSame(self::ID, (string) $finding->id());
        self::assertSame(self::TICKET_ID, (string) $finding->ticketId());
        self::assertSame('BRM > BMU > BC', $finding->modulePath()->path);
        self::assertSame('Billete Acceptance Module', $finding->componentName());
        self::assertSame(FindingType::OK, $finding->findingType());
        self::assertNull($finding->notes());
        self::assertSame(self::REGISTERED_BY, $finding->registeredBy());
        self::assertInstanceOf(\DateTimeImmutable::class, $finding->registeredAt());
        self::assertEmpty($finding->evidences());
    }

    public function testRecordWithNotes(): void
    {
        $finding = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM > BMU > BC'),
            componentName: 'Cash Dispenser',
            findingType: FindingType::FAULTY,
            registeredBy: self::REGISTERED_BY,
            notes: 'Componente desgastado',
        );

        self::assertSame('Cash Dispenser', $finding->componentName());
        self::assertSame(FindingType::FAULTY, $finding->findingType());
        self::assertSame('Componente desgastado', $finding->notes());
    }

    public function testRecordThrowsOnEmptyComponentName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre del componente es obligatorio');

        TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM > BMU > BC'),
            componentName: '',
            findingType: FindingType::OK,
            registeredBy: self::REGISTERED_BY,
        );
    }

    public function testRecordThrowsOnEmptyRegisteredBy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El usuario que registra es obligatorio');

        TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM > BMU > BC'),
            componentName: 'BMU',
            findingType: FindingType::OK,
            registeredBy: '',
        );
    }

    public function testAddEvidence(): void
    {
        $finding = $this->createFinding();

        $evidence = FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString('01HZ0000000000000000000010'),
            findingId: TicketModuleFindingIdValueObject::fromString(self::ID),
            filePath: '/uploads/test.jpg',
            originalName: 'test.jpg',
            mimeType: 'image/jpeg',
            moment: EvidenceMoment::BEFORE,
            uploadedBy: self::REGISTERED_BY,
        );

        $finding->addEvidence($evidence);

        self::assertCount(1, $finding->evidences());
        self::assertSame($evidence, $finding->evidences()[0]);
    }

    public function testChangeFindingType(): void
    {
        $finding = $this->createFinding();

        $finding->changeFindingType(FindingType::REPLACED, 'Reemplazado por nuevo');

        self::assertSame(FindingType::REPLACED, $finding->findingType());
        self::assertSame('Reemplazado por nuevo', $finding->notes());
    }

    public function testChangeFindingTypeWithoutNotes(): void
    {
        $finding = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM > BMU > BC'),
            componentName: 'BMU',
            findingType: FindingType::OK,
            registeredBy: self::REGISTERED_BY,
            notes: 'Nota original',
        );

        $finding->changeFindingType(FindingType::PENDING, null);

        self::assertSame(FindingType::PENDING, $finding->findingType());
        self::assertNull($finding->notes());
    }

    public function testRemoveEvidence(): void
    {
        $finding = $this->createFinding();

        $evidenceId = FindingEvidenceIdValueObject::fromString('01HZ0000000000000000000010');
        $evidence = FindingEvidence::upload(
            id: $evidenceId,
            findingId: TicketModuleFindingIdValueObject::fromString(self::ID),
            filePath: '/uploads/test.jpg',
            originalName: 'test.jpg',
            mimeType: 'image/jpeg',
            moment: EvidenceMoment::BEFORE,
            uploadedBy: self::REGISTERED_BY,
        );

        $finding->addEvidence($evidence);
        self::assertCount(1, $finding->evidences());

        $finding->removeEvidence($evidenceId);

        self::assertCount(0, $finding->evidences());
    }

    public function testAllFindingTypes(): void
    {
        foreach (FindingType::cases() as $type) {
            $finding = TicketModuleFinding::record(
                id: TicketModuleFindingIdValueObject::fromString(self::ID),
                ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
                modulePath: new ModulePathValueObject('BRM > BMU > BC'),
                componentName: 'Componente',
                findingType: $type,
                registeredBy: self::REGISTERED_BY,
            );
            self::assertSame($type, $finding->findingType());
        }
    }

    public function testFindingEvidenceRejectsNonImageNonVideo(): void
    {
        $this->expectException(FindingEvidenceException::class);
        $this->expectExceptionMessage('Solo se permiten archivos de imagen o video');

        FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString('01HZ0000000000000000000010'),
            findingId: TicketModuleFindingIdValueObject::fromString(self::ID),
            filePath: '/uploads/test.pdf',
            originalName: 'test.pdf',
            mimeType: 'application/pdf',
            moment: EvidenceMoment::BEFORE,
            uploadedBy: self::REGISTERED_BY,
        );
    }

    public function testFindingEvidenceUploadWithImageMime(): void
    {
        $evidence = FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString('01HZ0000000000000000000010'),
            findingId: TicketModuleFindingIdValueObject::fromString(self::ID),
            filePath: '/uploads/photo.png',
            originalName: 'photo.png',
            mimeType: 'image/png',
            moment: EvidenceMoment::BEFORE,
            uploadedBy: self::REGISTERED_BY,
        );

        self::assertSame('image/png', $evidence->mimeType());
        self::assertSame(EvidenceMoment::BEFORE, $evidence->moment());
    }

    public function testFindingEvidenceUploadWithVideoMime(): void
    {
        $evidence = FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString('01HZ0000000000000000000010'),
            findingId: TicketModuleFindingIdValueObject::fromString(self::ID),
            filePath: '/uploads/video.mp4',
            originalName: 'video.mp4',
            mimeType: 'video/mp4',
            moment: EvidenceMoment::AFTER,
            uploadedBy: self::REGISTERED_BY,
        );

        self::assertSame('video/mp4', $evidence->mimeType());
        self::assertSame(EvidenceMoment::AFTER, $evidence->moment());
    }

    public function testFindingEvidenceThrowsOnEmptyFilePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString('01HZ0000000000000000000010'),
            findingId: TicketModuleFindingIdValueObject::fromString(self::ID),
            filePath: '',
            originalName: 'test.jpg',
            mimeType: 'image/jpeg',
            moment: EvidenceMoment::BEFORE,
            uploadedBy: self::REGISTERED_BY,
        );
    }

    public function testFindingEvidenceThrowsOnEmptyOriginalName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString('01HZ0000000000000000000010'),
            findingId: TicketModuleFindingIdValueObject::fromString(self::ID),
            filePath: '/uploads/test.jpg',
            originalName: '',
            mimeType: 'image/jpeg',
            moment: EvidenceMoment::BEFORE,
            uploadedBy: self::REGISTERED_BY,
        );
    }
}
