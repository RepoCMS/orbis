<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\Enums\EntryStatus;
use App\Inventory\Domain\Exceptions\EntryAlreadyConfirmedException;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use PHPUnit\Framework\TestCase;

final class EntryTest extends TestCase
{
    // ─── Factory helpers ─────────────────────────────────────────────────────

    private function makeEntry(string $reference = 'ENTRY-001'): Entry
    {
        return Entry::create(
            new EntryIdValueObject('01HZ0000000000000000000001'),
            $reference,
            'Notas de prueba',
            new UserIdValueObject('01HZ0000000000000000000002'),
        );
    }

    // ─── Creation ────────────────────────────────────────────────────────────

    public function testCreateReturnsDraftEntry(): void
    {
        $entry = $this->makeEntry();

        self::assertSame(EntryStatus::DRAFT, $entry->status());
        self::assertTrue($entry->isDraft());
        self::assertFalse($entry->isConfirmed());
        self::assertNull($entry->confirmedAt());
    }

    public function testCreateStoresReference(): void
    {
        $entry = $this->makeEntry('ENTRY-XYZ');
        self::assertSame('ENTRY-XYZ', $entry->reference());
    }

    // ─── Confirm ─────────────────────────────────────────────────────────────

    public function testConfirmSetsStatusAndTimestamp(): void
    {
        $entry = $this->makeEntry();
        $entry->confirm();

        self::assertSame(EntryStatus::CONFIRMED, $entry->status());
        self::assertTrue($entry->isConfirmed());
        self::assertNotNull($entry->confirmedAt());
    }

    public function testDoubleConfirmThrowsException(): void
    {
        $entry = $this->makeEntry();
        $entry->confirm();

        $this->expectException(EntryAlreadyConfirmedException::class);
        $entry->confirm();
    }

    // ─── Cancel ──────────────────────────────────────────────────────────────

    public function testCancelFromDraftSetsStatusCancelled(): void
    {
        $entry = $this->makeEntry();
        $entry->cancel();

        self::assertSame(EntryStatus::CANCELLED, $entry->status());
    }

    public function testCancelAfterConfirmThrowsException(): void
    {
        $entry = $this->makeEntry();
        $entry->confirm();

        $this->expectException(EntryAlreadyConfirmedException::class);
        $entry->cancel();
    }
}
