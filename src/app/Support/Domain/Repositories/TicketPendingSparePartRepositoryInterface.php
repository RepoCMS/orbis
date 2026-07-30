<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\TicketPendingSparePart;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPendingSparePartIdValueObject;

interface TicketPendingSparePartRepositoryInterface
{
    public function findById(TicketPendingSparePartIdValueObject $id): ?TicketPendingSparePart;

    /** @return TicketPendingSparePart[] */
    public function findByTicket(TicketIdValueObject $ticketId): array;

    public function save(TicketPendingSparePart $pendingSparePart): void;

    public function delete(TicketPendingSparePartIdValueObject $id): void;
}
