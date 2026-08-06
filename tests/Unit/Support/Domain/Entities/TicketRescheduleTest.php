<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\TicketReschedule;
use App\Support\Domain\Enums\RescheduleRequestedBy;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketRescheduleIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketRescheduleTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';
    private const RESCHEDULED_BY = '01HZ0000000000000000000003';

    public function testCreateFactoryCreatesCorrectly(): void
    {
        $newDate = new \DateTimeImmutable('+5 days');
        $previousDate = new \DateTimeImmutable('yesterday');

        $reschedule = TicketReschedule::create(
            id: TicketRescheduleIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            previousScheduledAt: $previousDate,
            newScheduledAt: $newDate,
            reason: 'Cliente solicitó cambio',
            requestedBy: RescheduleRequestedBy::CLIENT,
            rescheduledBy: self::RESCHEDULED_BY,
        );

        self::assertSame(self::ID, (string) $reschedule->id());
        self::assertSame(self::TICKET_ID, (string) $reschedule->ticketId());
        self::assertSame($previousDate, $reschedule->previousScheduledAt());
        self::assertSame($newDate, $reschedule->newScheduledAt());
        self::assertSame('Cliente solicitó cambio', $reschedule->reason());
        self::assertSame(RescheduleRequestedBy::CLIENT, $reschedule->requestedBy());
        self::assertSame(self::RESCHEDULED_BY, $reschedule->rescheduledBy());
        self::assertInstanceOf(\DateTimeImmutable::class, $reschedule->rescheduledAt());
    }

    public function testCreateThrowsOnEmptyReason(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El motivo de la reprogramación es obligatorio');

        TicketReschedule::create(
            id: TicketRescheduleIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            previousScheduledAt: new \DateTimeImmutable('yesterday'),
            newScheduledAt: new \DateTimeImmutable('+5 days'),
            reason: '',
            requestedBy: RescheduleRequestedBy::CMS,
            rescheduledBy: self::RESCHEDULED_BY,
        );
    }

    public function testCreateThrowsOnEmptyRescheduledBy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El usuario que reprograma es obligatorio');

        TicketReschedule::create(
            id: TicketRescheduleIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            previousScheduledAt: new \DateTimeImmutable('yesterday'),
            newScheduledAt: new \DateTimeImmutable('+5 days'),
            reason: 'Cliente solicitó cambio',
            requestedBy: RescheduleRequestedBy::CMS,
            rescheduledBy: '',
        );
    }

    public function testCreateWithInternalRequestedBy(): void
    {
        $reschedule = TicketReschedule::create(
            id: TicketRescheduleIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            previousScheduledAt: new \DateTimeImmutable('yesterday'),
            newScheduledAt: new \DateTimeImmutable('+5 days'),
            reason: 'Reasignación interna',
            requestedBy: RescheduleRequestedBy::CMS,
            rescheduledBy: self::RESCHEDULED_BY,
        );

        self::assertSame(RescheduleRequestedBy::CMS, $reschedule->requestedBy());
    }
}
