<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\EvaluateScheduledMaintenance\EvaluateScheduledMaintenanceCommand;
use App\Support\Application\Command\EvaluateScheduledMaintenance\EvaluateScheduledMaintenanceHandler;
use App\Support\Domain\Entities\ScheduledMaintenance;
use App\Support\Domain\Enums\MaintenanceFrequencyType;
use App\Support\Domain\Repositories\ScheduledMaintenanceRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\ScheduledMaintenanceIdValueObject;
use PHPUnit\Framework\TestCase;

final class EvaluateScheduledMaintenanceHandlerTest extends TestCase
{
    private const MAINTENANCE_ID = '01HZ0000000000000000000001';
    private const CLIENT_ID      = '01HZ0000000000000000000002';
    private const TERMINAL_ID    = '01HZ0000000000000000000003';
    private const MODEL_ID       = '01HZ0000000000000000000004';
    private const CREATED_BY     = '01HZ0000000000000000000005';

    public function testEvaluatesAndCreatesTickets(): void
    {
        $maintenance = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::MAINTENANCE_ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
            nextScheduledAt: new \DateTimeImmutable('-1 day'),
        );

        $scheduledMaintenanceRepo = $this->createMock(ScheduledMaintenanceRepositoryInterface::class);
        $scheduledMaintenanceRepo->method('findOverdue')->willReturn([$maintenance]);
        $scheduledMaintenanceRepo->method('findDueSoon')->willReturn([$maintenance]);
        $scheduledMaintenanceRepo->expects($this->once())->method('save');

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('nextTicketSequence')->willReturn(1);
        $ticketRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01HZ0000000000000000000006');

        $handler = new EvaluateScheduledMaintenanceHandler(
            $scheduledMaintenanceRepo,
            $ticketRepo,
            $idGenerator,
        );

        $result = ($handler)(new EvaluateScheduledMaintenanceCommand());

        self::assertSame(1, $result->ticketsCreated);
        self::assertSame(1, $result->alertsSent);
        self::assertSame(1, $result->dueMaintenances);
    }

    public function testNoMaintenanceDue(): void
    {
        $scheduledMaintenanceRepo = $this->createMock(ScheduledMaintenanceRepositoryInterface::class);
        $scheduledMaintenanceRepo->method('findOverdue')->willReturn([]);
        $scheduledMaintenanceRepo->method('findDueSoon')->willReturn([]);
        $scheduledMaintenanceRepo->expects($this->never())->method('save');

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->expects($this->never())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $handler = new EvaluateScheduledMaintenanceHandler(
            $scheduledMaintenanceRepo,
            $ticketRepo,
            $idGenerator,
        );

        $result = ($handler)(new EvaluateScheduledMaintenanceCommand());

        self::assertSame(0, $result->ticketsCreated);
        self::assertSame(0, $result->alertsSent);
        self::assertSame(0, $result->dueMaintenances);
        self::assertSame(0, $result->upcomingAlerts);
    }
}
