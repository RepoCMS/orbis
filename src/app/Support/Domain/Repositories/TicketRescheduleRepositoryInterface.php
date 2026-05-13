<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\TicketReschedule;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketRescheduleIdValueObject;

interface TicketRescheduleRepositoryInterface
{
    public function findById(TicketRescheduleIdValueObject $id): ?TicketReschedule;

    /** @return TicketReschedule[] */
    public function findByTicket(TicketIdValueObject $ticketId): array;

    public function save(TicketReschedule $reschedule): void;
}
