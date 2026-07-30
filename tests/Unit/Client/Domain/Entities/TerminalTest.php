<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\Entities;

use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use PHPUnit\Framework\TestCase;

final class TerminalTest extends TestCase
{
    private function createTerminal(): Terminal
    {
        return Terminal::add(
            new TerminalIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new SerialNumberValueObject('SN12345678'),
            TerminalVendor::Hyosung,
            TerminalFunctionality::Dispenser,
            TerminalType::Lobby,
        );
    }

    public function testAddStoresAllFields(): void
    {
        $terminal = $this->createTerminal();

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $terminal->id());
        self::assertSame('01BXZ4MDEKTSV4RRFFQ69G5ABC', (string) $terminal->clientId());
        self::assertSame('SN12345678', (string) $terminal->serial());
        self::assertSame(TerminalVendor::Hyosung, $terminal->vendor());
        self::assertSame(TerminalFunctionality::Dispenser, $terminal->funcionality());
        self::assertSame(TerminalType::Lobby, $terminal->type());
        self::assertSame(TerminalStatus::ACTIVE, $terminal->status());
        self::assertTrue($terminal->isActive());
        self::assertFalse($terminal->isDeleted());
    }

    public function testAddWithInactiveStatus(): void
    {
        $terminal = Terminal::add(
            new TerminalIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new SerialNumberValueObject('SN12345678'),
            TerminalVendor::Hyosung,
            TerminalFunctionality::Dispenser,
            TerminalType::Lobby,
            TerminalStatus::INACTIVE,
        );

        self::assertSame(TerminalStatus::INACTIVE, $terminal->status());
        self::assertFalse($terminal->isActive());
    }

    public function testActivateSetsActiveStatus(): void
    {
        $terminal = Terminal::add(
            new TerminalIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new ClientIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            new SerialNumberValueObject('SN12345678'),
            TerminalVendor::Hyosung,
            TerminalFunctionality::Dispenser,
            TerminalType::Lobby,
            TerminalStatus::INACTIVE,
        );

        $terminal->activate();

        self::assertSame(TerminalStatus::ACTIVE, $terminal->status());
        self::assertTrue($terminal->isActive());
    }

    public function testDeactivateSetsInactiveStatus(): void
    {
        $terminal = $this->createTerminal();

        $terminal->deactivate();

        self::assertSame(TerminalStatus::INACTIVE, $terminal->status());
        self::assertFalse($terminal->isActive());
    }

    public function testChangeVendorUpdatesValue(): void
    {
        $terminal = $this->createTerminal();

        $terminal->changeVendor(TerminalVendor::Diebold_NIXDORF);

        self::assertSame(TerminalVendor::Diebold_NIXDORF, $terminal->vendor());
    }

    public function testChangeSerialUpdatesValue(): void
    {
        $terminal = $this->createTerminal();
        $newSerial = new SerialNumberValueObject('SN87654321');

        $terminal->changeSerial($newSerial);

        self::assertSame('SN87654321', (string) $terminal->serial());
    }

    public function testChangeFuncionalityUpdatesValue(): void
    {
        $terminal = $this->createTerminal();

        $terminal->changeFuncionality(TerminalFunctionality::Recycler);

        self::assertSame(TerminalFunctionality::Recycler, $terminal->funcionality());
    }

    public function testChangeTypeUpdatesValue(): void
    {
        $terminal = $this->createTerminal();

        $terminal->changeType(TerminalType::TTW);

        self::assertSame(TerminalType::TTW, $terminal->type());
    }

    public function testSoftDeleteSetsDeletedAt(): void
    {
        $terminal = $this->createTerminal();

        self::assertFalse($terminal->isDeleted());

        $terminal->softDelete();

        self::assertTrue($terminal->isDeleted());
        self::assertNotNull($terminal->deletedAt());
    }
}
