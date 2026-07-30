<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Query\ListEntries\ListEntriesHandler;
use App\Inventory\Application\Query\Response\EntryResponse;
use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\Enums\EntryStatus;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ListEntriesHandlerTest extends TestCase
{
    private const ENTRY_ID_1 = '01HZ0000000000000000000001';
    private const ENTRY_ID_2 = '01HZ0000000000000000000002';
    private const ENTRY_ID_3 = '01HZ0000000000000000000003';
    private const USER_ID    = '01HZ0000000000000000000010';

    private function makeEntry(
        string $id,
        string $reference,
        EntryStatus $status,
        ?string $notes = null,
        ?DateTimeImmutable $confirmedAt = null,
    ): Entry {
        $entry = new Entry(
            new EntryIdValueObject($id),
            $reference,
            $status,
            $notes,
            new UserIdValueObject(self::USER_ID),
            new DateTimeImmutable('2025-01-15 10:00:00'),
            $confirmedAt,
        );

        return $entry;
    }

    public function testReturnsAllEntries(): void
    {
        $entry1 = $this->makeEntry(self::ENTRY_ID_1, 'ENT-001', EntryStatus::DRAFT);
        $entry2 = $this->makeEntry(self::ENTRY_ID_2, 'ENT-002', EntryStatus::CONFIRMED);

        $repo = $this->createMock(EntryRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findAll')
            ->willReturn([$entry1, $entry2]);

        $handler = new ListEntriesHandler($repo);
        $result  = ($handler)();

        self::assertCount(2, $result);
        self::assertInstanceOf(EntryResponse::class, $result[0]);
        self::assertInstanceOf(EntryResponse::class, $result[1]);
    }

    public function testReturnsEmptyArrayWhenNoEntries(): void
    {
        $repo = $this->createMock(EntryRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListEntriesHandler($repo);
        $result  = ($handler)();

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $entry = $this->makeEntry(
            self::ENTRY_ID_1,
            'ENT-001',
            EntryStatus::CONFIRMED,
            'Nota de prueba',
            new DateTimeImmutable('2025-01-15 14:00:00'),
        );

        $repo = $this->createMock(EntryRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$entry]);

        $handler = new ListEntriesHandler($repo);
        $result  = ($handler)();

        self::assertCount(1, $result);
        self::assertSame(self::ENTRY_ID_1, $result[0]->id);
        self::assertSame('ENT-001', $result[0]->reference);
        self::assertSame('confirmed', $result[0]->status);
        self::assertSame('Confirmada', $result[0]->statusLabel);
        self::assertSame('Nota de prueba', $result[0]->notes);
        self::assertSame(self::USER_ID, $result[0]->createdBy);
        self::assertSame('2025-01-15 10:00:00', $result[0]->createdAt);
        self::assertSame('2025-01-15 14:00:00', $result[0]->confirmedAt);
    }

    public function testMapsAllEntryStatuses(): void
    {
        $draft     = $this->makeEntry(self::ENTRY_ID_1, 'ENT-001', EntryStatus::DRAFT);
        $confirmed = $this->makeEntry(self::ENTRY_ID_2, 'ENT-002', EntryStatus::CONFIRMED);
        $cancelled = $this->makeEntry(self::ENTRY_ID_3, 'ENT-003', EntryStatus::CANCELLED);

        $repo = $this->createMock(EntryRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$draft, $confirmed, $cancelled]);

        $handler = new ListEntriesHandler($repo);
        $result  = ($handler)();

        self::assertCount(3, $result);
        self::assertSame('draft', $result[0]->status);
        self::assertSame('Borrador', $result[0]->statusLabel);
        self::assertSame('confirmed', $result[1]->status);
        self::assertSame('Confirmada', $result[1]->statusLabel);
        self::assertSame('cancelled', $result[2]->status);
        self::assertSame('Cancelada', $result[2]->statusLabel);
    }

    public function testResponseWithNullOptionalFields(): void
    {
        $entry = $this->makeEntry(self::ENTRY_ID_1, 'ENT-001', EntryStatus::DRAFT);

        $repo = $this->createMock(EntryRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$entry]);

        $handler = new ListEntriesHandler($repo);
        $result  = ($handler)();

        self::assertCount(1, $result);
        self::assertNull($result[0]->notes);
        self::assertNull($result[0]->confirmedAt);
    }
}
