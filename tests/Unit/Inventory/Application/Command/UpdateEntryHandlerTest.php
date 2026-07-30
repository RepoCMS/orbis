<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\UpdateEntry\UpdateEntryCommand;
use App\Inventory\Application\Command\UpdateEntry\UpdateEntryHandler;
use App\Inventory\Application\Exception\EntryNotFoundException;
use App\Inventory\Application\Exception\EntryReferenceAlreadyExistsException;
use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use PHPUnit\Framework\TestCase;

final class UpdateEntryHandlerTest extends TestCase
{
    private const ENTRY_ID   = '01HZ0000000000000000000001';
    private const REFERENCE  = 'ENT-2026-001';

    public function testUpdatesEntry(): void
    {
        $entry = Entry::create(
            id: EntryIdValueObject::fromString(self::ENTRY_ID),
            reference: 'old-reference',
            notes: null,
            createdBy: new UserIdValueObject('01HZ0000000000000000000002'),
        );

        $repo = $this->createMock(EntryRepositoryInterface::class);
        $repo->method('findById')->willReturn($entry);
        $repo->method('findByReference')->willReturn(null);
        $repo->expects(self::once())->method('save')->with($entry);

        $handler = new UpdateEntryHandler($repo);
        ($handler)(new UpdateEntryCommand(
            entryId: self::ENTRY_ID,
            reference: self::REFERENCE,
            notes: 'Notas actualizadas',
            totalCost: '100.00',
        ));

        self::assertSame(self::REFERENCE, $entry->reference());
    }

    public function testThrowsWhenEntryNotFound(): void
    {
        $repo = $this->createMock(EntryRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UpdateEntryHandler($repo);

        $this->expectException(EntryNotFoundException::class);
        ($handler)(new UpdateEntryCommand(
            entryId: '01HZ0000000000000000000099',
            reference: self::REFERENCE,
            notes: null,
            totalCost: null,
        ));
    }

    public function testThrowsWhenReferenceAlreadyExists(): void
    {
        $entry = Entry::create(
            id: EntryIdValueObject::fromString(self::ENTRY_ID),
            reference: 'different-ref',
            notes: null,
            createdBy: new UserIdValueObject('01HZ0000000000000000000002'),
        );

        $existing = Entry::create(
            id: EntryIdValueObject::fromString('01HZ00000000000000000000AA'),
            reference: self::REFERENCE,
            notes: null,
            createdBy: new UserIdValueObject('01HZ0000000000000000000003'),
        );

        $repo = $this->createMock(EntryRepositoryInterface::class);
        $repo->method('findById')->willReturn($entry);
        $repo->method('findByReference')->willReturn($existing);

        $handler = new UpdateEntryHandler($repo);

        $this->expectException(EntryReferenceAlreadyExistsException::class);
        ($handler)(new UpdateEntryCommand(
            entryId: self::ENTRY_ID,
            reference: self::REFERENCE,
            notes: null,
            totalCost: null,
        ));
    }
}
