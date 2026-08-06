<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\AttachEvidence\AttachEvidenceCommand;
use App\Inventory\Application\Command\AttachEvidence\AttachEvidenceHandler;
use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Enums\EvidenceType;
use App\Inventory\Domain\Repositories\RepairEvidenceRepositoryInterface;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class AttachEvidenceHandlerTest extends TestCase
{
    private const REPAIR_ID = '01HZ0000000000000000000001';
    private const NEW_ID    = '01HZ0000000000000000000002';

    public function testAttachesEvidence(): void
    {
        $repair = Repair::assign(
            id: RepairIdValueObject::fromString(self::REPAIR_ID),
            serializedStockId: SerializedStockIdValueObject::fromString('01HZ0000000000000000000003'),
            laboratoryId: LocationIdValueObject::fromString('01HZ0000000000000000000004'),
            assignedById: new UserIdValueObject('01HZ0000000000000000000005'),
        );

        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $repairRepo->method('findById')->willReturn($repair);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::NEW_ID);

        $evidenceRepo = $this->createMock(RepairEvidenceRepositoryInterface::class);
        $evidenceRepo->expects(self::once())->method('save');

        $handler = new AttachEvidenceHandler($repairRepo, $evidenceRepo, $idGenerator);
        $result = ($handler)(new AttachEvidenceCommand(
            repairId: self::REPAIR_ID,
            filePath: '/tmp/evidence.jpg',
            type: EvidenceType::PHOTO->value,
            uploadedBy: 'user-1',
        ));

        self::assertSame(self::NEW_ID, $result);
    }

    public function testThrowsWhenRepairNotFound(): void
    {
        $repairRepo = $this->createMock(RepairRepositoryInterface::class);
        $repairRepo->method('findById')->willReturn(null);

        $handler = new AttachEvidenceHandler(
            $repairRepo,
            $this->createMock(RepairEvidenceRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
        );

        $this->expectException(RepairNotFoundException::class);
        ($handler)(new AttachEvidenceCommand(
            repairId: '01HZ0000000000000000000099',
            filePath: '/tmp/test.jpg',
            type: EvidenceType::PHOTO->value,
            uploadedBy: 'user-1',
        ));
    }
}
