<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\TicketPause;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPauseIdValueObject;

interface TicketPauseRepositoryInterface
{
    public function findById(TicketPauseIdValueObject $id): ?TicketPause;

    /** @return TicketPause[] */
    public function findByTicket(TicketIdValueObject $ticketId): array;

    public function save(TicketPause $pause): void;
}
