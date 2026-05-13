<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

interface TicketRepositoryInterface
{
    public function findById(TicketIdValueObject $id): ?Ticket;

    public function findByTicketNumber(string $ticketNumber): ?Ticket;

    /**
     * @param TicketStatus[] $statuses
     * @return Ticket[]
     */
    public function findByStatuses(array $statuses): array;

    /**
     * @return Ticket[]
     */
    public function findByClientId(string $clientId): array;

    /**
     * @return Ticket[]
     */
    public function findByEmployeeId(string $employeeId): array;

    /**
     * @return Ticket[]
     */
    public function findAll(): array;

    public function nextTicketSequence(int $year): int;

    public function save(Ticket $ticket): void;

    public function delete(TicketIdValueObject $id): void;
}
