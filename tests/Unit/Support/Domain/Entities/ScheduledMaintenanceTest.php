<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\ScheduledMaintenance;
use App\Support\Domain\Enums\MaintenanceFrequencyType;
use App\Support\Domain\ValueObjects\ScheduledMaintenanceIdValueObject;
use PHPUnit\Framework\TestCase;

final class ScheduledMaintenanceTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const CLIENT_ID = '01HZ0000000000000000000002';
    private const TERMINAL_ASSIGNMENT_ID = '01HZ0000000000000000000003';
    private const MODEL_ID = '01HZ0000000000000000000004';
    private const CREATED_BY = '01HZ0000000000000000000005';

    public function testCreateFactoryCreatesCorrectly(): void
    {
        $sm = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
        );

        self::assertSame(self::ID, (string) $sm->id());
        self::assertSame(self::CLIENT_ID, $sm->clientId());
        self::assertSame(self::TERMINAL_ASSIGNMENT_ID, $sm->terminalAssignmentId());
        self::assertSame(self::MODEL_ID, $sm->modelId());
        self::assertSame(MaintenanceFrequencyType::MONTHLY, $sm->frequency());
        self::assertTrue($sm->isActive());
        self::assertSame(self::CREATED_BY, $sm->createdBy());
        self::assertInstanceOf(\DateTimeImmutable::class, $sm->createdAt());
        self::assertNull($sm->lastCompletedAt());
        self::assertNull($sm->updatedAt());
        self::assertNull($sm->deletedAt());
        self::assertNotNull($sm->nextScheduledAt());
    }

    public function testCreateWithExplicitNextScheduledAt(): void
    {
        $futureDate = new \DateTimeImmutable('+2 months');
        $sm = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::QUARTERLY,
            createdBy: self::CREATED_BY,
            nextScheduledAt: $futureDate,
        );

        self::assertSame($futureDate, $sm->nextScheduledAt());
    }

    public function testCreateThrowsOnEmptyClientId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del cliente es obligatorio');

        ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: '',
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
        );
    }

    public function testCreateThrowsOnEmptyTerminalAssignmentId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID de asignación de terminal es obligatorio');

        ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: '',
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
        );
    }

    public function testCreateThrowsOnEmptyModelId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del modelo es obligatorio');

        ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: '',
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
        );
    }

    public function testCreateThrowsOnEmptyCreatedBy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El usuario creador es obligatorio');

        ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: '',
        );
    }

    public function testScheduleNextUpdatesDates(): void
    {
        $sm = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
        );

        $originalNext = $sm->nextScheduledAt();

        $sm->scheduleNext();

        self::assertNotNull($sm->lastCompletedAt());
        self::assertNotNull($sm->updatedAt());
        self::assertNotSame($originalNext, $sm->nextScheduledAt());
    }

    public function testIsDueReturnsTrueWhenReferenceDateIsPastNextScheduled(): void
    {
        $nextDate = new \DateTimeImmutable('-7 days');
        $sm = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
            nextScheduledAt: $nextDate,
        );

        self::assertTrue($sm->isDue(new \DateTimeImmutable()));
    }

    public function testIsDueReturnsFalseWhenReferenceDateIsBeforeNextScheduled(): void
    {
        $sm = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
            nextScheduledAt: new \DateTimeImmutable('+1 month'),
        );

        self::assertFalse($sm->isDue());
    }

    public function testIsDueReturnsFalseWhenInactive(): void
    {
        $sm = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
            nextScheduledAt: new \DateTimeImmutable('-1 day'),
        );

        $sm->deactivate();

        self::assertFalse($sm->isDue());
    }

    public function testActivateAndDeactivate(): void
    {
        $sm = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
        );

        self::assertTrue($sm->isActive());

        $sm->deactivate();
        self::assertFalse($sm->isActive());

        $sm->activate();
        self::assertTrue($sm->isActive());
    }

    public function testUpdateNextScheduledAt(): void
    {
        $sm = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
        );

        $newDate = new \DateTimeImmutable('+6 months');
        $sm->updateNextScheduledAt($newDate);

        self::assertSame($newDate, $sm->nextScheduledAt());
        self::assertNotNull($sm->updatedAt());
    }

    public function testSoftDelete(): void
    {
        $sm = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
        );

        $sm->softDelete();

        self::assertNotNull($sm->deletedAt());
    }

    public function testIsDueSoonReturnsTrueWhenWithinAlertDays(): void
    {
        $in6Days = new \DateTimeImmutable('+6 days');
        $sm = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
            nextScheduledAt: new \DateTimeImmutable('+7 days'),
        );

        self::assertTrue($sm->isDueSoon($in6Days));
    }

    public function testIsDueSoonReturnsFalseWhenBeyondAlertDays(): void
    {
        $sm = ScheduledMaintenance::create(
            id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
            modelId: self::MODEL_ID,
            frequency: MaintenanceFrequencyType::MONTHLY,
            createdBy: self::CREATED_BY,
            nextScheduledAt: new \DateTimeImmutable('+30 days'),
        );

        self::assertFalse($sm->isDueSoon());
    }

    public function testAllFrequencies(): void
    {
        foreach (MaintenanceFrequencyType::cases() as $frequency) {
            $sm = ScheduledMaintenance::create(
                id: ScheduledMaintenanceIdValueObject::fromString(self::ID),
                clientId: self::CLIENT_ID,
                terminalAssignmentId: self::TERMINAL_ASSIGNMENT_ID,
                modelId: self::MODEL_ID,
                frequency: $frequency,
                createdBy: self::CREATED_BY,
            );
            self::assertSame($frequency, $sm->frequency());
        }
    }
}
