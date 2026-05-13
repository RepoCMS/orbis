<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\TicketSparePart;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketSparePartIdValueObject;

interface TicketSparePartRepositoryInterface
{
    public function findById(TicketSparePartIdValueObject $id): ?TicketSparePart;

    /** @return TicketSparePart[] */
    public function findByTicket(TicketIdValueObject $ticketId): array;

    /** @return TicketSparePart[] */
    public function findByFinding(TicketFindingIdValueObject $findingId): array;

    public function save(TicketSparePart $sparePart): void;

    public function delete(TicketSparePartIdValueObject $id): void;
}
