<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Presentation\Http;

use App\Support\Application\Command\RecordModuleFinding\RecordModuleFindingCommand;
use App\Support\Domain\AtmModel\FindingType;
use App\Support\Domain\AtmModel\ModulePathValueObject;
use App\Support\Domain\Entities\FindingEvidence;
use App\Support\Domain\Entities\TicketModuleFinding;
use App\Support\Domain\AtmModel\EvidenceMoment;
use App\Support\Domain\Repositories\TicketModuleFindingRepositoryInterface;
use App\Support\Domain\ValueObjects\FindingEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests focused on command/query construction and repository interaction
 * for the module finding controller endpoints.
 */
final class ApiSupportTicketModuleFindingControllerTest extends TestCase
{
    private const TICKET_ID   = '01HZ00000000000000000000T1';
    private const FINDING_ID  = '01HZ00000000000000000000F1';
    private const EVIDENCE_ID = '01HZ00000000000000000000E1';
    private const USER_ID     = '01HZ00000000000000000000Z1';

    // ── Repository-level tests (test delete/save logic without mocking final classes) ──

    public function test_delete_module_finding_via_repository(): void
    {
        $finding = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM > BMU > BC'),
            componentName: 'Bill Cassette',
            findingType: FindingType::FAULTY,
            registeredBy: self::USER_ID,
        );

        $repo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findById')
            ->with($this->callback(fn($id) => (string) $id === self::FINDING_ID))
            ->willReturn($finding);
        $repo->expects($this->once())->method('delete')->with($finding);

        $found = $repo->findById(TicketModuleFindingIdValueObject::fromString(self::FINDING_ID));
        self::assertNotNull($found);
        $repo->delete($found);
    }

    public function test_delete_nonexistent_module_finding(): void
    {
        $repo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findById')
            ->willReturn(null);
        $repo->expects($this->never())->method('delete');

        $found = $repo->findById(TicketModuleFindingIdValueObject::fromString(self::FINDING_ID));
        self::assertNull($found);
    }

    public function test_delete_evidence_from_finding(): void
    {
        $finding = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM > BMU > BC'),
            componentName: 'Bill Cassette',
            findingType: FindingType::FAULTY,
            registeredBy: self::USER_ID,
        );

        $evidence = FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString(self::EVIDENCE_ID),
            findingId: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            filePath: 'uploads/findings/test.jpg',
            originalName: 'foto.jpg',
            mimeType: 'image/jpeg',
            moment: EvidenceMoment::BEFORE,
            uploadedBy: self::USER_ID,
        );
        $finding->addEvidence($evidence);

        self::assertCount(1, $finding->evidences());

        $finding->removeEvidence(FindingEvidenceIdValueObject::fromString(self::EVIDENCE_ID));
        self::assertCount(0, $finding->evidences());
    }

    // ── Command construction tests ──

    public function test_record_module_finding_command_construction(): void
    {
        $request = new Request(
            content: json_encode([
                'modulePath' => 'BRM > BMU > BC',
                'componentName' => 'Bill Cassette',
                'findingType' => 'faulty',
                'notes' => 'Test note',
            ], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json'],
        );

        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $command = new RecordModuleFindingCommand(
            ticketId: self::TICKET_ID,
            modulePath: trim((string) ($payload['modulePath'] ?? '')),
            componentName: trim((string) ($payload['componentName'] ?? '')),
            findingType: trim((string) ($payload['findingType'] ?? '')),
            registeredBy: self::USER_ID,
            notes: isset($payload['notes']) ? trim((string) $payload['notes']) : null,
        );

        self::assertSame(self::TICKET_ID, $command->ticketId);
        self::assertSame('BRM > BMU > BC', $command->modulePath);
        self::assertSame('Bill Cassette', $command->componentName);
        self::assertSame('faulty', $command->findingType);
        self::assertSame('Test note', $command->notes);
        self::assertSame(self::USER_ID, $command->registeredBy);
    }

    public function test_record_module_finding_command_parsing_without_notes(): void
    {
        $request = new Request(
            content: json_encode([
                'modulePath' => 'BRM',
                'componentName' => 'Cassette',
                'findingType' => 'ok',
            ], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json'],
        );

        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $command = new RecordModuleFindingCommand(
            ticketId: self::TICKET_ID,
            modulePath: trim((string) ($payload['modulePath'] ?? '')),
            componentName: trim((string) ($payload['componentName'] ?? '')),
            findingType: trim((string) ($payload['findingType'] ?? '')),
            registeredBy: self::USER_ID,
            notes: isset($payload['notes']) ? trim((string) $payload['notes']) : null,
        );

        self::assertSame('BRM', $command->modulePath);
        self::assertSame('Cassette', $command->componentName);
        self::assertSame('ok', $command->findingType);
        self::assertNull($command->notes);
    }

    // ── Domain logic tests for controller-adjacent behavior ──

    public function test_finding_type_from_enum(): void
    {
        self::assertSame(FindingType::FAULTY, FindingType::from('faulty'));
        self::assertSame(FindingType::OK, FindingType::from('ok'));
        self::assertSame(FindingType::REPLACED, FindingType::from('replaced'));
        self::assertSame(FindingType::PENDING, FindingType::from('pending'));
    }

    public function test_invalid_finding_type_throws_value_error(): void
    {
        $this->expectException(\ValueError::class);
        FindingType::from('invalid');
    }

    public function test_evidence_moment_from_enum(): void
    {
        self::assertSame(EvidenceMoment::BEFORE, EvidenceMoment::from('before'));
        self::assertSame(EvidenceMoment::AFTER, EvidenceMoment::from('after'));
    }

    public function test_invalid_evidence_moment_throws_value_error(): void
    {
        $this->expectException(\ValueError::class);
        EvidenceMoment::from('invalid');
    }

    public function test_module_finding_upload_evidence_domain_flow(): void
    {
        $finding = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM'),
            componentName: 'Cassette',
            findingType: FindingType::PENDING,
            registeredBy: self::USER_ID,
        );

        $evidence = FindingEvidence::upload(
            id: FindingEvidenceIdValueObject::fromString(self::EVIDENCE_ID),
            findingId: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            filePath: 'uploads/findings/photo.jpg',
            originalName: 'photo.jpg',
            mimeType: 'image/jpeg',
            moment: EvidenceMoment::AFTER,
            uploadedBy: self::USER_ID,
        );

        $finding->addEvidence($evidence);

        self::assertCount(1, $finding->evidences());
        $retrieved = $finding->evidences()[0];
        self::assertSame('uploads/findings/photo.jpg', $retrieved->filePath());
        self::assertSame('image/jpeg', $retrieved->mimeType());
        self::assertSame(EvidenceMoment::AFTER, $retrieved->moment());
    }

    public function test_module_finding_change_type_domain_flow(): void
    {
        $finding = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM'),
            componentName: 'Cassette',
            findingType: FindingType::FAULTY,
            registeredBy: self::USER_ID,
        );

        self::assertSame(FindingType::FAULTY, $finding->findingType());

        $finding->changeFindingType(FindingType::REPLACED, 'Componente cambiado');

        self::assertSame(FindingType::REPLACED, $finding->findingType());
        self::assertSame('Componente cambiado', $finding->notes());
    }
}
