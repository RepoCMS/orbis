<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use App\Support\Application\Command\UploadFindingEvidence\UploadFindingEvidenceCommand;
use App\Support\Application\Command\UploadFindingEvidence\UploadFindingEvidenceHandler;
use App\Support\Domain\AtmModel\EvidenceMoment;
use App\Support\Domain\AtmModel\FindingType;
use App\Support\Domain\AtmModel\ModulePathValueObject;
use App\Support\Domain\Entities\TicketModuleFinding;
use App\Support\Domain\Repositories\TicketModuleFindingRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;
use PHPUnit\Framework\TestCase;

final class UploadFindingEvidenceHandlerTest extends TestCase
{
    private const FINDING_ID    = '01HZ0000000000000000000001';
    private const TICKET_ID     = '01HZ0000000000000000000002';
    private const EVIDENCE_ID   = '01HZ0000000000000000000003';
    private const UPLOADED_BY   = '01HZ0000000000000000000004';

    private function makeFinding(): TicketModuleFinding
    {
        return TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM > BMU > BC'),
            componentName: 'BC - BILL CASSETTE',
            findingType: FindingType::FAULTY,
            registeredBy: self::UPLOADED_BY,
        );
    }

    public function testUploadsEvidence(): void
    {
        $finding = $this->makeFinding();

        $findingRepo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $findingRepo->method('findById')->willReturn($finding);
        $findingRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::EVIDENCE_ID);

        $handler = new UploadFindingEvidenceHandler($findingRepo, $idGenerator);
        $result = ($handler)(new UploadFindingEvidenceCommand(
            findingId:    self::FINDING_ID,
            filePath:     '/tmp/uploads/evidence.jpg',
            originalName: 'foto_cassette.jpg',
            mimeType:     'image/jpeg',
            moment:       EvidenceMoment::BEFORE->value,
            uploadedBy:   self::UPLOADED_BY,
        ));

        self::assertSame(self::EVIDENCE_ID, $result);
        self::assertCount(1, $finding->evidences());
    }

    public function testThrowsWhenFindingNotFound(): void
    {
        $findingRepo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $findingRepo->method('findById')->willReturn(null);
        $findingRepo->expects($this->never())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new UploadFindingEvidenceHandler($findingRepo, $idGenerator);

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('no encontrado');

        ($handler)(new UploadFindingEvidenceCommand(
            findingId:    '01HZ0000000000000000000099',
            filePath:     '/tmp/uploads/evidence.jpg',
            originalName: 'foto.jpg',
            mimeType:     'image/jpeg',
            moment:       EvidenceMoment::BEFORE->value,
            uploadedBy:   self::UPLOADED_BY,
        ));
    }
}
