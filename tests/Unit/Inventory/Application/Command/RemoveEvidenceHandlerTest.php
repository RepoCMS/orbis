<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\RemoveEvidence\RemoveEvidenceCommand;
use App\Inventory\Application\Command\RemoveEvidence\RemoveEvidenceHandler;
use App\Inventory\Application\Exception\RepairEvidenceNotFoundException;
use App\Inventory\Domain\Entities\RepairEvidence;
use App\Inventory\Domain\Enums\EvidenceType;
use App\Inventory\Domain\Repositories\RepairEvidenceRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RepairEvidenceIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use PHPUnit\Framework\TestCase;

final class RemoveEvidenceHandlerTest extends TestCase
{
    private const EVIDENCE_ID = '01HZ0000000000000000000001';
    private const REPAIR_ID   = '01HZ0000000000000000000002';

    public function testRemovesEvidence(): void
    {
        $evidence = RepairEvidence::upload(
            id: RepairEvidenceIdValueObject::fromString(self::EVIDENCE_ID),
            repairId: RepairIdValueObject::fromString(self::REPAIR_ID),
            filePath: '/tmp/test.jpg',
            type: EvidenceType::PHOTO,
            uploadedBy: 'user-1',
        );

        $repo = $this->createMock(RepairEvidenceRepositoryInterface::class);
        $repo->method('findById')->willReturn($evidence);
        $repo->expects(self::once())->method('delete')->with($evidence);

        $handler = new RemoveEvidenceHandler($repo);
        ($handler)(new RemoveEvidenceCommand(
            evidenceId: self::EVIDENCE_ID,
            repairId: self::REPAIR_ID,
        ));
    }

    public function testThrowsWhenEvidenceNotFound(): void
    {
        $repo = $this->createMock(RepairEvidenceRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new RemoveEvidenceHandler($repo);

        $this->expectException(RepairEvidenceNotFoundException::class);
        ($handler)(new RemoveEvidenceCommand(
            evidenceId: '01HZ0000000000000000000099',
            repairId: self::REPAIR_ID,
        ));
    }
}
