<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\TicketFinding;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

interface TicketFindingRepositoryInterface
{
    public function findById(TicketFindingIdValueObject $id): ?TicketFinding;

    public function findByTicket(TicketIdValueObject $ticketId): ?TicketFinding;

    public function save(TicketFinding $finding): void;
}
