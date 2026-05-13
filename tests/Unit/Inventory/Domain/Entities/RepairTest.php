<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use PHPUnit\Framework\TestCase;

final class RepairTest extends TestCase
{
    // ─── Factory helpers ─────────────────────────────────────────────────────

    private function makeRepair(): Repair
    {
        return Repair::assign(
            new RepairIdValueObject('01HZ0000000000000000000001'),
            new SerializedStockIdValueObject('01HZ0000000000000000000002'),
            new LocationIdValueObject('01HZ0000000000000000000003'),
            new UserIdValueObject('01HZ0000000000000000000004'),
            'Pantalla rota',
        );
    }

    private function makeTechnicianId(): UserIdValueObject
    {
        return new UserIdValueObject('01HZ0000000000000000000005');
    }

    // ─── Assignment ──────────────────────────────────────────────────────────

    public function testAssignCreatesPendingRepair(): void
    {
        $repair = $this->makeRepair();

        self::assertSame(RepairStatus::PENDING, $repair->status());
        self::assertTrue($repair->isPending());
        self::assertNull($repair->technicianId());
        self::assertNull($repair->startedAt());
        self::assertNull($repair->completedAt());
    }

    public function testAssignStoresNotes(): void
    {
        $repair = $this->makeRepair();
        self::assertSame('Pantalla rota', $repair->notes());
    }

    // ─── Start ───────────────────────────────────────────────────────────────

    public function testStartSetsInProgressAndTechnicianAndStartedAt(): void
    {
        $repair      = $this->makeRepair();
        $technician  = $this->makeTechnicianId();

        $repair->start($technician);

        self::assertSame(RepairStatus::IN_PROGRESS, $repair->status());
        self::assertTrue($repair->isInProgress());
        self::assertSame($technician, $repair->technicianId());
        self::assertNotNull($repair->startedAt());
    }

    // ─── Complete ────────────────────────────────────────────────────────────

    public function testCompleteSetsStatusAndCompletedAt(): void
    {
        $repair = $this->makeRepair();
        $repair->start($this->makeTechnicianId());
        $repair->complete('Reparado con éxito');

        self::assertSame(RepairStatus::COMPLETED, $repair->status());
        self::assertNotNull($repair->completedAt());
        self::assertSame('Reparado con éxito', $repair->notes());
    }

    public function testCompleteWithoutNotePreservesOriginalNotes(): void
    {
        $repair = $this->makeRepair();
        $repair->start($this->makeTechnicianId());
        $repair->complete();

        self::assertSame(RepairStatus::COMPLETED, $repair->status());
        self::assertSame('Pantalla rota', $repair->notes());
    }

    // ─── Unrepairable ────────────────────────────────────────────────────────

    public function testMarkUnrepairableSetsStatusAndCompletedAt(): void
    {
        $repair = $this->makeRepair();
        $repair->start($this->makeTechnicianId());
        $repair->markUnrepairable('Sin piezas de repuesto');

        self::assertSame(RepairStatus::UNREPAIRABLE, $repair->status());
        self::assertNotNull($repair->completedAt());
        self::assertSame('Sin piezas de repuesto', $repair->notes());
    }
}
