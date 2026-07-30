<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\TicketPause;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPauseIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketPauseTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';

    public function testStartFactoryCreatesCorrectly(): void
    {
        $pause = TicketPause::start(
            id: TicketPauseIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            pauseReason: 'Repuesto no disponible',
        );

        self::assertSame(self::ID, (string) $pause->id());
        self::assertSame(self::TICKET_ID, (string) $pause->ticketId());
        self::assertSame('Repuesto no disponible', $pause->pauseReason());
        self::assertInstanceOf(\DateTimeImmutable::class, $pause->pausedAt());
        self::assertNull($pause->resumedAt());
        self::assertTrue($pause->isActive());
    }

    public function testStartThrowsOnEmptyReason(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El motivo de la pausa es obligatorio');

        TicketPause::start(
            id: TicketPauseIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            pauseReason: '',
        );
    }

    public function testResumeSetsResumedAt(): void
    {
        $pause = TicketPause::start(
            id: TicketPauseIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            pauseReason: 'Repuesto no disponible',
        );

        $pause->resume();

        self::assertNotNull($pause->resumedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $pause->resumedAt());
        self::assertFalse($pause->isActive());
    }

    public function testCannotResumeTwice(): void
    {
        $pause = TicketPause::start(
            id: TicketPauseIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            pauseReason: 'Repuesto no disponible',
        );

        $pause->resume();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('La pausa ya fue reanudada');
        $pause->resume();
    }

    public function testIsActiveReturnsTrueWhenNotResumed(): void
    {
        $pause = TicketPause::start(
            id: TicketPauseIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            pauseReason: 'Repuesto no disponible',
        );

        self::assertTrue($pause->isActive());
    }

    public function testIsActiveReturnsFalseAfterResume(): void
    {
        $pause = TicketPause::start(
            id: TicketPauseIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            pauseReason: 'Repuesto no disponible',
        );

        $pause->resume();

        self::assertFalse($pause->isActive());
    }
}
