<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\Entities;

use App\Client\Domain\Entities\TerminalWarranty;
use App\Client\Domain\Enums\WarrantyType;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalWarrantyIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TerminalWarrantyTest extends TestCase
{
    private function createWarranty(): TerminalWarranty
    {
        return TerminalWarranty::register(
            new TerminalWarrantyIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new TerminalIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            WarrantyType::MANUFACTURER,
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-12-31'),
            'Garantía estándar de fábrica',
        );
    }

    public function testRegisterStoresAllFields(): void
    {
        $warranty = $this->createWarranty();

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $warranty->id());
        self::assertSame('01BXZ4MDEKTSV4RRFFQ69G5ABC', (string) $warranty->terminalId());
        self::assertSame(WarrantyType::MANUFACTURER, $warranty->type());
        self::assertSame('2025-01-01', $warranty->startDate()->format('Y-m-d'));
        self::assertSame('2025-12-31', $warranty->endDate()->format('Y-m-d'));
        self::assertSame('Garantía estándar de fábrica', $warranty->terms());
        self::assertNotNull($warranty->createdAt());
        self::assertNull($warranty->updatedAt());
    }

    public function testRegisterWithoutTerms(): void
    {
        $warranty = TerminalWarranty::register(
            new TerminalWarrantyIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new TerminalIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            WarrantyType::MANUFACTURER,
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-12-31'),
        );

        self::assertNull($warranty->terms());
    }

    public function testRegisterWithExtendedType(): void
    {
        $warranty = TerminalWarranty::register(
            new TerminalWarrantyIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new TerminalIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            WarrantyType::EXTENDED,
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2027-12-31'),
        );

        self::assertSame(WarrantyType::EXTENDED, $warranty->type());
    }

    public function testChangeTypeUpdatesValue(): void
    {
        $warranty = $this->createWarranty();

        $warranty->changeType(WarrantyType::EXTENDED);

        self::assertSame(WarrantyType::EXTENDED, $warranty->type());
        self::assertNotNull($warranty->updatedAt());
    }

    public function testIsExpiredReturnsTrueWhenPastEndDate(): void
    {
        $warranty = TerminalWarranty::register(
            new TerminalWarrantyIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new TerminalIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            WarrantyType::MANUFACTURER,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-12-31'),
        );

        self::assertTrue($warranty->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenCurrentBeforeEndDate(): void
    {
        $warranty = TerminalWarranty::register(
            new TerminalWarrantyIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new TerminalIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            WarrantyType::MANUFACTURER,
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2030-12-31'),
        );

        self::assertFalse($warranty->isExpired());
    }

    public function testIsAboutToExpireReturnsTrueWhenWithinThreshold(): void
    {
        $nearFuture = new DateTimeImmutable('+20 days');
        $warranty = TerminalWarranty::register(
            new TerminalWarrantyIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new TerminalIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            WarrantyType::MANUFACTURER,
            new DateTimeImmutable('2025-01-01'),
            $nearFuture,
        );

        self::assertTrue($warranty->isAboutToExpire(30));
    }

    public function testIsAboutToExpireReturnsFalseWhenFarFromEndDate(): void
    {
        $warranty = TerminalWarranty::register(
            new TerminalWarrantyIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new TerminalIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            WarrantyType::MANUFACTURER,
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2030-12-31'),
        );

        self::assertFalse($warranty->isAboutToExpire(30));
    }

    public function testIsAboutToExpireReturnsFalseWhenAlreadyExpired(): void
    {
        $warranty = TerminalWarranty::register(
            new TerminalWarrantyIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new TerminalIdValueObject('01BXZ4MDEKTSV4RRFFQ69G5ABC'),
            WarrantyType::MANUFACTURER,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-12-31'),
        );

        self::assertFalse($warranty->isAboutToExpire(30));
    }

    public function testExtendEndDateUpdatesValue(): void
    {
        $warranty = $this->createWarranty();
        $newEnd = new DateTimeImmutable('2026-06-30');

        $warranty->extendEndDate($newEnd);

        self::assertSame('2026-06-30', $warranty->endDate()->format('Y-m-d'));
        self::assertNotNull($warranty->updatedAt());
    }

    public function testExtendEndDateThrowsWhenNotLater(): void
    {
        $warranty = $this->createWarranty();
        $sameEnd = new DateTimeImmutable('2025-12-31');

        $this->expectException(\InvalidArgumentException::class);

        $warranty->extendEndDate($sameEnd);
    }

    public function testExtendEndDateThrowsWhenEarlier(): void
    {
        $warranty = $this->createWarranty();
        $earlierEnd = new DateTimeImmutable('2025-06-30');

        $this->expectException(\InvalidArgumentException::class);

        $warranty->extendEndDate($earlierEnd);
    }

    public function testUpdateTermsChangesValue(): void
    {
        $warranty = $this->createWarranty();

        $warranty->updateTerms('Nuevos términos');

        self::assertSame('Nuevos términos', $warranty->terms());
        self::assertNotNull($warranty->updatedAt());
    }

    public function testUpdateTermsToNullClearsIt(): void
    {
        $warranty = $this->createWarranty();

        $warranty->updateTerms(null);

        self::assertNull($warranty->terms());
    }

    public function testUpdateTermsTrimsValue(): void
    {
        $warranty = $this->createWarranty();

        $warranty->updateTerms('  Términos con espacios  ');

        self::assertSame('Términos con espacios', $warranty->terms());
    }
}
