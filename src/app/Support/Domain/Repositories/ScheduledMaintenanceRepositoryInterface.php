<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\ScheduledMaintenance;
use App\Support\Domain\ValueObjects\ScheduledMaintenanceIdValueObject;

interface ScheduledMaintenanceRepositoryInterface
{
    public function findById(ScheduledMaintenanceIdValueObject $id): ?ScheduledMaintenance;

    /** @return ScheduledMaintenance[] */
    public function findByTerminalAssignmentId(string $terminalAssignmentId): array;

    /** @return ScheduledMaintenance[] */
    public function findActive(): array;

    /** @return ScheduledMaintenance[] */
    public function findDueSoon(\DateTimeImmutable $referenceDate): array;

    /** @return ScheduledMaintenance[] */
    public function findOverdue(\DateTimeImmutable $referenceDate): array;

    /** @return ScheduledMaintenance[] */
    public function findAll(): array;

    public function save(ScheduledMaintenance $maintenance): void;

    public function delete(ScheduledMaintenanceIdValueObject $id): void;
}
