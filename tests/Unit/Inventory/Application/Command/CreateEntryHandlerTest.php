<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\CreateEntry\CreateEntryCommand;
use App\Inventory\Application\Command\CreateEntry\CreateEntryHandler;
use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\Enums\EntryStatus;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class CreateEntryHandlerTest extends TestCase
{
    private const ENTRY_ID = '01HZ0000000000000000000100';
    private const REFERENCE = 'PO-2024-001';
    private const NOTES = 'Entrada de prueba';
    private const CREATED_BY = '01HZ0000000000000000000020';

    public function testCreatesEntrySuccessfully(): void
    {
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::ENTRY_ID);

        $savedEntries = [];
        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Entry $e) use (&$savedEntries): void {
                $savedEntries[] = $e;
            });

        $handler = new CreateEntryHandler(
            entryRepository: $entryRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new CreateEntryCommand(
            reference: self::REFERENCE,
            notes: self::NOTES,
            createdBy: self::CREATED_BY,
        ));

        self::assertCount(1, $savedEntries);
        $entry = $savedEntries[0];
        self::assertSame(self::ENTRY_ID, (string) $entry->id());
        self::assertSame(self::REFERENCE, $entry->reference());
        self::assertSame(self::NOTES, $entry->notes());
        self::assertSame(EntryStatus::DRAFT, $entry->status());
        self::assertSame(self::CREATED_BY, (string) $entry->createdBy());
        self::assertNull($entry->confirmedAt());
    }

    public function testCreatesEntryWithNullNotes(): void
    {
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::ENTRY_ID);

        $savedEntries = [];
        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Entry $e) use (&$savedEntries): void {
                $savedEntries[] = $e;
            });

        $handler = new CreateEntryHandler(
            entryRepository: $entryRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new CreateEntryCommand(
            reference: self::REFERENCE,
            notes: null,
            createdBy: self::CREATED_BY,
        ));

        self::assertCount(1, $savedEntries);
        self::assertNull($savedEntries[0]->notes());
    }

    public function testGeneratesNewIdForEachEntry(): void
    {
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->expects($this->once())->method('generate')->willReturn(self::ENTRY_ID);

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->expects($this->once())->method('save');

        $handler = new CreateEntryHandler(
            entryRepository: $entryRepo,
            idGenerator: $idGenerator,
        );

        ($handler)(new CreateEntryCommand(
            reference: self::REFERENCE,
            notes: null,
            createdBy: self::CREATED_BY,
        ));
    }
}
