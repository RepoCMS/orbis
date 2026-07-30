<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\Entities;

use App\Client\Domain\Entities\TerminalAssignment;
use App\Client\Domain\Enums\TerminalAssignmentStatus;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdAssignedByClientValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalNameValueObject;
use PHPUnit\Framework\TestCase;

final class TerminalAssignmentTest extends TestCase
{
    private function createAssignment(): TerminalAssignment
    {
        return TerminalAssignment::add(
            new TerminalAssignmentIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new TerminalIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new TerminalNameValueObject('Cajero Centro'),
            new TerminalIdAssignedByClientValueObject('IDCLI001'),
            new SiteIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'),
        );
    }

    public function testAddStoresAllFields(): void
    {
        $assignment = $this->createAssignment();

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $assignment->id());
        self::assertSame('01BXZ4MDEKTSV4RRFFQ69G5ABC', (string) $assignment->terminalId());
        self::assertSame('Cajero Centro', (string) $assignment->name());
        self::assertSame('IDCLI001', (string) $assignment->idAssingedByClient());
        self::assertSame('01CYZ5NDEKTSV4RRFFQ69G5DEF', (string) $assignment->siteId());
        self::assertSame(TerminalAssignmentStatus::OPERATIVE, $assignment->status());
        self::assertFalse($assignment->isDeleted());
    }

    public function testAddWithInoperativeStatus(): void
    {
        $assignment = TerminalAssignment::add(
            new TerminalAssignmentIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new TerminalIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new TerminalNameValueObject('Cajero Centro'),
            new TerminalIdAssignedByClientValueObject('IDCLI001'),
            new SiteIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'),
            TerminalAssignmentStatus::INOPERATIVE,
        );

        self::assertSame(TerminalAssignmentStatus::INOPERATIVE, $assignment->status());
    }

    public function testActivateSetsOperativeStatus(): void
    {
        $assignment = TerminalAssignment::add(
            new TerminalAssignmentIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new TerminalIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new TerminalNameValueObject('Cajero Centro'),
            new TerminalIdAssignedByClientValueObject('IDCLI001'),
            new SiteIdValueObject('01CYZ5NDEKTSV4RRFFQ69G5DEF'),
            TerminalAssignmentStatus::INOPERATIVE,
        );

        $assignment->activate();

        self::assertSame(TerminalAssignmentStatus::OPERATIVE, $assignment->status());
    }

    public function testDeactivateSetsInoperativeStatus(): void
    {
        $assignment = $this->createAssignment();

        $assignment->deactivate();

        self::assertSame(TerminalAssignmentStatus::INOPERATIVE, $assignment->status());
    }

    public function testChangeNameUpdatesValue(): void
    {
        $assignment = $this->createAssignment();

        $assignment->changeName('Cajero Norte');

        self::assertSame('Cajero Norte', (string) $assignment->name());
    }

    public function testChangeIdAssingedByClientUpdatesValue(): void
    {
        $assignment = $this->createAssignment();

        $assignment->changeIdAssingedByClient('IDCLI002');

        self::assertSame('IDCLI002', (string) $assignment->idAssingedByClient());
    }

    public function testChangeSiteIdUpdatesValue(): void
    {
        $assignment = $this->createAssignment();
        $newSiteId = new SiteIdValueObject('01DEG6NDEKTSV4RRFFQ69G5GHB');

        $assignment->changeSiteId($newSiteId);

        self::assertSame('01DEG6NDEKTSV4RRFFQ69G5GHB', (string) $assignment->siteId());
    }

    public function testSoftDeleteSetsDeletedAt(): void
    {
        $assignment = $this->createAssignment();

        self::assertFalse($assignment->isDeleted());

        $assignment->softDelete();

        self::assertTrue($assignment->isDeleted());
        self::assertNotNull($assignment->deletedAt());
    }
}
