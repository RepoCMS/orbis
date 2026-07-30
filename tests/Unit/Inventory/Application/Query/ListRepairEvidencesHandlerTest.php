<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\ListRepairEvidences\ListRepairEvidencesHandler;
use App\Inventory\Application\Query\ListRepairEvidences\ListRepairEvidencesQuery;
use App\Inventory\Application\Query\Response\RepairEvidenceResponse;
use App\Inventory\Domain\Entities\RepairEvidence;
use App\Inventory\Domain\Enums\EvidenceType;
use App\Inventory\Domain\Repositories\RepairEvidenceRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RepairEvidenceIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListRepairEvidencesHandlerTest extends TestCase
{
    private const EVIDENCE_ID_1 = '01HZ0000000000000000000001';
    private const EVIDENCE_ID_2 = '01HZ0000000000000000000002';
    private const REPAIR_ID     = '01HZ0000000000000000000010';

    private function makeEvidence(string $id, EvidenceType $type, ?string $notes = null): RepairEvidence
    {
        return RepairEvidence::upload(
            new RepairEvidenceIdValueObject($id),
            new RepairIdValueObject(self::REPAIR_ID),
            '/uploads/repairs/' . $id . '.jpg',
            $type,
            'user-001',
            $notes,
        );
    }

    private function makeHandler(array $evidences): ListRepairEvidencesHandler
    {
        $repo = $this->createMock(RepairEvidenceRepositoryInterface::class);
        $repo->method('findByRepairId')->willReturn($evidences);

        return new ListRepairEvidencesHandler($repo);
    }

    public function testReturnsAllEvidencesForRepair(): void
    {
        $e1 = $this->makeEvidence(self::EVIDENCE_ID_1, EvidenceType::PHOTO);
        $e2 = $this->makeEvidence(self::EVIDENCE_ID_2, EvidenceType::VIDEO);

        $handler = $this->makeHandler([$e1, $e2]);
        $query   = new ListRepairEvidencesQuery(self::REPAIR_ID);
        $result  = ($handler)($query);

        self::assertCount(2, $result);
        self::assertInstanceOf(RepairEvidenceResponse::class, $result[0]);
        self::assertInstanceOf(RepairEvidenceResponse::class, $result[1]);
    }

    public function testReturnsEmptyArrayWhenNoEvidences(): void
    {
        $handler = $this->makeHandler([]);
        $query   = new ListRepairEvidencesQuery(self::REPAIR_ID);
        $result  = ($handler)($query);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $evidence = $this->makeEvidence(self::EVIDENCE_ID_1, EvidenceType::PHOTO, 'Foto del estado inicial');

        $handler = $this->makeHandler([$evidence]);
        $query   = new ListRepairEvidencesQuery(self::REPAIR_ID);
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertSame(self::EVIDENCE_ID_1, $result[0]->id);
        self::assertSame(self::REPAIR_ID, $result[0]->repairId);
        self::assertSame('/uploads/repairs/' . self::EVIDENCE_ID_1 . '.jpg', $result[0]->filePath);
        self::assertSame('photo', $result[0]->type);
        self::assertSame('photo', $result[0]->typeLabel);
        self::assertSame('user-001', $result[0]->uploadedBy);
        self::assertSame('Foto del estado inicial', $result[0]->notes);
        self::assertNotNull($result[0]->createdAt);
    }

    public function testMapsAllEvidenceTypes(): void
    {
        $photo = $this->makeEvidence(self::EVIDENCE_ID_1, EvidenceType::PHOTO);
        $video = $this->makeEvidence(self::EVIDENCE_ID_2, EvidenceType::VIDEO);

        $handler = $this->makeHandler([$photo, $video]);
        $query   = new ListRepairEvidencesQuery(self::REPAIR_ID);
        $result  = ($handler)($query);

        self::assertCount(2, $result);
        self::assertSame('photo', $result[0]->type);
        self::assertSame('photo', $result[0]->typeLabel);
        self::assertSame('video', $result[1]->type);
        self::assertSame('video', $result[1]->typeLabel);
    }

    public function testResponseWithNullNotes(): void
    {
        $evidence = $this->makeEvidence(self::EVIDENCE_ID_1, EvidenceType::PHOTO);

        $handler = $this->makeHandler([$evidence]);
        $query   = new ListRepairEvidencesQuery(self::REPAIR_ID);
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertNull($result[0]->notes);
    }
}
