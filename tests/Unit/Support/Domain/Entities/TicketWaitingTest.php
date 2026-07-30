<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\TicketWaiting;
use App\Support\Domain\Enums\WaitingCategory;
use App\Support\Domain\Exceptions\InvalidTimeException;
use App\Support\Domain\ValueObjects\TicketWaitingIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketWaitingTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';

    public function testStartFactoryCreatesCorrectly(): void
    {
        $waiting = TicketWaiting::start(
            id: TicketWaitingIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            category: WaitingCategory::PRE_ASSISTANCE,
        );

        self::assertSame(self::ID, (string) $waiting->id());
        self::assertSame(self::TICKET_ID, $waiting->ticketId());
        self::assertSame(WaitingCategory::PRE_ASSISTANCE, $waiting->category());
        self::assertNull($waiting->subReason());
        self::assertInstanceOf(\DateTimeImmutable::class, $waiting->startedAt());
        self::assertNull($waiting->endedAt());
        self::assertTrue($waiting->isActive());
    }

    public function testStartWithSubReason(): void
    {
        $waiting = TicketWaiting::start(
            id: TicketWaitingIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            category: WaitingCategory::DURING_ASSISTANCE,
            subReason: 'Pantalla LCD',
        );

        self::assertSame('Pantalla LCD', $waiting->subReason());
    }

    public function testStartThrowsOnEmptyTicketId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del ticket es obligatorio');

        TicketWaiting::start(
            id: TicketWaitingIdValueObject::fromString(self::ID),
            ticketId: '',
            category: WaitingCategory::PRE_ASSISTANCE,
        );
    }

    public function testEndSetsEndedAt(): void
    {
        $waiting = TicketWaiting::start(
            id: TicketWaitingIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            category: WaitingCategory::PRE_ASSISTANCE,
        );

        $waiting->end();

        self::assertNotNull($waiting->endedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $waiting->endedAt());
        self::assertFalse($waiting->isActive());
    }

    public function testCannotEndTwice(): void
    {
        $waiting = TicketWaiting::start(
            id: TicketWaitingIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            category: WaitingCategory::PRE_ASSISTANCE,
        );

        $waiting->end();

        $this->expectException(InvalidTimeException::class);
        $waiting->end();
    }

    public function testIsActiveReturnsTrueWhenNotEnded(): void
    {
        $waiting = TicketWaiting::start(
            id: TicketWaitingIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            category: WaitingCategory::PRE_ASSISTANCE,
        );

        self::assertTrue($waiting->isActive());
    }

    public function testIsActiveReturnsFalseAfterEnd(): void
    {
        $waiting = TicketWaiting::start(
            id: TicketWaitingIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            category: WaitingCategory::PRE_ASSISTANCE,
        );

        $waiting->end();

        self::assertFalse($waiting->isActive());
    }

    public function testAllWaitingCategories(): void
    {
        foreach (WaitingCategory::cases() as $category) {
            $waiting = TicketWaiting::start(
                id: TicketWaitingIdValueObject::fromString(self::ID),
                ticketId: self::TICKET_ID,
                category: $category,
            );
            self::assertSame($category, $waiting->category());
        }
    }
}
