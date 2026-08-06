<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\CancelEntry\CancelEntryCommand;
use App\Inventory\Application\Command\CancelEntry\CancelEntryHandler;
use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\Enums\EntryStatus;
use App\Inventory\Domain\Exceptions\EntryAlreadyConfirmedException;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use PHPUnit\Framework\TestCase;

final class CancelEntryHandlerTest extends TestCase
{
    private const ENTRY_ID = '01HZ0000000000000000000100';
    private const REFERENCE = 'PO-2024-001';
    private const CREATED_BY = '01HZ0000000000000000000020';

    private function makeDraftEntry(): Entry
    {
        return Entry::create(
            id: new EntryIdValueObject(self::ENTRY_ID),
            reference: self::REFERENCE,
            notes: 'Entrada de prueba',
            createdBy: new UserIdValueObject(self::CREATED_BY),
        );
    }

    private function makeConfirmedEntry(): Entry
    {
        $entry = $this->makeDraftEntry();
        $entry->confirm();

        return $entry;
    }

    public function testCancelsDraftEntry(): void
    {
        $entry = $this->makeDraftEntry();

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn($entry);
        $entryRepo->expects($this->once())->method('save')->with($entry);

        $handler = new CancelEntryHandler(
            entryRepository: $entryRepo,
        );

        ($handler)(new CancelEntryCommand(id: self::ENTRY_ID));

        self::assertSame(EntryStatus::CANCELLED, $entry->status());
    }

    public function testThrowsWhenEntryNotFound(): void
    {
        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn(null);

        $handler = new CancelEntryHandler(
            entryRepository: $entryRepo,
        );

        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage('Entrada con ID "' . self::ENTRY_ID . '" no encontrada.');

        ($handler)(new CancelEntryCommand(id: self::ENTRY_ID));
    }

    public function testThrowsWhenEntryAlreadyConfirmed(): void
    {
        $entry = $this->makeConfirmedEntry();

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn($entry);

        $handler = new CancelEntryHandler(
            entryRepository: $entryRepo,
        );

        $this->expectException(EntryAlreadyConfirmedException::class);

        ($handler)(new CancelEntryCommand(id: self::ENTRY_ID));
    }

    public function testCancelsOnlyDraftEntries(): void
    {
        $entry = $this->makeDraftEntry();

        $entryRepo = $this->createMock(EntryRepositoryInterface::class);
        $entryRepo->method('findById')->willReturn($entry);
        $entryRepo->expects($this->once())->method('save');

        $handler = new CancelEntryHandler(
            entryRepository: $entryRepo,
        );

        ($handler)(new CancelEntryCommand(id: self::ENTRY_ID));

        self::assertTrue($entry->isDraft() === false, 'Entry should no longer be draft after cancel');
        self::assertSame(EntryStatus::CANCELLED, $entry->status());
    }
}
