<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\TicketWaiting;
use App\Support\Domain\ValueObjects\TicketWaitingIdValueObject;

interface TicketWaitingRepositoryInterface
{
    public function findById(TicketWaitingIdValueObject $id): ?TicketWaiting;

    /** @return TicketWaiting[] */
    public function findByTicketId(string $ticketId): array;

    public function save(TicketWaiting $waiting): void;
}
