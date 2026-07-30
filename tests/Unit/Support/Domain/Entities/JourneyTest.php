<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\Journey;
use App\Support\Domain\Enums\JourneyType;
use App\Support\Domain\ValueObjects\JourneyIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class JourneyTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';

    public function testStartFactoryCreatesCorrectly(): void
    {
        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            type: JourneyType::OUTWARD,
        );

        self::assertSame(self::ID, (string) $journey->id());
        self::assertSame(self::TICKET_ID, (string) $journey->ticketId());
        self::assertSame(JourneyType::OUTWARD, $journey->type());
        self::assertInstanceOf(\DateTimeImmutable::class, $journey->startedAt());
        self::assertNull($journey->arrivedAt());
        self::assertFalse($journey->isPartOfRoute());
        self::assertNull($journey->notes());
    }

    public function testStartWithOptionalParams(): void
    {
        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            type: JourneyType::RETURN,
            isPartOfRoute: true,
            notes: 'Ruta programada',
        );

        self::assertSame(JourneyType::RETURN, $journey->type());
        self::assertTrue($journey->isPartOfRoute());
        self::assertSame('Ruta programada', $journey->notes());
    }

    public function testArriveSetsArrivedAt(): void
    {
        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            type: JourneyType::OUTWARD,
        );

        $journey->arrive();

        self::assertNotNull($journey->arrivedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $journey->arrivedAt());
    }

    public function testCannotArriveTwice(): void
    {
        $journey = Journey::start(
            id: JourneyIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            type: JourneyType::OUTWARD,
        );

        $journey->arrive();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('El viaje ya tiene registrada una llegada');
        $journey->arrive();
    }
}
